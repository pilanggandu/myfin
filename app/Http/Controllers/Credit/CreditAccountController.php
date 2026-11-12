<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\PayCreditBillRequest;
use App\Http\Requests\Credit\StoreCreditAccountRequest;
use App\Http\Requests\Credit\UpdateCreditAccountRequest;
use App\Models\CreditAccount;
use App\Models\Installment;
use App\Services\ActivityLogService;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CreditAccountController extends Controller
{
    public function __construct(
        protected CreditService $creditService,
        protected ActivityLogService $activityLog
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        Gate::authorize('viewAny', CreditAccount::class);

        $tz = $space->timezone ?? 'Asia/Jakarta';
        $tab = $request->query('tab', 'accounts');
        $selectedAccountFilter = $request->query('account_id');
        $planSearch = $request->query('search');

        $creditAccounts = $space->creditAccounts()
            ->with([
                'installmentPlans.installments',
                'recurringSchedules' => fn ($query) => $this->constrainVisibleRecurringSchedules($query),
            ])
            ->orderBy('archived_at', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $activePlansQuery = $space->installmentPlans()
            ->with(['creditAccount', 'category', 'installments'])
            ->active();

        if ($selectedAccountFilter) {
            $activePlansQuery->where('credit_account_id', $selectedAccountFilter);
        }
        if ($planSearch) {
            $activePlansQuery->where('name', 'like', '%'.$planSearch.'%');
        }

        $activePlans = $activePlansQuery->orderBy('start_date', 'desc')->get();

        $completedPlansQuery = $space->installmentPlans()
            ->with(['creditAccount', 'category', 'installments'])
            ->where('status', '!=', 'active');

        if ($selectedAccountFilter) {
            $completedPlansQuery->where('credit_account_id', $selectedAccountFilter);
        }
        if ($planSearch) {
            $completedPlansQuery->where('name', 'like', '%'.$planSearch.'%');
        }

        $completedPlans = $completedPlansQuery->orderBy('start_date', 'desc')->get();

        $totalCreditLimit = (int) $creditAccounts->whereNull('archived_at')->sum('credit_limit');
        $totalLiability = (int) $space->creditLiabilities();
        $totalAvailableLimit = max(0, $totalCreditLimit - $totalLiability);
        $totalUtilizationRate = $totalCreditLimit > 0 ? round(($totalLiability / $totalCreditLimit) * 100, 1) : 0.0;
        $utilizationStatus = $totalUtilizationRate <= 30.0 ? 'safe' : ($totalUtilizationRate <= 70.0 ? 'moderate' : 'high');

        $categories = $space->categories()->expense()->active()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $cashAccounts = $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet'])->orderBy('name')->get();

        // Overdue and Due Soon Installments
        $today = now()->startOfDay()->toDateString();
        $threeDaysLater = now()->addDays(3)->endOfDay()->toDateString();

        $overdueInstallments = Installment::query()
            ->where('space_id', $space->id)
            ->active()
            ->pending()
            ->where('due_date', '<', $today)
            ->with(['installmentPlan.creditAccount'])
            ->orderBy('due_date', 'asc')
            ->get();

        $dueSoonInstallments = Installment::query()
            ->where('space_id', $space->id)
            ->active()
            ->pending()
            ->whereBetween('due_date', [$today, $threeDaysLater])
            ->with(['installmentPlan.creditAccount'])
            ->orderBy('due_date', 'asc')
            ->get();

        // 6-Month Monthly Installment Projections
        $monthlyProjections = [];
        $currentMonth = now()->startOfMonth();
        for ($m = 0; $m < 6; $m++) {
            $monthObj = $currentMonth->copy()->addMonths($m);
            $monthKey = $monthObj->format('Y-m');
            $monthStart = $monthObj->startOfMonth()->toDateString();
            $monthEnd = $monthObj->endOfMonth()->toDateString();

            $monthSum = (int) Installment::query()
                ->where('space_id', $space->id)
                ->active()
                ->pending()
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $monthlyProjections[] = [
                'month_name' => $monthObj->translatedFormat('F Y'),
                'month_short' => $monthObj->translatedFormat('M y'),
                'total_amount' => $monthSum,
            ];
        }

        return view('credit.index', compact(
            'creditAccounts',
            'activePlans',
            'completedPlans',
            'totalCreditLimit',
            'totalLiability',
            'totalAvailableLimit',
            'totalUtilizationRate',
            'utilizationStatus',
            'categories',
            'members',
            'cashAccounts',
            'overdueInstallments',
            'dueSoonInstallments',
            'monthlyProjections',
            'selectedAccountFilter',
            'planSearch',
            'tab',
            'tz'
        ));
    }

    public function statement(Request $request, CreditAccount $creditAccount): View
    {
        Gate::authorize('view', $creditAccount);
        $space = app('currentSpace');
        $tz = $space->timezone ?? 'Asia/Jakarta';

        $month = $request->query('month', now()->format('Y-m'));
        $statement = $creditAccount->getStatementBill($month);

        $recurringSchedules = $this->constrainVisibleRecurringSchedules(
            $creditAccount->recurringSchedules()
        )->get();

        $paymentSchedules = $recurringSchedules->where('type', 'credit_payment')->values();
        $expenseSchedules = $recurringSchedules->where('type', 'expense')->values();

        $cashAccounts = $space->accounts()
            ->whereNull('archived_at')
            ->whereIn('type', ['bank', 'cash', 'e-wallet'])
            ->orderBy('name')
            ->get();

        $availableMonths = [];
        $currentMonth = now()->startOfMonth();
        for ($i = -6; $i <= 5; $i++) {
            $m = $currentMonth->copy()->addMonths($i);
            $availableMonths[] = [
                'key' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
                'is_current' => $m->format('Y-m') === now()->format('Y-m'),
            ];
        }

        return view('credit.statement', compact(
            'creditAccount',
            'statement',
            'month',
            'cashAccounts',
            'availableMonths',
            'paymentSchedules',
            'expenseSchedules',
            'tz'
        ));
    }

    public function payBill(PayCreditBillRequest $request, CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('update', $creditAccount);
        $space = app('currentSpace');

        $payment = $this->creditService->payCreditAccountBill($space, auth()->user(), $creditAccount, $request->validated());

        if ($request->input('redirect_to') === 'statement') {
            return redirect()->route('credit.accounts.statement', [
                'creditAccount' => $creditAccount,
                'month' => $request->input('month', now()->format('Y-m')),
            ])->with('status', 'Pembayaran lembar tagihan kartu '.$creditAccount->name.' sebesar Rp '.number_format($payment->total_amount, 0, ',', '.').' berhasil diposting.');
        }

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Pembayaran tagihan kartu '.$creditAccount->name.' sebesar Rp '.number_format($payment->total_amount, 0, ',', '.').' berhasil diposting.');
    }

    public function store(StoreCreditAccountRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        Gate::authorize('create', CreditAccount::class);

        $this->creditService->createCreditAccount($space, $request->validated());

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Akun kredit atau kartu berhasil ditambahkan.');
    }

    public function update(UpdateCreditAccountRequest $request, CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('update', $creditAccount);

        $this->creditService->updateCreditAccount($creditAccount, $request->validated());

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Data akun kredit berhasil diperbarui.');
    }

    public function destroy(CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('delete', $creditAccount);

        $creditAccount->archive();

        $this->activityLog->log(
            module: 'credit',
            action: 'archived',
            description: "Mengarsipkan akun kredit \"{$creditAccount->name}\"",
            space: app('currentSpace'),
            subject: $creditAccount,
            subjectLabel: $creditAccount->name,
        );

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Akun kredit berhasil diarsipkan.');
    }

    public function unarchive(CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('update', $creditAccount);

        $creditAccount->unarchive();

        $this->activityLog->log(
            module: 'credit',
            action: 'unarchived',
            description: "Mengaktifkan kembali akun kredit \"{$creditAccount->name}\"",
            space: app('currentSpace'),
            subject: $creditAccount,
            subjectLabel: $creditAccount->name,
        );

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Akun kredit berhasil diaktifkan kembali.');
    }

    private function constrainVisibleRecurringSchedules(mixed $query): mixed
    {
        return $query->activeOrPaused()
            ->with(['sourceAccount', 'category'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('next_due_date', 'asc');
    }
}
