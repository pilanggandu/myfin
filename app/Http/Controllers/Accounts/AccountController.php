<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AdjustAccountBalanceRequest;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $accounts = $space->accounts()
            ->with('creditAccount')
            ->orderBy('archived_at')
            ->orderBy('name')
            ->get();

        $activeAccounts = $accounts->whereNull('archived_at');
        $totalBalance = $space->totalBalance();

        return view('accounts.index', compact('accounts', 'activeAccounts', 'totalBalance'));
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
}
