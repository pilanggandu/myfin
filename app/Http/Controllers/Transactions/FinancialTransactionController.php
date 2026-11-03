<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\FinancialTransaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['category', 'entries.account', 'creator', 'member'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('type') && in_array($request->type, ['income', 'expense', 'transfer'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, ['posted', 'void'])) {
            $query->where('status', $request->status);
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

        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $transactions = $query->paginate(15)->withQueryString();

        $accounts = $space->activeAccounts()->orderBy('name')->get();
        $incomeCategories = $space->categories()->income()->orderBy('name')->get();
        $expenseCategories = $space->categories()->expense()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();

        return view('transactions.index', compact(
            'transactions',
            'accounts',
            'incomeCategories',
            'expenseCategories',
            'members'
        ));
    }

    public function create(Request $request): View
    {
        $space = app('currentSpace');
        $accounts = $space->activeAccounts()->orderByDesc('is_default')->orderBy('name')->get();
        $defaultAccount = $space->defaultAccount();
        $incomeCategories = $space->activeCategories()->income()->orderBy('name')->get();
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $requestKey = (string) Str::uuid();

        $defaultType = $request->get('type', 'expense');

        return view('transactions.create', compact(
            'accounts',
            'defaultAccount',
            'incomeCategories',
            'expenseCategories',
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
                    'amount' => $transaction->totalAmount(),
                ],
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

        $transaction->load(['entries.account', 'category', 'creator', 'updater', 'revisions.actor', 'member']);

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
        $incomeCategories = $space->activeCategories()->income()->orderBy('name')->get();
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();

        $transaction->load(['entries.account', 'category', 'member']);

        return view('transactions.edit', compact(
            'transaction',
            'accounts',
            'incomeCategories',
            'expenseCategories',
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
