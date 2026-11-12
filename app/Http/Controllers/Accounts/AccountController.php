<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AdjustAccountBalanceRequest;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Space;
use App\Models\TransactionEntry;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $rawAccounts = $space->accounts()
            ->with('creditAccount')
            ->orderBy('archived_at')
            ->orderBy('name')
            ->get();

        $allowedSorts = ['name_asc', 'name_desc', 'type_asc', 'type_desc', 'balance_desc'];
        $requestedSort = (string) $request->query('sort', 'name_asc');
        $sort = in_array($requestedSort, $allowedSorts, true) ? $requestedSort : 'name_asc';

        $requestedGroup = (string) $request->query('group', 'none');
        $groupByType = in_array($requestedGroup, ['type', '1', 'true'], true);

        $accountBalances = $this->computeAccountBalances($space, $rawAccounts);
        $accounts = $this->sortAccounts($rawAccounts, $sort, $accountBalances);
        $accountGroups = $groupByType
            ? $this->buildAccountGroups($accounts, $sort, $accountBalances)
            : collect();

        $activeAccounts = $accounts->whereNull('archived_at');
        $totalBalance = $space->totalBalance();

        $requestedView = $request->query('view');
        $hasExplicitView = in_array($requestedView, ['list', 'card'], true);
        $viewMode = $hasExplicitView ? $requestedView : 'list';

        return view('accounts.index', compact(
            'accounts',
            'activeAccounts',
            'totalBalance',
            'viewMode',
            'hasExplicitView',
            'sort',
            'groupByType',
            'accountGroups',
            'accountBalances'
        ));
    }

    public function create(): View
    {
        return view('accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        $validated = $request->validated();

        $isFirst = $space->accounts()->count() === 0;
        $isDefault = $request->boolean('is_default') || $isFirst;

        $account = $space->accounts()->create(array_merge($validated, [
            'is_default' => $isDefault,
        ]));

        if ($isDefault && ! $isFirst) {
            $account->makeDefault();
        }

        return redirect()->route('accounts.index')
            ->with('success', "Rekening '{$account->name}' berhasil ditambahkan.");
    }

    public function show(Account $account): View
    {
        Gate::authorize('view', $account);

        $account->load('creditAccount');

        $entries = $account->entries()
            ->with(['transaction.category', 'transaction.creator'])
            ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
            ->orderBy('financial_transactions.transaction_date', 'desc')
            ->orderBy('financial_transactions.transaction_time', 'desc')
            ->orderBy('transaction_entries.id', 'desc')
            ->select('transaction_entries.*')
            ->paginate(20);

        return view('accounts.show', compact('account', 'entries'));
    }

    public function edit(Account $account): View
    {
        Gate::authorize('update', $account);

        return view('accounts.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $validated = $request->validated();
        $oldBalance = (int) $account->opening_balance;
        $newBalance = (int) $validated['opening_balance'];
        $oldOpenedOn = $account->opened_on->format('Y-m-d');
        $newOpenedOn = $validated['opened_on'];

        if ($oldBalance !== $newBalance || $oldOpenedOn !== $newOpenedOn) {
            $account->openingBalanceLogs()->create([
                'actor_id' => $request->user()->id,
                'old_opening_balance' => $oldBalance,
                'new_opening_balance' => $newBalance,
                'old_opened_on' => $oldOpenedOn,
                'new_opened_on' => $newOpenedOn,
                'reason' => $validated['reason'] ?? null,
            ]);
        }

        $account->update($validated);

        if ($request->has('is_default') && $request->boolean('is_default')) {
            $account->makeDefault();
        }

        return redirect()->route('accounts.index')
            ->with('success', "Rekening '{$account->name}' berhasil diperbarui.");
    }

    public function archive(Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->archive();

        return redirect()->route('accounts.index')
            ->with('success', "Rekening '{$account->name}' berhasil diarsipkan.");
    }

    public function unarchive(Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->unarchive();

        return redirect()->route('accounts.index')
            ->with('success', "Rekening '{$account->name}' berhasil diaktifkan kembali.");
    }

    public function setDefault(Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        if ($account->isArchived()) {
            return redirect()->route('accounts.index')
                ->with('error', 'Rekening yang diarsipkan tidak dapat dijadikan rekening default.');
        }

        $account->makeDefault();

        return redirect()->route('accounts.index')
            ->with('success', "Rekening '{$account->name}' telah ditetapkan sebagai rekening default transaksi.");
    }

    public function adjust(AdjustAccountBalanceRequest $request, Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        if ($account->isArchived()) {
            return redirect()->back()
                ->with('error', 'Rekening yang telah diarsipkan tidak dapat disesuaikan saldonya.');
        }

        $validated = $request->validated();
        $targetBalance = round((float) $validated['target_balance'], 2);
        $adjDate = $validated['adjustment_date'];
        $adjTime = ! empty($validated['adjustment_time']) ? $validated['adjustment_time'] : now()->format('H:i:s');
        $reason = $validated['reason'] ?? null;
        $space = $account->space;
        $user = $request->user();

        $diff = DB::transaction(function () use ($account, $targetBalance, $adjDate, $adjTime, $reason, $space, $user) {
            $lockedAccount = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();
            $currentBalance = $lockedAccount->currentBalance();
            $difference = round($targetBalance - $currentBalance, 2);

            if ($difference == 0.0) {
                return 0.0;
            }

            if ($difference > 0) {
                $category = $space->categories()->firstOrCreate(
                    ['name' => 'Penyesuaian Saldo', 'type' => 'income'],
                    ['name' => 'Penyesuaian Saldo', 'type' => 'income']
                );

                $this->transactionService->recordIncome($space, $user, [
                    'account_id' => $lockedAccount->id,
                    'category_id' => $category->id,
                    'amount' => $difference,
                    'transaction_date' => $adjDate,
                    'transaction_time' => $adjTime,
                    'description' => 'Penyesuaian Saldo: '.($reason ?: 'Koreksi saldo riil kas/bank'),
                ]);
            } else {
                $category = $space->categories()->firstOrCreate(
                    ['name' => 'Penyesuaian Saldo', 'type' => 'expense'],
                    ['name' => 'Penyesuaian Saldo', 'type' => 'expense']
                );

                $this->transactionService->recordExpense($space, $user, [
                    'account_id' => $lockedAccount->id,
                    'category_id' => $category->id,
                    'amount' => abs($difference),
                    'transaction_date' => $adjDate,
                    'transaction_time' => $adjTime,
                    'description' => 'Penyesuaian Saldo: '.($reason ?: 'Koreksi saldo riil kas/bank'),
                ]);
            }

            return $difference;
        });

        if ($diff == 0.0) {
            return redirect()->back()
                ->with('info', "Saldo rekening '{$account->name}' sudah sesuai dengan nominal target (tidak ada selisih).");
        }

        return redirect()->back()
            ->with('success', "Saldo rekening '{$account->name}' berhasil disesuaikan menjadi Rp ".number_format($targetBalance, 0, ',', '.').'.');
    }

    /**
     * Hitung saldo terkini setiap rekening pada space aktif dalam satu kueri agregat.
     *
     * @param  Collection<int, Account>  $accounts
     * @return array<int, float>
     */
    private function computeAccountBalances(Space $space, Collection $accounts): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $postedSums = TransactionEntry::query()
            ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
            ->where('financial_transactions.space_id', $space->id)
            ->where('financial_transactions.status', 'posted')
            ->groupBy('transaction_entries.account_id')
            ->selectRaw('transaction_entries.account_id, SUM(transaction_entries.amount_signed) as total_signed')
            ->pluck('total_signed', 'account_id');

        $balances = [];
        foreach ($accounts as $account) {
            $balances[$account->id] = round(
                (float) $account->opening_balance + (float) ($postedSums[$account->id] ?? 0),
                2
            );
        }

        return $balances;
    }

    /**
     * Urutkan koleksi rekening berdasarkan parameter sort yang diminta.
     *
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, float>  $accountBalances
     * @return Collection<int, Account>
     */
    private function sortAccounts(Collection $accounts, string $sort, array $accountBalances): Collection
    {
        return $accounts->sort(function (Account $a, Account $b) use ($sort, $accountBalances): int {
            $archivedA = $a->isArchived() ? 1 : 0;
            $archivedB = $b->isArchived() ? 1 : 0;
            $nameA = mb_strtolower($a->name);
            $nameB = mb_strtolower($b->name);

            if ($sort === 'type_asc' || $sort === 'type_desc') {
                $typeCompare = $this->compareAccountTypes($a->type, $b->type, $sort === 'type_desc');
                if ($typeCompare !== 0) {
                    return $typeCompare;
                }

                if ($archivedA !== $archivedB) {
                    return $archivedA <=> $archivedB;
                }

                return $nameA <=> $nameB;
            }

            if ($archivedA !== $archivedB) {
                return $archivedA <=> $archivedB;
            }

            if ($sort === 'balance_desc') {
                $balCompare = ($accountBalances[$b->id] ?? 0.0) <=> ($accountBalances[$a->id] ?? 0.0);
                if ($balCompare !== 0) {
                    return $balCompare;
                }

                return $nameA <=> $nameB;
            }

            if ($sort === 'name_desc') {
                return $nameB <=> $nameA;
            }

            return $nameA <=> $nameB;
        })->values();
    }

    /**
     * Kelompokkan rekening berdasarkan tipe akun dengan urutan grup konsisten.
     *
     * @param  Collection<int, Account>  $sortedAccounts
     * @param  array<int, float>  $accountBalances
     * @return Collection<int, array{
     *     type: string,
     *     label: string,
     *     accounts: Collection<int, Account>,
     *     active_count: int,
     *     archived_count: int,
     *     total_amount: float,
     *     summary_label: string
     * }>
     */
    private function buildAccountGroups(Collection $sortedAccounts, string $sort, array $accountBalances): Collection
    {
        $reverseTypeOrder = $sort === 'type_desc';

        $grouped = $sortedAccounts->groupBy('type');

        $sortedTypeKeys = $grouped->keys()
            ->sort(fn (string $typeA, string $typeB): int => $this->compareAccountTypes($typeA, $typeB, $reverseTypeOrder))
            ->values();

        return $sortedTypeKeys->map(function (string $type) use ($grouped, $accountBalances): array {
            /** @var Collection<int, Account> $items */
            $items = $grouped->get($type);

            $activeItems = $items->filter(fn (Account $a): bool => ! $a->isArchived())->values();
            $archivedItems = $items->filter(fn (Account $a): bool => $a->isArchived())->values();
            $orderedItems = $activeItems->concat($archivedItems)->values();

            if ($type === 'credit_card' && $orderedItems->contains(fn (Account $a): bool => $a->availableLimit() !== null)) {
                $totalAmount = (float) $orderedItems->sum(fn (Account $a): float => (float) ($a->availableLimit() ?? 0.0));
                $summaryLabel = 'Total Sisa Limit';
            } elseif (in_array($type, ['loan', 'paylater'], true)) {
                $totalAmount = (float) $orderedItems->sum(fn (Account $a): float => abs((float) ($accountBalances[$a->id] ?? 0.0)));
                $summaryLabel = 'Total Kewajiban';
            } else {
                $totalAmount = (float) $orderedItems->sum(fn (Account $a): float => (float) ($accountBalances[$a->id] ?? 0.0));
                $summaryLabel = 'Total Saldo';
            }

            return [
                'type' => $type,
                'label' => Account::labelForType($type),
                'accounts' => $orderedItems,
                'active_count' => $activeItems->count(),
                'archived_count' => $archivedItems->count(),
                'total_amount' => round($totalAmount, 2),
                'summary_label' => $summaryLabel,
            ];
        })->values();
    }

    /**
     * Bandingkan urutan prioritas dua tipe akun untuk pengurutan naik atau turun.
     */
    private function compareAccountTypes(string $typeA, string $typeB, bool $descending): int
    {
        $priorityA = Account::typePriority($typeA);
        $priorityB = Account::typePriority($typeB);

        $compare = $descending ? ($priorityB <=> $priorityA) : ($priorityA <=> $priorityB);
        if ($compare !== 0) {
            return $compare;
        }

        $labelA = mb_strtolower(Account::labelForType($typeA));
        $labelB = mb_strtolower(Account::labelForType($typeB));

        return $descending ? ($labelB <=> $labelA) : ($labelA <=> $labelB);
    }
}
