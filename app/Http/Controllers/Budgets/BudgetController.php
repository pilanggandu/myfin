<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Models\Budget;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog
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

        $budgets = $space ? $space->budgets()
            ->where('year', $year)
            ->where('month', $month)
            ->with('category')
            ->get() : collect();

        $budgetCategoryIds = $budgets->pluck('category_id')->toArray();
        $availableCategories = $space ? $space->activeCategories()
            ->expense()
            ->whereNotIn('id', $budgetCategoryIds)
            ->orderBy('name')
            ->get() : collect();

        $totalBudget = 0;
        $totalSpent = 0;
        $overBudgetCount = 0;

        foreach ($budgets as $budget) {
            $spent = $budget->spentAmount();
            $budget->calculated_spent = $spent;
            $budget->calculated_remaining = $budget->limit_amount - $spent;
            $budget->calculated_percentage = $budget->limit_amount > 0 ? round(($spent / $budget->limit_amount) * 100, 1) : 0;
            $budget->calculated_is_over = $spent > $budget->limit_amount;

            $totalBudget += $budget->limit_amount;
            $totalSpent += $spent;
            if ($budget->calculated_is_over) {
                $overBudgetCount++;
            }
        }

        $totalRemaining = $totalBudget - $totalSpent;
        $overallPercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0;

        $periodDate = Carbon::createFromDate($year, $month, 1);

        return view('budgets.index', compact(
            'space',
            'budgets',
            'year',
            'month',
            'periodDate',
            'totalBudget',
            'totalSpent',
            'totalRemaining',
            'overallPercentage',
            'overBudgetCount',
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
