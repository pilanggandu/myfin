<?php

namespace App\Http\Controllers\Recurring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recurring\ConfirmOccurrenceRequest;
use App\Http\Requests\Recurring\StoreRecurringScheduleRequest;
use App\Http\Requests\Recurring\UpdateRecurringScheduleRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\Member;
use App\Models\RecurringSchedule;
use App\Services\RecurringService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecurringScheduleController extends Controller
{
    public function __construct(
        protected RecurringService $recurringService
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $tz = $space->timezone ?? 'Asia/Jakarta';

        $status = $request->query('status', 'active');
        $type = $request->query('type', 'all');
        $memberId = $request->query('member_id');

        $query = RecurringSchedule::where('space_id', $space->id)
            ->with(['member', 'category', 'sourceAccount.creditAccount', 'destinationAccount', 'creditAccount']);

        if ($status === 'archived') {
            $query->archived();
        } elseif ($status === 'paused') {
            $query->paused();
        } else {
            $query->active();
        }

        if (in_array($type, ['expense', 'transfer', 'credit_payment'], true)) {
            $query->where('type', $type);
        }

        if ($memberId) {
            $query->where('member_id', $memberId);
        }

        $schedules = $query->orderBy('next_due_date', 'asc')->get();

        $activeCount = RecurringSchedule::where('space_id', $space->id)->active()->count();
        $pausedCount = RecurringSchedule::where('space_id', $space->id)->paused()->count();
        $archivedCount = RecurringSchedule::where('space_id', $space->id)->archived()->count();
        $expenseCount = RecurringSchedule::where('space_id', $space->id)->active()->where('type', 'expense')->count();
        $transferCount = RecurringSchedule::where('space_id', $space->id)->active()->where('type', 'transfer')->count();
        $creditPaymentCount = RecurringSchedule::where('space_id', $space->id)->active()->where('type', 'credit_payment')->count();

        $members = Member::where('space_id', $space->id)->active()->orderBy('name')->get();
        $accounts = Account::where('space_id', $space->id)->active()->expenseSources()->with('creditAccount')->orderBy('name')->get();
        $categories = Category::where('space_id', $space->id)->expense()->active()->orderBy('name')->get();

        $creditSuggestions = [];
        foreach ($schedules as $sch) {
            if ($sch->isCreditPayment() && $sch->creditAccount && ! isset($creditSuggestions[$sch->credit_account_id])) {
                $statementDue = (int) $sch->creditAccount->statement_due_amount;
                $liability = (int) $sch->creditAccount->currentLiability();
                $creditSuggestions[$sch->credit_account_id] = [
                    'statement_due' => $statementDue,
                    'liability' => $liability,
                    'suggested' => $statementDue > 0 ? $statementDue : $liability,
                ];
            }
        }

        $today = Carbon::now($tz)->startOfDay();
        $overdueCount = 0;
        $dueTodayCount = 0;

        foreach ($schedules as $sch) {
            if (! $sch->isActive()) {
                continue;
            }
            $dueDate = Carbon::parse($sch->next_due_date, $tz)->startOfDay();
            if ($dueDate->lt($today)) {
                $overdueCount++;
            } elseif ($dueDate->eq($today)) {
                $dueTodayCount++;
            }
        }

        return view('recurring.index', compact(
            'schedules',
            'status',
            'type',
            'memberId',
            'activeCount',
            'pausedCount',
            'archivedCount',
            'expenseCount',
            'transferCount',
            'creditPaymentCount',
            'overdueCount',
            'dueTodayCount',
            'members',
            'accounts',
            'categories',
            'creditSuggestions',
            'tz'
        ));
    }

    public function create(Request $request): View
    {
        $space = app('currentSpace');

        $accounts = Account::where('space_id', $space->id)
            ->active()
            ->expenseSources()
            ->with('creditAccount')
            ->orderBy('name')
            ->get();

        $liquidAccounts = $accounts->filter(fn (Account $acc) => ! $acc->isLiability())->values();
        $billPaymentAccounts = $accounts->filter(fn (Account $acc) => in_array($acc->type, ['bank', 'cash', 'e-wallet'], true))->values();

        $creditAccounts = CreditAccount::where('space_id', $space->id)
            ->active()
            ->with('ledgerAccount')
            ->orderBy('name')
            ->get();

        $categories = Category::where('space_id', $space->id)
            ->expense()
            ->active()
            ->orderBy('name')
            ->get();

        $members = Member::where('space_id', $space->id)
            ->active()
            ->orderBy('name')
            ->get();

        $requestedType = $request->query('type', 'expense');
        $defaultType = in_array($requestedType, ['expense', 'transfer', 'credit_payment'], true) ? $requestedType : 'expense';
        $defaultCreditAccountId = $request->query('credit_account_id');

        return view('recurring.create', compact(
            'accounts',
            'liquidAccounts',
            'billPaymentAccounts',
            'creditAccounts',
            'categories',
            'members',
            'defaultType',
            'defaultCreditAccountId'
        ));
    }

    public function store(StoreRecurringScheduleRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        $schedule = $this->recurringService->createSchedule($space, $request->user(), $request->validated());

        return redirect()->route('recurring.show', $schedule)
            ->with('status', "Jadwal berulang \"{$schedule->name}\" berhasil dibuat.");
    }

    public function show(RecurringSchedule $recurring): View
    {
        Gate::authorize('view', $recurring);

        $space = app('currentSpace');
        $tz = $space->timezone ?? 'Asia/Jakarta';

        $recurring->load([
            'member',
            'category',
            'sourceAccount.creditAccount',
            'destinationAccount',
            'creditAccount.ledgerAccount',
            'occurrences.transaction.entries.account',
            'occurrences.confirmedBy',
            'creator',
        ]);

        if ($recurring->isCreditPayment()) {
            $accounts = Account::where('space_id', $space->id)->active()->billPaymentSources()->orderBy('name')->get();
        } elseif ($recurring->isTransfer()) {
            $accounts = Account::where('space_id', $space->id)->active()->liquidAssets()->orderBy('name')->get();
        } else {
            $accounts = Account::where('space_id', $space->id)->active()->expenseSources()->with('creditAccount')->orderBy('name')->get();
        }

        $categories = Category::where('space_id', $space->id)->expense()->active()->orderBy('name')->get();

        $creditLiability = 0;
        $creditStatementDue = 0;
        $suggestedCreditAmount = 0;
        if ($recurring->isCreditPayment() && $recurring->creditAccount) {
            $creditLiability = (int) $recurring->creditAccount->currentLiability();
            $creditStatementDue = (int) $recurring->creditAccount->statement_due_amount;
            $suggestedCreditAmount = $creditStatementDue > 0 ? $creditStatementDue : $creditLiability;
        }

        return view('recurring.show', compact(
            'recurring',
            'tz',
            'accounts',
            'categories',
            'creditLiability',
            'creditStatementDue',
            'suggestedCreditAmount'
        ));
    }

    public function edit(RecurringSchedule $recurring): View
    {
        Gate::authorize('update', $recurring);

        $space = app('currentSpace');

        $accounts = Account::where('space_id', $space->id)
            ->where(function ($q) use ($recurring) {
                $q->where(function ($sub) {
                    $sub->whereNull('archived_at')
                        ->expenseSources();
                });
                if ($recurring->source_account_id) {
                    $q->orWhere('id', $recurring->source_account_id);
                }
                if ($recurring->destination_account_id) {
                    $q->orWhere('id', $recurring->destination_account_id);
                }
            })
            ->with('creditAccount')
            ->orderBy('name')
            ->get();

        $liquidAccounts = Account::where('space_id', $space->id)
            ->where(function ($q) use ($recurring) {
                $q->where(function ($sub) {
                    $sub->whereNull('archived_at')
                        ->liquidAssets();
                });
                if ($recurring->isTransfer()) {
                    if ($recurring->source_account_id) {
                        $q->orWhere('id', $recurring->source_account_id);
                    }
                    if ($recurring->destination_account_id) {
                        $q->orWhere('id', $recurring->destination_account_id);
                    }
                }
            })
            ->orderBy('name')
            ->get();

        $billPaymentAccounts = Account::where('space_id', $space->id)
            ->where(function ($q) use ($recurring) {
                $q->where(function ($sub) {
                    $sub->whereNull('archived_at')
                        ->billPaymentSources();
                });
                if ($recurring->isCreditPayment() && $recurring->source_account_id) {
                    $q->orWhere('id', $recurring->source_account_id);
                }
            })
            ->orderBy('name')
            ->get();

        $creditAccounts = CreditAccount::where('space_id', $space->id)
            ->where(function ($q) use ($recurring) {
                $q->whereNull('archived_at');
                if ($recurring->credit_account_id) {
                    $q->orWhere('id', $recurring->credit_account_id);
                }
            })
            ->with('ledgerAccount')
            ->orderBy('name')
            ->get();

        $categories = Category::where('space_id', $space->id)
            ->expense()
            ->where(function ($q) use ($recurring) {
                $q->whereNull('archived_at');
                if ($recurring->category_id) {
                    $q->orWhere('id', $recurring->category_id);
                }
            })
            ->orderBy('name')
            ->get();

        $members = Member::where('space_id', $space->id)
            ->where(function ($q) use ($recurring) {
                $q->whereNull('archived_at');
                if ($recurring->member_id) {
                    $q->orWhere('id', $recurring->member_id);
                }
            })
            ->orderBy('name')
            ->get();

        return view('recurring.edit', compact(
            'recurring',
            'accounts',
            'liquidAccounts',
            'billPaymentAccounts',
            'creditAccounts',
            'categories',
            'members'
        ));
    }

    public function update(UpdateRecurringScheduleRequest $request, RecurringSchedule $recurring): RedirectResponse
    {
        Gate::authorize('update', $recurring);

        $schedule = $this->recurringService->updateSchedule($recurring, $request->user(), $request->validated());

        return redirect()->route('recurring.show', $schedule)
            ->with('status', "Jadwal berulang \"{$schedule->name}\" berhasil diperbarui.");
    }

    public function destroy(RecurringSchedule $recurring): RedirectResponse
    {
        Gate::authorize('delete', $recurring);

        if ($recurring->isArchived()) {
            $this->recurringService->unarchiveSchedule($recurring, auth()->user());
            $message = "Jadwal berulang \"{$recurring->name}\" berhasil dipulihkan dari arsip.";
        } else {
            $this->recurringService->archiveSchedule($recurring, auth()->user());
            $message = "Jadwal berulang \"{$recurring->name}\" berhasil diarsipkan.";
        }

        return redirect()->route('recurring.index')->with('status', $message);
    }

    public function confirm(ConfirmOccurrenceRequest $request, RecurringSchedule $recurring): RedirectResponse
    {
        Gate::authorize('update', $recurring);

        $dueDate = $request->validated('due_date');
        $transaction = $this->recurringService->confirmOccurrence(
            $recurring,
            $dueDate,
            $request->user(),
            $request->validated()
        );

        $formattedAmt = 'Rp '.number_format($transaction->totalAmount(), 0, ',', '.');

        return redirect()->back()->with('status', "Pembayaran/transfer jadwal \"{$recurring->name}\" periode {$dueDate} ({$formattedAmt}) berhasil dicatat.");
    }

    public function skip(Request $request, RecurringSchedule $recurring): RedirectResponse
    {
        Gate::authorize('update', $recurring);

        $request->validate([
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $dueDate = $request->input('due_date');
        $this->recurringService->skipOccurrence($recurring, $dueDate, $request->user(), $request->input('notes'));

        return redirect()->back()->with('status', "Jadwal \"{$recurring->name}\" untuk periode {$dueDate} telah dilewati.");
    }
}
