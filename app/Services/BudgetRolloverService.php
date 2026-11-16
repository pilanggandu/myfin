<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Space;
use Illuminate\Support\Collection;

class BudgetRolloverService
{
    /**
     * Cache internal untuk mencegah kalkulasi berulang selama satu siklus request.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $calculationCache = [];

    /**
     * Hitung sisa atau kelebihan anggaran dari periode sebelumnya yang bergulir ke periode target.
     *
     * @return array{
     *     has_rollover: bool,
     *     rollover_amount: int,
     *     previous_year: int,
     *     previous_month: int,
     *     previous_limit: int,
     *     previous_spent: int,
     *     previous_variance: int,
     *     rollover_mode: string,
     *     rollover_cap: int|null
     * }
     */
    public function calculateRollover(
        Space $space,
        Category $category,
        int $year,
        int $month,
        int $depth = 0
    ): array {
        // Pengaman rekursi bertingkat (maksimal 24 bulan ke belakang)
        if ($depth >= 24 || ! $category->isRolloverEnabled()) {
            return $this->emptyRolloverResult($year, $month, $category);
        }

        $cacheKey = "{$space->id}_{$category->id}_{$year}_{$month}";
        if (isset($this->calculationCache[$cacheKey])) {
            return $this->calculationCache[$cacheKey];
        }

        // Tentukan periode bulan sebelumnya
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear = $month === 1 ? $year - 1 : $year;

        // Cari anggaran kategori pada bulan sebelumnya
        /** @var Budget|null $prevBudget */
        $prevBudget = Budget::query()
            ->where('space_id', $space->id)
            ->where('category_id', $category->id)
            ->where('year', $prevYear)
            ->where('month', $prevMonth)
            ->first();

        if (! $prevBudget) {
            $res = $this->emptyRolloverResult($year, $month, $category);
            $this->calculationCache[$cacheKey] = $res;

            return $res;
        }

        // Hitung bergulir dari bulan sebelum-sebelumnya secara berantai (chained rollover)
        $prevRollover = $this->calculateRollover($space, $category, $prevYear, $prevMonth, $depth + 1);

        $prevBaseLimit = (int) $prevBudget->limit_amount;
        $prevEffectiveLimit = (int) ($prevBaseLimit + $prevRollover['rollover_amount']);
        $prevSpent = (int) $prevBudget->spentAmount();
        $prevVariance = $prevEffectiveLimit - $prevSpent;

        $rolloverAmount = 0;
        $mode = $category->rollover_mode ?: 'all';

        if ($prevVariance > 0) {
            // Surplus / Sisa hemat
            if (in_array($mode, ['all', 'surplus_only'], true)) {
                $cap = $category->rollover_cap ? (int) $category->rollover_cap : null;
                $rolloverAmount = $cap !== null ? min($prevVariance, $cap) : $prevVariance;
            }
        } elseif ($prevVariance < 0) {
            // Defisit / Kelebihan pengeluaran
            if (in_array($mode, ['all', 'deficit_only'], true)) {
                $rolloverAmount = $prevVariance; // Nilai negatif
            }
        }

        $result = [
            'has_rollover' => $rolloverAmount !== 0,
            'rollover_amount' => (int) $rolloverAmount,
            'previous_year' => $prevYear,
            'previous_month' => $prevMonth,
            'previous_limit' => $prevBaseLimit,
            'previous_spent' => $prevSpent,
            'previous_variance' => $prevVariance,
            'rollover_mode' => $mode,
            'rollover_cap' => $category->rollover_cap ? (int) $category->rollover_cap : null,
        ];

        $this->calculationCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Memperkaya satu model Budget dengan data bergulir, realisasi, dan batas efektif.
     */
    public function enrichBudget(Budget $budget): Budget
    {
        $category = $budget->category;
        $space = $budget->space;

        $rolloverInfo = ($category && $space)
            ? $this->calculateRollover($space, $category, $budget->year, $budget->month)
            : $this->emptyRolloverResult($budget->year, $budget->month, $category);

        $spent = $budget->spentAmount();
        $rolloverAmount = $rolloverInfo['rollover_amount'];

        // Batas efektif anggaran bulan ini (tidak boleh bernilai negatif secara fisik limit)
        $effectiveLimit = max(0, (int) ($budget->limit_amount + $rolloverAmount));

        $budget->calculated_spent = $spent;
        $budget->rollover_amount = $rolloverAmount;
        $budget->has_rollover = $rolloverInfo['has_rollover'];
        $budget->rollover_info = $rolloverInfo;
        $budget->effective_limit_amount = $effectiveLimit;
        $budget->calculated_remaining = $effectiveLimit - $spent;
        $budget->calculated_percentage = $effectiveLimit > 0
            ? round(($spent / $effectiveLimit) * 100, 1)
            : ($spent > 0 ? 100.0 : 0.0);
        $budget->calculated_is_over = $spent > $effectiveLimit;

        return $budget;
    }

    /**
     * Mengambil daftar anggaran untuk satu periode bulan dan tahun, lengkap dengan kalkulasi bergulir.
     *
     * @return array{
     *     budgets: Collection<int, Budget>,
     *     summary: array{
     *         total_base_budget: int,
     *         total_rollover: int,
     *         total_effective_budget: int,
     *         total_spent: int,
     *         total_remaining: int,
     *         overall_percentage: float,
     *         over_budget_count: int,
     *         rollover_active_count: int
     *     }
     * }
     */
    public function getEnrichedBudgetsForPeriod(Space $space, int $year, int $month): array
    {
        $budgets = $space->budgets()
            ->where('year', $year)
            ->where('month', $month)
            ->with('category')
            ->get();

        $totalBase = 0;
        $totalRollover = 0;
        $totalEffective = 0;
        $totalSpent = 0;
        $overCount = 0;
        $rolloverActiveCount = 0;

        foreach ($budgets as $budget) {
            $this->enrichBudget($budget);

            $totalBase += (int) $budget->limit_amount;
            $totalRollover += (int) $budget->rollover_amount;
            $totalEffective += (int) $budget->effective_limit_amount;
            $totalSpent += (int) $budget->calculated_spent;

            if ($budget->calculated_is_over) {
                $overCount++;
            }

            if ($budget->has_rollover || ($budget->category && $budget->category->isRolloverEnabled())) {
                $rolloverActiveCount++;
            }
        }

        $totalRemaining = $totalEffective - $totalSpent;
        $overallPercentage = $totalEffective > 0
            ? round(($totalSpent / $totalEffective) * 100, 1)
            : ($totalSpent > 0 ? 100.0 : 0.0);

        return [
            'budgets' => $budgets,
            'summary' => [
                'total_base_budget' => $totalBase,
                'total_rollover' => $totalRollover,
                'total_effective_budget' => $totalEffective,
                'total_spent' => $totalSpent,
                'total_remaining' => $totalRemaining,
                'overall_percentage' => $overallPercentage,
                'over_budget_count' => $overCount,
                'rollover_active_count' => $rolloverActiveCount,
            ],
        ];
    }

    /**
     * Payload kosong jika rollover tidak aktif atau periode tidak valid.
     *
     * @return array<string, mixed>
     */
    protected function emptyRolloverResult(int $year, int $month, ?Category $category = null): array
    {
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear = $month === 1 ? $year - 1 : $year;

        return [
            'has_rollover' => false,
            'rollover_amount' => 0,
            'previous_year' => $prevYear,
            'previous_month' => $prevMonth,
            'previous_limit' => 0,
            'previous_spent' => 0,
            'previous_variance' => 0,
            'rollover_mode' => $category?->rollover_mode ?: 'all',
            'rollover_cap' => $category?->rollover_cap ? (int) $category->rollover_cap : null,
        ];
    }
}
