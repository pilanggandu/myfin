<?php

namespace App\Http\Controllers\Recurring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recurring\ConfirmOccurrenceRequest;
use App\Http\Requests\Recurring\StoreRecurringScheduleRequest;
use App\Http\Requests\Recurring\UpdateRecurringScheduleRequest;
use App\Models\Account;
use App\Models\Category;
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
            ->with(['member', 'category', 'sourceAccount', 'destinationAccount']);

        if ($status === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }

        if ($type === 'expense' || $type === 'transfer') {
            $query->where('type', $type);
        }

        if ($memberId) {
            $query->where('member_id', $memberId);
        }

        $schedules = $query->orderBy('next_due_date', 'asc')->get();

        $activeCount = RecurringSchedule::where('space_id', $space->id)->active()->count();
        $archivedCount = RecurringSchedule::where('space_id', $space->id)->archived()->count();
        $expenseCount = RecurringSchedule::where('space_id', $space->id)->active()->where('type', 'expense')->count();
        $transferCount = RecurringSchedule::where('space_id', $space->id)->active()->where('type', 'transfer')->count();

        $members = Member::where('space_id', $space->id)->active()->orderBy('name')->get();
        $accounts = Account::where('space_id', $space->id)->active()->orderBy('name')->get();
        $categories = Category::where('space_id', $space->id)->expense()->active()->orderBy('name')->get();

        $today = Carbon::now($tz)->startOfDay();
        $overdueCount = 0;
        $dueTodayCount = 0;

        foreach ($schedules as $sch) {
            if ($sch->isArchived()) {
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
            'archivedCount',
            'expenseCount',
            'transferCount',
            'overdueCount',
            'dueTodayCount',
            'members',
            'accounts',
            'categories',
            'tz'
        ));
    }

    public function create(Request $request): View
    {
        $space = app('currentSpace');

        $accounts = Account::where('space_id', $space->id)
            ->active()
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

        $defaultType = $request->query('type', 'expense');

        return view('recurring.create', compact('accounts', 'categories', 'members', 'defaultType'));
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
            'sourceAccount',
            'destinationAccount',
            'occurrences.transaction.entries.account',
            'occurrences.confirmedBy',
            'creator',
        ]);

        $accounts = Account::where('space_id', $space->id)->active()->orderBy('name')->get();
        $categories = Category::where('space_id', $space->id)->expense()->active()->orderBy('name')->get();

        return view('recurring.show', compact('recurring', 'tz', 'accounts', 'categories'));
    }

    public function edit(RecurringSchedule $recurring): View
    {
        Gate::authorize('update', $recurring);

        $space = app('currentSpace');

        $accounts = Account::where('space_id', $space->id)
            ->active()
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

        return view('recurring.edit', compact('recurring', 'accounts', 'categories', 'members'));
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
