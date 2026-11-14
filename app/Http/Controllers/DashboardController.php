<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\ShortPlan;
use App\Services\CashflowProjectionService;
use App\Services\RecurringService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CashflowProjectionService $cashflowProjectionService,
        protected RecurringService $recurringService
    ) {}

    public function index(Request $request): View
    {
        $currentSpace = app('currentSpace');
        $now = $currentSpace ? $currentSpace->now() : Carbon::now('Asia/Jakarta');
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $accounts = $currentSpace ? $currentSpace->activeAccounts()->with('creditAccount')->get() : collect();
        $totalBalance = $currentSpace ? $currentSpace->totalBalance() : 0;

        $recentTransactions = $currentSpace ? $currentSpace->financialTransactions()
            ->with(['category', 'entries.account', 'member', 'installmentPlan'])
            ->recent($now->toDateString())
            ->take(7)
            ->get() : collect();

        $monthlyIncome = 0;
        $monthlyExpense = 0;

        if ($currentSpace) {
            $monthlyIncome = (int) $currentSpace->financialTransactions()
                ->posted()
                ->income()
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->with('entries')
                ->get()
                ->sum(fn ($t) => $t->totalAmount());

            $directExpense = (int) $currentSpace->financialTransactions()
                ->posted()
                ->expense()
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->with('entries')
                ->get()
                ->sum(fn ($t) => $t->totalAmount());

            $transferFees = (int) $currentSpace->financialTransactions()
                ->posted()
                ->transfer()
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('fee_amount');

            $monthlyExpense = $directExpense + $transferFees;
        }

        $netCashFlow = $monthlyIncome - $monthlyExpense;

        $currentBudgets = $currentSpace ? $currentSpace->budgets()
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->with('category')
            ->get() : collect();

        $totalBudgetLimit = 0;
        $totalBudgetSpent = 0;
        $overBudgetCount = 0;

        foreach ($currentBudgets as $b) {
            $spent = $b->spentAmount();
            $b->calculated_spent = $spent;
            $b->calculated_percentage = $b->limit_amount > 0 ? round(($spent / $b->limit_amount) * 100, 1) : 0;
            $b->calculated_is_over = $spent > $b->limit_amount;

            $totalBudgetLimit += $b->limit_amount;
            $totalBudgetSpent += $spent;
            if ($b->calculated_is_over) {
                $overBudgetCount++;
            }
        }

        $budgetRemaining = $totalBudgetLimit - $totalBudgetSpent;
        $unbudgetedExpense = max(0, $monthlyExpense - $totalBudgetSpent);

        $recurringSummary = $currentSpace
            ? app(RecurringService::class)->getDashboardSummary($currentSpace)
            : ['total_due_count' => 0, 'overdue_count' => 0, 'due_today_count' => 0, 'upcoming_count' => 0, 'items' => []];

        $creditAccounts = $currentSpace
            ? $currentSpace->creditAccounts()->active()->with(['installmentPlans.installments'])->get()
            : collect();

        $totalCreditLimit = (int) $creditAccounts->sum('credit_limit');
        $totalCreditLiability = $currentSpace ? (int) $currentSpace->creditLiabilities() : 0;
        $totalAvailableCredit = max(0, $totalCreditLimit - $totalCreditLiability);

        $upcomingInstallments = $currentSpace
            ? Installment::query()
                ->where('space_id', $currentSpace->id)
                ->active()
                ->pending()
                ->with(['installmentPlan.creditAccount'])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get()
            : collect();

        $activeGoals = $currentSpace
            ? $currentSpace->goals()
                ->active()
                ->with('account')
                ->orderByRaw('target_date IS NULL, target_date ASC')
                ->take(3)
                ->get()
            : collect();

        $allPlannedShortPlans = $currentSpace
            ? $currentSpace->shortPlans()
                ->planned()
                ->with(['account', 'category', 'items'])
                ->orderByRaw('CASE WHEN target_date IS NULL THEN 1 ELSE 0 END ASC, target_date ASC')
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END ASC")
                ->orderByDesc('id')
                ->get()
            : collect();

        $shortPlanActiveCount = $allPlannedShortPlans->count();
        $shortPlanActiveTotal = round((float) $allPlannedShortPlans->sum('planned_amount'), 2);
        $shortPlanUrgentCount = $allPlannedShortPlans->filter(function (ShortPlan $plan) use ($now): bool {
            return $plan->priority === 'high' || in_array($plan->dueStatus($now), ['overdue', 'due_today', 'soon'], true);
        })->count();
        $activeShortPlans = $allPlannedShortPlans->take(4);

        $timelineProjection = $currentSpace
            ? $this->cashflowProjectionService->project($currentSpace, [
                'horizon_days' => 30,
                'include_recurring' => true,
                'include_installments' => true,
                'include_planned_incomes' => true,
                'include_short_plans' => true,
            ])
            : null;
        $timelineEvents = $timelineProjection['timeline_events'] ?? [];

        return view('dashboard', [
            'space' => $currentSpace,
            'user' => $request->user(),
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'recentTransactions' => $recentTransactions,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'netCashFlow' => $netCashFlow,
            'currentBudgets' => $currentBudgets,
            'totalBudgetLimit' => $totalBudgetLimit,
            'totalBudgetSpent' => $totalBudgetSpent,
            'unbudgetedExpense' => $unbudgetedExpense,
            'budgetRemaining' => $budgetRemaining,
            'overBudgetCount' => $overBudgetCount,
            'recurringSummary' => $recurringSummary,
            'creditAccounts' => $creditAccounts,
            'totalCreditLimit' => $totalCreditLimit,
            'totalCreditLiability' => $totalCreditLiability,
            'totalAvailableCredit' => $totalAvailableCredit,
            'upcomingInstallments' => $upcomingInstallments,
            'activeGoals' => $activeGoals,
            'activeShortPlans' => $activeShortPlans,
            'shortPlanActiveCount' => $shortPlanActiveCount,
            'shortPlanActiveTotal' => $shortPlanActiveTotal,
            'shortPlanUrgentCount' => $shortPlanUrgentCount,
            'timelineProjection' => $timelineProjection,
            'timelineEvents' => $timelineEvents,
            'now' => $now,
        ]);
    }
}
