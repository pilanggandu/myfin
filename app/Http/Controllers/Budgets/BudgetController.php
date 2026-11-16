<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Models\Budget;
use App\Services\ActivityLogService;
use App\Services\BudgetRolloverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly BudgetRolloverService $rolloverService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Budget::class);

        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);

        if ($month < 1 || $month > 12) {
            $month = (int) $now->month;
        }

        $enriched = $space
            ? $this->rolloverService->getEnrichedBudgetsForPeriod($space, $year, $month)
            : [
                'budgets' => collect(),
                'summary' => [
                    'total_base_budget' => 0,
                    'total_rollover' => 0,
                    'total_effective_budget' => 0,
                    'total_spent' => 0,
                    'total_remaining' => 0,
                    'overall_percentage' => 0.0,
                    'over_budget_count' => 0,
                    'rollover_active_count' => 0,
                ],
            ];

        $budgets = $enriched['budgets'];
        $summary = $enriched['summary'];

        $budgetCategoryIds = $budgets->pluck('category_id')->toArray();
        $availableCategories = $space ? $space->activeCategories()
            ->expense()
            ->whereNotIn('id', $budgetCategoryIds)
            ->orderBy('name')
            ->get() : collect();

        $totalBudget = $summary['total_effective_budget'];
        $totalBaseBudget = $summary['total_base_budget'];
        $totalRollover = $summary['total_rollover'];
        $totalSpent = $summary['total_spent'];
        $totalRemaining = $summary['total_remaining'];
        $overallPercentage = $summary['overall_percentage'];
        $overBudgetCount = $summary['over_budget_count'];
        $rolloverActiveCount = $summary['rollover_active_count'];

        $periodDate = Carbon::createFromDate($year, $month, 1);

        return view('budgets.index', compact(
            'space',
            'budgets',
            'year',
            'month',
            'periodDate',
            'totalBudget',
            'totalBaseBudget',
            'totalRollover',
            'totalSpent',
            'totalRemaining',
            'overallPercentage',
            'overBudgetCount',
            'rolloverActiveCount',
            'availableCategories'
        ));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        Gate::authorize('create', Budget::class);

        $space = app('currentSpace');
        $validated = $request->validated();

        $budget = $space->budgets()->create($validated);
        $budget->load('category');
        $categoryName = $budget->category?->name ?? 'Kategori';
        $periodLabel = sprintf('%02d/%d', $budget->month, $budget->year);

        $this->activityLog->log(
            module: 'budget',
            action: 'created',
            description: "Menetapkan anggaran '{$categoryName}' periode {$periodLabel} sebesar ".$this->activityLog->formatCurrency($budget->limit_amount),
            space: $space,
            actor: $request->user(),
            subject: $budget,
            subjectLabel: "{$categoryName} ({$periodLabel})",
            properties: [
                'category' => $categoryName,
                'year' => $budget->year,
                'month' => $budget->month,
                'limit_amount' => (int) $budget->limit_amount,
            ],
        );

        return redirect()->route('budgets.index', [
            'year' => $validated['year'],
            'month' => $validated['month'],
        ])->with('success', 'Anggaran kategori berhasil ditetapkan.');
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        Gate::authorize('update', $budget);

        $validated = $request->validated();
        $oldLimit = (int) $budget->limit_amount;
        $budget->update([
            'limit_amount' => $validated['limit_amount'],
        ]);

        $categoryName = $budget->category?->name ?? 'Kategori';
        $periodLabel = sprintf('%02d/%d', $budget->month, $budget->year);

        $this->activityLog->log(
            module: 'budget',
            action: 'updated',
            description: "Memperbarui limit anggaran '{$categoryName}' ({$periodLabel}) menjadi ".$this->activityLog->formatCurrency($budget->limit_amount),
            space: $budget->space,
            actor: $request->user(),
            subject: $budget,
            subjectLabel: "{$categoryName} ({$periodLabel})",
            properties: $this->activityLog->buildDiff(
                ['limit_amount' => $oldLimit],
                ['limit_amount' => (int) $budget->limit_amount],
                ['limit_amount' => 'Limit Anggaran']
            ),
        );

        return redirect()->route('budgets.index', [
            'year' => $budget->year,
            'month' => $budget->month,
        ])->with('success', "Limit anggaran '{$budget->category->name}' berhasil diperbarui.");
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        Gate::authorize('delete', $budget);

        $year = $budget->year;
        $month = $budget->month;
        $categoryName = $budget->category->name;
        $space = $budget->space;
        $periodLabel = sprintf('%02d/%d', $month, $year);
        $limitAmount = (int) $budget->limit_amount;

        $budget->delete();

        $this->activityLog->log(
            module: 'budget',
            action: 'deleted',
            description: "Menghapus anggaran '{$categoryName}' periode {$periodLabel}",
            space: $space,
            subjectLabel: "{$categoryName} ({$periodLabel})",
            properties: ['limit_amount' => $limitAmount],
        );

        return redirect()->route('budgets.index', [
            'year' => $year,
            'month' => $month,
        ])->with('success', "Anggaran '{$categoryName}' berhasil dihapus.");
    }
}
