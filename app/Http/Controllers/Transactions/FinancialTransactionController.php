<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FinancialTransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');

        $query = $space->financialTransactions()
            ->with(['category', 'feeCategory', 'entries.account', 'creator', 'member']);

        $sort = $request->input('sort', 'date_desc');
        if ($sort === 'date_asc' || $request->input('direction') === 'asc') {
            $query->oldestFirst();
        } else {
            $query->latestFirst();
        }

        $this->applyIndexFilters($query, $request);

        $today = $space ? $space->now()->toDateString() : now()->toDateString();
        $showUpcoming = $request->boolean('include_future', false);

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        } elseif (! $showUpcoming && (! $request->filled('start_date') || $request->start_date <= $today)) {
            $query->whereDate('transaction_date', '<=', $today);
        }

        $upcomingQuery = $space->financialTransactions()
            ->whereDate('transaction_date', '>', $today);

        $this->applyIndexFilters($upcomingQuery, $request, forUpcomingCount: true);

        $upcomingCount = $upcomingQuery->count();

        $transactions = $query->paginate(15)->withQueryString();

        $accounts = $space->activeAccounts()->orderBy('name')->get();
        $recentAccountIds = $space->recentAccountIds(limit: 5);
        $incomeCategories = $space->categories()->income()->orderBy('name')->get();
        $expenseCategories = $space->categories()->expense()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $recentCategoryIds = $space->recentCategoryIds(null, 6, activeOnly: false);
        $recentFilterCategories = $this->orderCategoriesByIds(
            $expenseCategories->concat($incomeCategories),
            $recentCategoryIds
        );

        return view('transactions.index', compact(
            'transactions',
            'accounts',
            'recentAccountIds',
            'incomeCategories',
            'expenseCategories',
            'members',
            'recentCategoryIds',
            'recentFilterCategories',
            'upcomingCount',
            'showUpcoming',
            'today'
        ));
    }

    protected function applyIndexFilters(Builder|HasMany $query, Request $request, bool $forUpcomingCount = false): void
    {
        if ($request->filled('type') && in_array($request->type, ['income', 'expense', 'transfer'])) {
            $query->where('type', $request->type);
        }

        $status = $request->input('status', 'posted');
        if ($status !== 'all') {
            if (! $forUpcomingCount && in_array($status, ['posted', 'void'])) {
                $query->where('status', $status);
            } else {
                $query->where('status', 'posted');
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('account_id')) {
            $query->whereHas('entries', function ($q) use ($request) {
                $q->where('account_id', $request->account_id);
            });
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }
    }

    /**
     * Order a category collection by an explicit list of recent category IDs.
     *
     * @param  Collection<int, Category>  $categories
     * @param  list<int>  $orderedIds
     * @return Collection<int, Category>
     */
    protected function orderCategoriesByIds($categories, array $orderedIds)
    {
        $byId = $categories->keyBy('id');

        return collect($orderedIds)
            ->map(fn (int $id) => $byId->get($id))
            ->filter()
            ->values();
    }

    public function create(Request $request): View
    {
        $space = app('currentSpace');
        $accounts = $space->activeAccounts()->orderByDesc('is_default')->orderBy('name')->get();
        $defaultAccount = $space->defaultAccount();
        $recentAccountIds = $space->recentAccountIds(limit: 5);
        $incomeCategories = $space->activeCategories()->income()->orderBy('name')->get();
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $recentIncomeCategoryIds = $space->recentCategoryIds('income', 5);
        $recentExpenseCategoryIds = $space->recentCategoryIds('expense', 5);
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $requestKey = (string) Str::uuid();

        $defaultType = $request->get('type', 'expense');

        return view('transactions.create', compact(
            'accounts',
            'defaultAccount',
            'recentAccountIds',
            'incomeCategories',
            'expenseCategories',
            'recentIncomeCategoryIds',
            'recentExpenseCategoryIds',
            'members',
            'requestKey',
            'defaultType'
        ));
    }

    public function store(StoreTransactionRequest $request): RedirectResponse|JsonResponse
    {
        $space = app('currentSpace');
        $user = $request->user();
        $validated = $request->validated();

        $transaction = match ($validated['type']) {
            'income' => $this->transactionService->recordIncome($space, $user, $validated),
            'expense' => $this->transactionService->recordExpense($space, $user, $validated),
            'transfer' => $this->transactionService->recordTransfer($space, $user, $validated),
        };

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dicatat.',
                'transaction' => [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'type' => $transaction->type,
                    'category_id' => $transaction->category_id,
                    'amount' => $transaction->totalAmount(),
                ],
                'recent_category_ids' => [
                    'income' => $space->recentCategoryIds('income', 5),
                    'expense' => $space->recentCategoryIds('expense', 5),
                ],
                'recent_account_ids' => $space->recentAccountIds(limit: 5),
            ]);
        }

        if ($request->input('action') === 'save_and_again' || $request->boolean('save_and_again')) {
            $redirectParams = [
                'type' => $validated['type'],
                'date' => $validated['transaction_date'],
            ];

            if (! empty($validated['transaction_time'])) {
                $redirectParams['time'] = $validated['transaction_time'];
            }

            if (! empty($validated['account_id'])) {
                $redirectParams['account_id'] = $validated['account_id'];
            } elseif (! empty($validated['source_account_id'])) {
                $redirectParams['source_account_id'] = $validated['source_account_id'];
            }

            return redirect()->route('transactions.create', $redirectParams)
                ->with('success', 'Transaksi berhasil dicatat. Silakan input transaksi berikutnya.');
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dicatat.');
    }

    public function show(FinancialTransaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['entries.account', 'category', 'feeCategory', 'creator', 'updater', 'revisions.actor', 'member']);

        return view('transactions.show', compact('transaction'));
    }

    public function edit(FinancialTransaction $transaction): View|RedirectResponse
    {
        Gate::authorize('update', $transaction);

        if ($transaction->isVoid()) {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Transaksi yang telah dibatalkan tidak dapat diubah.');
        }

        $space = $transaction->space;
        $accounts = $space->activeAccounts()->orderBy('name')->get();
        $recentAccountIds = $space->recentAccountIds(limit: 5);
        $incomeCategories = $space->activeCategories()->income()->orderBy('name')->get();
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $recentIncomeCategoryIds = $space->recentCategoryIds('income', 5);
        $recentExpenseCategoryIds = $space->recentCategoryIds('expense', 5);
        $members = $space->customMembers()->active()->orderBy('name')->get();

        $transaction->load(['entries.account', 'category', 'feeCategory', 'member']);

        return view('transactions.edit', compact(
            'transaction',
            'accounts',
            'recentAccountIds',
            'incomeCategories',
            'expenseCategories',
            'recentIncomeCategoryIds',
            'recentExpenseCategoryIds',
            'members'
        ));
    }

    public function update(UpdateTransactionRequest $request, FinancialTransaction $transaction): RedirectResponse
    {
        Gate::authorize('update', $transaction);

        $validated = $request->validated();
        $reason = $validated['reason'] ?? null;

        $this->transactionService->correctTransaction($transaction, $request->user(), $validated, $reason);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil dikoreksi.');
    }

    public function void(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        Gate::authorize('void', $transaction);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->transactionService->voidTransaction($transaction, $request->user(), $request->reason);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dibatalkan (void).');
    }
}
