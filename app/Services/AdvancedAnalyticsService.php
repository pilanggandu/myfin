<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\RecurringSchedule;
use App\Models\ShortPlan;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdvancedAnalyticsService
{
    /**
     * Bangun seluruh metrik analisis keuangan lanjutan berbasis data riil ruang aktif.
     *
     * @return array<string, mixed>
     */
    public function build(
        ?Space $space,
        string $startDate,
        string $endDate,
        int $year,
        int $month,
        string $periodType = 'month',
        int $horizonMonths = 6,
        ?int $accountId = null
    ): array {
        if (! $space) {
            return $this->emptyPayload($startDate, $endDate, $horizonMonths);
        }

        $timezone = $space->timezone ?: 'Asia/Jakarta';
        $now = $space->now();
        $horizonMonths = in_array($horizonMonths, [6, 12], true) ? $horizonMonths : 6;

        $startCarbon = Carbon::parse($startDate, $timezone)->startOfDay();
        $endCarbon = Carbon::parse($endDate, $timezone)->endOfDay();

        // Hitung periode pembanding (bulan sebelumnya atau rentang durasi yang sama persis sebelumnya)
        if ($periodType === 'month') {
            $prevStart = $startCarbon->copy()->subMonthNoOverflow()->startOfMonth();
            $prevEnd = $prevStart->copy()->endOfMonth();
            $prevLabel = $prevStart->translatedFormat('F Y');
        } else {
            $spanDays = (int) $startCarbon->diffInDays($endCarbon) + 1;
            $prevEnd = $startCarbon->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($spanDays - 1)->startOfDay();
            $prevLabel = $prevStart->format('d M Y').' - '.$prevEnd->format('d M Y');
        }

        $currentStats = $this->summarizePeriod($space->id, $startCarbon->toDateString(), $endCarbon->toDateString(), $accountId);
        $previousStats = $this->summarizePeriod($space->id, $prevStart->toDateString(), $prevEnd->toDateString(), $accountId);

        $balanceSheet = $this->buildBalanceSheet($space);
        $trendData = $this->buildMultiMonthTrend($space, $year, $month, $periodType, $horizonMonths, $accountId);

        // Hitung rata-rata pengeluaran bulanan 3 bulan terakhir yang aktif untuk estimasi runway likuiditas
        $recentExpenses = collect($trendData['months'])
            ->slice(-3)
            ->pluck('expense')
            ->filter(fn (int $val): bool => $val > 0);
        $baselineMonthlyExpense = $recentExpenses->isNotEmpty()
            ? (int) round($recentExpenses->avg())
            : $currentStats['expense'];

        $runwayMonths = $baselineMonthlyExpense > 0
            ? round($balanceSheet['liquid_cash'] / $baselineMonthlyExpense, 1)
            : null;

        // Hitung burn rate harian & proyeksi akhir periode
        $totalDaysInPeriod = max(1, (int) $startCarbon->copy()->startOfDay()->diffInDays($endCarbon->copy()->startOfDay()) + 1);
        $today = $now->copy()->startOfDay();
        if ($today->lt($startCarbon->copy()->startOfDay())) {
            $elapsedDays = 0;
        } elseif ($today->gt($endCarbon->copy()->startOfDay())) {
            $elapsedDays = $totalDaysInPeriod;
        } else {
            $elapsedDays = (int) $startCarbon->copy()->startOfDay()->diffInDays($today) + 1;
        }

        $dailyBurnRate = $elapsedDays > 0 ? (int) round($currentStats['expense'] / $elapsedDays) : 0;
        $projectedPeriodExpense = ($elapsedDays > 0 && $elapsedDays < $totalDaysInPeriod)
            ? (int) round($dailyBurnRate * $totalDaysInPeriod)
            : $currentStats['expense'];

        $periodComparison = [
            'prev_label' => $prevLabel,
            'current' => $currentStats,
            'previous' => $previousStats,
            'income_delta' => $currentStats['income'] - $previousStats['income'],
            'income_delta_pct' => $this->percentChange($previousStats['income'], $currentStats['income']),
            'expense_delta' => $currentStats['expense'] - $previousStats['expense'],
            'expense_delta_pct' => $this->percentChange($previousStats['expense'], $currentStats['expense']),
            'net_delta' => $currentStats['net'] - $previousStats['net'],
        ];

        $categoryCurrent = $this->expenseByCategory($space->id, $startCarbon->toDateString(), $endCarbon->toDateString(), $accountId);
        $categoryPrevious = $this->expenseByCategory($space->id, $prevStart->toDateString(), $prevEnd->toDateString(), $accountId);
        $categoryAnalysis = $this->buildCategoryMovers($categoryCurrent, $categoryPrevious, $currentStats['expense']);

        $budgetAnalysis = $periodType === 'month'
            ? $this->buildBudgetVariance($space, $year, $month, $categoryCurrent, $elapsedDays, $totalDaysInPeriod)
            : ['has_budgets' => false, 'items' => collect(), 'total_limit' => 0, 'total_spent' => 0, 'over_count' => 0, 'unbudgeted' => collect()];

        $dailyPattern = $this->buildDailyAndWeekdayPatterns(
            $space->id,
            $startCarbon,
            $endCarbon,
            $prevStart,
            $prevEnd,
            $budgetAnalysis['total_limit'],
            $accountId
        );

        $spendingStructure = $this->buildSpendingStructure(
            $space->id,
            $startCarbon->toDateString(),
            $endCarbon->toDateString(),
            $currentStats['expense'],
            $accountId
        );

        $commitmentsForecast = $this->buildUpcomingCommitments($space, $now);
        $memberBreakdown = $this->buildMemberBreakdown($space->id, $startCarbon->toDateString(), $endCarbon->toDateString(), $accountId);
        $topExpenses = $this->buildTopExpenses($space->id, $startCarbon->toDateString(), $endCarbon->toDateString(), $accountId);

        $healthSignals = $this->evaluateHealthSignals(
            $currentStats['savings_rate'],
            $runwayMonths,
            $balanceSheet['credit_utilization'],
            $budgetAnalysis['over_count'],
            $budgetAnalysis['items']->count(),
            $currentStats['income'],
            $currentStats['expense']
        );

        $insights = $this->generateInsights(
            $periodComparison,
            $categoryAnalysis,
            $dailyPattern,
            $budgetAnalysis,
            $balanceSheet,
            $runwayMonths,
            $commitmentsForecast
        );

        return [
            'horizonMonths' => $horizonMonths,
            'periodComparison' => $periodComparison,
            'balanceSheet' => $balanceSheet,
            'runwayMonths' => $runwayMonths,
            'baselineMonthlyExpense' => $baselineMonthlyExpense,
            'dailyBurnRate' => $dailyBurnRate,
            'projectedPeriodExpense' => $projectedPeriodExpense,
            'elapsedDays' => $elapsedDays,
            'totalDaysInPeriod' => $totalDaysInPeriod,
            'healthSignals' => $healthSignals,
            'trendData' => $trendData,
            'dailyPattern' => $dailyPattern,
            'categoryAnalysis' => $categoryAnalysis,
            'budgetAnalysis' => $budgetAnalysis,
            'spendingStructure' => $spendingStructure,
            'commitmentsForecast' => $commitmentsForecast,
            'memberBreakdown' => $memberBreakdown,
            'topExpenses' => $topExpenses,
            'insights' => $insights,
        ];
    }

    /**
     * Ringkas pemasukan, pengeluaran, net, dan savings rate dalam rentang tanggal.
     *
     * @return array{income: int, expense: int, net: int, savings_rate: ?float, expense_ratio: ?float, tx_count: int}
     */
    private function summarizePeriod(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): array
    {
        if ($accountId !== null) {
            $rows = DB::table('transaction_entries')
                ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
                ->where('financial_transactions.space_id', $spaceId)
                ->where('financial_transactions.status', 'posted')
                ->where('transaction_entries.account_id', $accountId)
                ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
                ->select('financial_transactions.type', 'transaction_entries.amount_signed')
                ->get();

            $income = 0;
            $expense = 0;
            foreach ($rows as $row) {
                $signed = (int) round((float) $row->amount_signed);
                if ($row->type === 'income') {
                    $income += max(0, $signed);
                } elseif ($row->type === 'expense') {
                    $expense += abs($signed);
                }
            }

            $txCount = $rows->count();
        } else {
            $income = (int) DB::table('financial_transactions')
                ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
                ->where('financial_transactions.space_id', $spaceId)
                ->where('financial_transactions.status', 'posted')
                ->where('financial_transactions.type', 'income')
                ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
                ->sum('transaction_entries.amount_signed');

            $directExpense = (int) DB::table('financial_transactions')
                ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
                ->where('financial_transactions.space_id', $spaceId)
                ->where('financial_transactions.status', 'posted')
                ->where('financial_transactions.type', 'expense')
                ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
                ->sum(DB::raw('ABS(transaction_entries.amount_signed)'));

            $transferFees = (int) DB::table('financial_transactions')
                ->where('space_id', $spaceId)
                ->where('status', 'posted')
                ->where('type', 'transfer')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('fee_amount');

            $expense = $directExpense + $transferFees;

            $txCount = (int) DB::table('financial_transactions')
                ->where('space_id', $spaceId)
                ->where('status', 'posted')
                ->whereIn('type', ['income', 'expense'])
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->count();
        }

        $net = $income - $expense;
        $savingsRate = $income > 0 ? round(($net / $income) * 100, 1) : null;
        $expenseRatio = $income > 0 ? round(($expense / $income) * 100, 1) : null;

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $net,
            'savings_rate' => $savingsRate,
            'expense_ratio' => $expenseRatio,
            'tx_count' => $txCount,
        ];
    }

    /**
     * Hitung neraca kekayaan bersih ruang aktif saat ini.
     *
     * @return array<string, mixed>
     */
    private function buildBalanceSheet(Space $space): array
    {
        $accounts = $space->activeAccounts()->get();
        $entrySums = DB::table('transaction_entries')
            ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
            ->where('financial_transactions.space_id', $space->id)
            ->where('financial_transactions.status', 'posted')
            ->groupBy('transaction_entries.account_id')
            ->select('transaction_entries.account_id', DB::raw('SUM(transaction_entries.amount_signed) as total_signed'))
            ->pluck('total_signed', 'account_id');

        $liquidCash = 0;
        $investmentAssets = 0;
        $accountComposition = [];

        foreach ($accounts as $acc) {
            if (! in_array($acc->type, ['bank', 'e-wallet', 'cash', 'investment'], true)) {
                continue;
            }
            $bal = (int) round((float) $acc->opening_balance + (float) ($entrySums[$acc->id] ?? 0));
            if ($acc->type === 'investment') {
                $investmentAssets += $bal;
            } else {
                $liquidCash += $bal;
            }

            $accountComposition[] = (object) [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type,
                'balance' => $bal,
            ];
        }

        usort($accountComposition, fn ($a, $b) => $b->balance <=> $a->balance);

        $grossAssets = $liquidCash + $investmentAssets;

        $creditAccounts = $space->creditAccounts()->active()->with('ledgerAccount')->get();
        $creditLiabilities = 0;
        $totalCreditLimit = 0;
        foreach ($creditAccounts as $ca) {
            $creditLiabilities += (int) round($ca->currentLiability());
            $totalCreditLimit += (int) round((float) $ca->credit_limit);
        }

        $netWorth = $grossAssets - $creditLiabilities;
        $creditUtilization = $totalCreditLimit > 0 ? round(($creditLiabilities / $totalCreditLimit) * 100, 1) : null;
        $debtToAssetRatio = $grossAssets > 0 ? round(($creditLiabilities / $grossAssets) * 100, 1) : null;

        return [
            'liquid_cash' => $liquidCash,
            'investment_assets' => $investmentAssets,
            'gross_assets' => $grossAssets,
            'credit_liabilities' => $creditLiabilities,
            'total_credit_limit' => $totalCreditLimit,
            'net_worth' => $netWorth,
            'credit_utilization' => $creditUtilization,
            'debt_to_asset_ratio' => $debtToAssetRatio,
            'account_composition' => $accountComposition,
        ];
    }

    /**
     * Bangun tren arus kas dan savings rate multi-bulan.
     *
     * @return array<string, mixed>
     */
    private function buildMultiMonthTrend(
        Space $space,
        int $year,
        int $month,
        string $periodType,
        int $horizonMonths,
        ?int $accountId = null
    ): array {
        $timezone = $space->timezone ?: 'Asia/Jakarta';
        $anchorMonth = $periodType === 'month'
            ? Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth()
            : $space->now()->startOfMonth();

        $months = [];
        for ($i = $horizonMonths - 1; $i >= 0; $i--) {
            $mDate = $anchorMonth->copy()->subMonthsNoOverflow($i);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();

            $stats = $this->summarizePeriod($space->id, $mStart, $mEnd, $accountId);
            $months[] = (object) [
                'label' => $mDate->translatedFormat('M Y'),
                'short_label' => $mDate->translatedFormat('M y'),
                'year' => (int) $mDate->year,
                'month' => (int) $mDate->month,
                'income' => $stats['income'],
                'expense' => $stats['expense'],
                'net' => $stats['net'],
                'savings_rate' => $stats['savings_rate'],
            ];
        }

        $count = max(1, count($months));
        $avgIncome = (int) round(collect($months)->sum('income') / $count);
        $avgExpense = (int) round(collect($months)->sum('expense') / $count);
        $avgNet = $avgIncome - $avgExpense;

        return [
            'months' => $months,
            'avg_income' => $avgIncome,
            'avg_expense' => $avgExpense,
            'avg_net' => $avgNet,
        ];
    }

    /**
     * Hitung kurva pengeluaran kumulatif harian vs periode lalu dan sebaran hari dalam seminggu.
     *
     * @return array<string, mixed>
     */
    private function buildDailyAndWeekdayPatterns(
        int $spaceId,
        Carbon $startCarbon,
        Carbon $endCarbon,
        Carbon $prevStart,
        Carbon $prevEnd,
        int $totalBudgetLimit,
        ?int $accountId = null
    ): array {
        $currentDailyMap = $this->dailyExpenseMap($spaceId, $startCarbon->toDateString(), $endCarbon->toDateString(), $accountId);
        $prevDailyMap = $this->dailyExpenseMap($spaceId, $prevStart->toDateString(), $prevEnd->toDateString(), $accountId);

        $totalDays = min(62, max(1, (int) $startCarbon->copy()->startOfDay()->diffInDays($endCarbon->copy()->startOfDay()) + 1));
        $prevTotalDays = max(1, (int) $prevStart->copy()->startOfDay()->diffInDays($prevEnd->copy()->startOfDay()) + 1);

        $labels = [];
        $cumCurrent = [];
        $cumPrevious = [];
        $budgetLine = [];

        $runningCurr = 0;
        $runningPrev = 0;

        for ($d = 0; $d < $totalDays; $d++) {
            $currDate = $startCarbon->copy()->addDays($d);
            $currStr = $currDate->toDateString();
            $runningCurr += (int) ($currentDailyMap[$currStr] ?? 0);
            $cumCurrent[] = $runningCurr;
            $labels[] = $totalDays <= 31 ? (string) $currDate->day : $currDate->format('d/m');

            if ($d < $prevTotalDays) {
                $prevDateStr = $prevStart->copy()->addDays($d)->toDateString();
                $runningPrev += (int) ($prevDailyMap[$prevDateStr] ?? 0);
            }
            $cumPrevious[] = $runningPrev;

            if ($totalBudgetLimit > 0) {
                $budgetLine[] = (int) round(($totalBudgetLimit / $totalDays) * ($d + 1));
            }
        }

        // Pola hari dalam seminggu (Senin=1 .. Minggu=7) berdasarkan transaksi riil periode aktif
        $weekdayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $weekdayTotals = array_fill(1, 7, 0);
        $weekdayTxCounts = array_fill(1, 7, 0);

        $txQuery = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereBetween('financial_transactions.transaction_date', [$startCarbon->toDateString(), $endCarbon->toDateString()]);

        if ($accountId !== null) {
            $txQuery->where('transaction_entries.account_id', $accountId);
        }

        $expenseRows = $txQuery
            ->select('financial_transactions.id', 'financial_transactions.transaction_date', 'transaction_entries.amount_signed')
            ->get();

        $seenTxPerDay = [];
        foreach ($expenseRows as $row) {
            $dow = Carbon::parse($row->transaction_date)->dayOfWeekIso;
            $weekdayTotals[$dow] += (int) round(abs((float) $row->amount_signed));
            if (! isset($seenTxPerDay[$row->id])) {
                $weekdayTxCounts[$dow]++;
                $seenTxPerDay[$row->id] = true;
            }
        }

        $grandWeekdayTotal = array_sum($weekdayTotals);
        $weekdays = [];
        $peakDay = null;
        $weekdaySum = 0;
        $weekendSum = 0;

        foreach ($weekdayNames as $iso => $label) {
            $amount = $weekdayTotals[$iso];
            $count = $weekdayTxCounts[$iso];
            $pct = $grandWeekdayTotal > 0 ? round(($amount / $grandWeekdayTotal) * 100, 1) : 0.0;

            if ($iso <= 5) {
                $weekdaySum += $amount;
            } else {
                $weekendSum += $amount;
            }

            $item = (object) [
                'iso' => $iso,
                'label' => $label,
                'short' => substr($label, 0, 3),
                'amount' => $amount,
                'count' => $count,
                'avg_per_tx' => $count > 0 ? (int) round($amount / $count) : 0,
                'percentage' => $pct,
            ];
            $weekdays[] = $item;

            if ($amount > 0 && ($peakDay === null || $amount > $peakDay->amount)) {
                $peakDay = $item;
            }
        }

        return [
            'cumulative' => [
                'labels' => $labels,
                'current' => $cumCurrent,
                'previous' => $cumPrevious,
                'budget_line' => $budgetLine,
            ],
            'weekdays' => $weekdays,
            'peak_day' => $peakDay,
            'weekday_total' => $weekdaySum,
            'weekend_total' => $weekendSum,
            'weekend_pct' => $grandWeekdayTotal > 0 ? round(($weekendSum / $grandWeekdayTotal) * 100, 1) : 0.0,
        ];
    }

    /**
     * Peta total pengeluaran per tanggal (YYYY-MM-DD => int).
     *
     * @return array<string, int>
     */
    private function dailyExpenseMap(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): array
    {
        $query = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId !== null) {
            $query->where('transaction_entries.account_id', $accountId);
        }

        $rows = $query
            ->groupBy('financial_transactions.transaction_date')
            ->select(
                'financial_transactions.transaction_date',
                DB::raw('SUM(ABS(transaction_entries.amount_signed)) as total')
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $dateKey = Carbon::parse($row->transaction_date)->toDateString();
            $map[$dateKey] = (int) round((float) $row->total);
        }

        if ($accountId === null) {
            $feeRows = DB::table('financial_transactions')
                ->where('space_id', $spaceId)
                ->where('status', 'posted')
                ->where('type', 'transfer')
                ->where('fee_amount', '>', 0)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->groupBy('transaction_date')
                ->select('transaction_date', DB::raw('SUM(fee_amount) as total'))
                ->get();

            foreach ($feeRows as $row) {
                $dateKey = Carbon::parse($row->transaction_date)->toDateString();
                $map[$dateKey] = ($map[$dateKey] ?? 0) + (int) round((float) $row->total);
            }
        }

        return $map;
    }

    /**
     * Rangkum pengeluaran per kategori untuk rentang tanggal.
     *
     * @return Collection<int, object>
     */
    private function expenseByCategory(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): Collection
    {
        $query = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->join('categories', 'financial_transactions.category_id', '=', 'categories.id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId !== null) {
            $query->where('transaction_entries.account_id', $accountId);
        }

        $rows = $query
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT financial_transactions.id) as tx_count'),
                DB::raw('SUM(ABS(transaction_entries.amount_signed)) as total_amount')
            )
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($rows as $id => $row) {
            $result[$id] = (object) [
                'id' => (int) $id,
                'name' => $row->name,
                'count' => (int) $row->tx_count,
                'amount' => (int) round((float) $row->total_amount),
            ];
        }

        if ($accountId === null) {
            $feeRows = DB::table('financial_transactions')
                ->join('categories', 'financial_transactions.fee_category_id', '=', 'categories.id')
                ->where('financial_transactions.space_id', $spaceId)
                ->where('financial_transactions.status', 'posted')
                ->where('financial_transactions.type', 'transfer')
                ->where('financial_transactions.fee_amount', '>', 0)
                ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
                ->groupBy('categories.id', 'categories.name')
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('COUNT(DISTINCT financial_transactions.id) as tx_count'),
                    DB::raw('SUM(financial_transactions.fee_amount) as total_amount')
                )
                ->get();

            foreach ($feeRows as $feeRow) {
                $id = (int) $feeRow->id;
                if (isset($result[$id])) {
                    $result[$id]->amount += (int) round((float) $feeRow->total_amount);
                    $result[$id]->count += (int) $feeRow->tx_count;
                } else {
                    $result[$id] = (object) [
                        'id' => $id,
                        'name' => $feeRow->name,
                        'count' => (int) $feeRow->tx_count,
                        'amount' => (int) round((float) $feeRow->total_amount),
                    ];
                }
            }
        }

        return collect($result)->sortByDesc('amount')->values();
    }

    /**
     * Bandingkan pengeluaran kategori terhadap periode sebelumnya dan hitung konsentrasi Pareto.
     *
     * @return array<string, mixed>
     */
    private function buildCategoryMovers(Collection $currentCats, Collection $prevCats, int $totalExpense): array
    {
        $prevMap = $prevCats->keyBy('id');
        $currMap = $currentCats->keyBy('id');
        $allIds = $currMap->keys()->merge($prevMap->keys())->unique();

        $movers = collect();
        foreach ($allIds as $catId) {
            $curr = $currMap->get($catId);
            $prev = $prevMap->get($catId);

            $currAmount = $curr ? (int) $curr->amount : 0;
            $prevAmount = $prev ? (int) $prev->amount : 0;
            $diff = $currAmount - $prevAmount;

            $movers->push((object) [
                'id' => $catId,
                'name' => $curr?->name ?? $prev?->name ?? 'Kategori',
                'current_amount' => $currAmount,
                'prev_amount' => $prevAmount,
                'diff_amount' => $diff,
                'diff_pct' => $this->percentChange($prevAmount, $currAmount),
                'share_pct' => $totalExpense > 0 ? round(($currAmount / $totalExpense) * 100, 1) : 0.0,
                'tx_count' => $curr ? (int) $curr->count : 0,
            ]);
        }

        $sortedByCurrent = $movers->filter(fn ($m) => $m->current_amount > 0 || $m->prev_amount > 0)
            ->sortByDesc('current_amount')
            ->values();

        $topGainer = $movers->filter(fn ($m) => $m->diff_amount > 0)->sortByDesc('diff_amount')->first();
        $topSaver = $movers->filter(fn ($m) => $m->diff_amount < 0)->sortBy('diff_amount')->first();

        $top3Total = (int) $currentCats->take(3)->sum('amount');
        $top3ConcentrationPct = $totalExpense > 0 ? round(($top3Total / $totalExpense) * 100, 1) : 0.0;

        return [
            'items' => $sortedByCurrent,
            'top_gainer' => $topGainer,
            'top_saver' => $topSaver,
            'top3_concentration_pct' => $top3ConcentrationPct,
            'active_category_count' => $currentCats->count(),
        ];
    }

    /**
     * Evaluasi realisasi anggaran, kecepatan serapan (pace), dan pengeluaran tanpa anggaran.
     *
     * @return array<string, mixed>
     */
    private function buildBudgetVariance(
        Space $space,
        int $year,
        int $month,
        Collection $categoryCurrent,
        int $elapsedDays,
        int $totalDaysInPeriod
    ): array {
        $budgets = $space->budgets()
            ->where('year', $year)
            ->where('month', $month)
            ->with('category:id,name')
            ->get();

        $spentByCategory = $categoryCurrent->keyBy('id');
        $budgetedCategoryIds = [];
        $items = collect();
        $totalLimit = 0;
        $totalSpent = 0;
        $overCount = 0;
        $paceRatio = ($elapsedDays > 0 && $totalDaysInPeriod > 0) ? ($elapsedDays / $totalDaysInPeriod) : 1.0;

        foreach ($budgets as $budget) {
            $budgetedCategoryIds[] = $budget->category_id;
            $spent = (int) ($spentByCategory->get($budget->category_id)?->amount ?? 0);
            $limit = (int) $budget->limit_amount;
            $pctUsed = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;
            $projected = ($elapsedDays > 0 && $elapsedDays < $totalDaysInPeriod)
                ? (int) round(($spent / $elapsedDays) * $totalDaysInPeriod)
                : $spent;

            $status = 'aman';
            if ($spent > $limit) {
                $status = 'over';
                $overCount++;
            } elseif ($pctUsed >= 85 || ($projected > $limit && $paceRatio >= 0.2)) {
                $status = 'waspada';
            }

            $totalLimit += $limit;
            $totalSpent += $spent;

            $items->push((object) [
                'category_id' => $budget->category_id,
                'category_name' => $budget->category?->name ?? 'Kategori',
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => $limit - $spent,
                'pct_used' => $pctUsed,
                'projected' => $projected,
                'status' => $status,
            ]);
        }

        $unbudgeted = $categoryCurrent
            ->filter(fn ($cat) => ! in_array($cat->id, $budgetedCategoryIds, true))
            ->take(5)
            ->values();

        return [
            'has_budgets' => $budgets->isNotEmpty(),
            'items' => $items->sortByDesc('pct_used')->values(),
            'total_limit' => $totalLimit,
            'total_spent' => $totalSpent,
            'over_count' => $overCount,
            'unbudgeted' => $unbudgeted,
        ];
    }

    /**
     * Pisahkan pengeluaran tetap/terjadwal (recurring & cicilan) dari pengeluaran diskresioner.
     *
     * @return array<string, mixed>
     */
    private function buildSpendingStructure(
        int $spaceId,
        string $startDate,
        string $endDate,
        int $totalExpense,
        ?int $accountId = null
    ): array {
        $committedQuery = FinancialTransaction::query()
            ->where('financial_transactions.space_id', $spaceId)
            ->posted()
            ->expense()
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereHas('recurringOccurrence')
                    ->orWhereHas('installmentPlan');
            })
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id');

        if ($accountId !== null) {
            $committedQuery->where('transaction_entries.account_id', $accountId);
        }

        $committedAmount = min($totalExpense, (int) round((float) $committedQuery->sum(DB::raw('ABS(transaction_entries.amount_signed)'))));
        $discretionaryAmount = max(0, $totalExpense - $committedAmount);

        return [
            'committed' => $committedAmount,
            'discretionary' => $discretionaryAmount,
            'committed_pct' => $totalExpense > 0 ? round(($committedAmount / $totalExpense) * 100, 1) : 0.0,
            'discretionary_pct' => $totalExpense > 0 ? round(($discretionaryAmount / $totalExpense) * 100, 1) : 0.0,
        ];
    }

    /**
     * Proyeksikan kewajiban rutin dan cicilan 6 bulan ke depan serta beban bunga aktif.
     *
     * @return array<string, mixed>
     */
    private function buildUpcomingCommitments(Space $space, Carbon $now): array
    {
        $startMonth = $now->copy()->startOfMonth();
        $endMonth = $startMonth->copy()->addMonthsNoOverflow(5)->endOfMonth();

        $pendingInstallments = Installment::query()
            ->where('space_id', $space->id)
            ->active()
            ->pending()
            ->whereBetween('due_date', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->get();

        $allPendingInstallments = Installment::query()
            ->where('space_id', $space->id)
            ->active()
            ->pending()
            ->get();

        $remainingPrincipal = (int) round((float) $allPendingInstallments->sum('principal_amount'));
        $remainingInterestAndFee = (int) round((float) $allPendingInstallments->sum(fn ($i) => (float) $i->interest_amount + (float) $i->fee_amount));

        $installmentsByMonth = [];
        foreach ($pendingInstallments as $inst) {
            $ym = $inst->due_date->format('Y-m');
            $installmentsByMonth[$ym] = ($installmentsByMonth[$ym] ?? 0) + (int) round((float) $inst->total_amount);
        }

        $recurringSchedules = RecurringSchedule::query()
            ->where('space_id', $space->id)
            ->active()
            ->whereIn('type', ['expense', 'credit_payment'])
            ->get();

        $months = [];
        $totalSixMonthCommitment = 0;

        for ($i = 0; $i < 6; $i++) {
            $mDate = $startMonth->copy()->addMonthsNoOverflow($i);
            $ym = $mDate->format('Y-m');
            $instAmount = $installmentsByMonth[$ym] ?? 0;

            $recAmount = 0;
            foreach ($recurringSchedules as $sch) {
                $amt = (float) ($sch->amount ?? 0);
                if ($amt <= 0) {
                    continue;
                }
                $schStart = Carbon::parse($sch->start_date)->startOfDay();
                $schEnd = $sch->end_date ? Carbon::parse($sch->end_date)->endOfDay() : null;
                if ($mDate->copy()->endOfMonth()->lt($schStart)) {
                    continue;
                }
                if ($schEnd && $mDate->copy()->startOfMonth()->gt($schEnd)) {
                    continue;
                }

                $recAmount += match ($sch->frequency) {
                    'daily' => $amt * $mDate->daysInMonth,
                    'weekly' => $amt * 4,
                    'yearly' => ($mDate->month === $schStart->month) ? $amt : 0,
                    default => $amt,
                };
            }

            $recInt = (int) round($recAmount);
            $combined = $instAmount + $recInt;
            $totalSixMonthCommitment += $combined;

            $months[] = (object) [
                'ym' => $ym,
                'label' => $mDate->translatedFormat('M Y'),
                'installments' => $instAmount,
                'recurring' => $recInt,
                'total' => $combined,
            ];
        }

        $plannedShortPlansTotal = (int) round((float) ShortPlan::query()
            ->where('space_id', $space->id)
            ->planned()
            ->sum('planned_amount'));

        return [
            'months' => $months,
            'total_6_months' => $totalSixMonthCommitment,
            'remaining_principal' => $remainingPrincipal,
            'remaining_interest_and_fee' => $remainingInterestAndFee,
            'planned_short_plans_total' => $plannedShortPlansTotal,
        ];
    }

    /**
     * Rangkum pengeluaran berdasarkan anggota ruang (member_id) bila tersedia.
     *
     * @return Collection<int, object>
     */
    private function buildMemberBreakdown(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): Collection
    {
        $query = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->leftJoin('members', 'financial_transactions.member_id', '=', 'members.id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId !== null) {
            $query->where('transaction_entries.account_id', $accountId);
        }

        $rows = $query
            ->groupBy('financial_transactions.member_id', 'members.name', 'members.color')
            ->select(
                'financial_transactions.member_id',
                'members.name as member_name',
                'members.color as member_color',
                DB::raw('COUNT(DISTINCT financial_transactions.id) as tx_count'),
                DB::raw('SUM(ABS(transaction_entries.amount_signed)) as total_amount')
            )
            ->orderByDesc('total_amount')
            ->get();

        $hasTaggedMember = $rows->contains(fn ($r) => $r->member_id !== null);
        if (! $hasTaggedMember) {
            return collect();
        }

        $total = (int) $rows->sum(fn ($r) => (int) round((float) $r->total_amount));

        return $rows->map(function ($row) use ($total) {
            $amt = (int) round((float) $row->total_amount);

            return (object) [
                'member_id' => $row->member_id,
                'name' => $row->member_name ?: 'Tanpa Tag Anggota',
                'color' => $row->member_color ?: '#64748B',
                'count' => (int) $row->tx_count,
                'amount' => $amt,
                'percentage' => $total > 0 ? round(($amt / $total) * 100, 1) : 0.0,
            ];
        })->values();
    }

    /**
     * Ambil 5 transaksi pengeluaran terbesar pada periode terpilih.
     *
     * @return Collection<int, object>
     */
    private function buildTopExpenses(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): Collection
    {
        $query = FinancialTransaction::query()
            ->where('space_id', $spaceId)
            ->posted()
            ->expense()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['category:id,name', 'entries.account:id,name', 'member:id,name,color']);

        if ($accountId !== null) {
            $query->whereHas('entries', fn ($q) => $q->where('account_id', $accountId));
        }

        return $query->get()
            ->map(function (FinancialTransaction $tx) use ($accountId) {
                $amount = $accountId !== null
                    ? (int) round(abs((float) ($tx->entries->firstWhere('account_id', $accountId)?->amount_signed ?? 0)))
                    : (int) round($tx->totalAmount());

                return (object) [
                    'id' => $tx->id,
                    'description' => $tx->description,
                    'date' => $tx->transaction_date->translatedFormat('d M Y'),
                    'category_name' => $tx->category?->name ?? '-',
                    'account_name' => $tx->sourceAccount()?->name ?? '-',
                    'member_name' => $tx->member?->name,
                    'amount' => $amount,
                    'url' => route('transactions.show', $tx),
                ];
            })
            ->sortByDesc('amount')
            ->take(5)
            ->values();
    }

    /**
     * Evaluasi status kualitatif indikator kesehatan keuangan berdasarkan ambang batas terukur.
     *
     * @return array<string, array{label: string, tone: string}>
     */
    private function evaluateHealthSignals(
        ?float $savingsRate,
        ?float $runwayMonths,
        ?float $creditUtilization,
        int $overBudgetCount,
        int $budgetCount,
        int $income,
        int $expense
    ): array {
        if ($savingsRate === null) {
            $savingsSignal = $expense > 0
                ? ['label' => 'Belum Ada Pemasukan', 'tone' => 'warning']
                : ['label' => 'Belum Ada Data', 'tone' => 'neutral'];
        } elseif ($savingsRate >= 20) {
            $savingsSignal = ['label' => 'Sehat (>= 20%)', 'tone' => 'positive'];
        } elseif ($savingsRate >= 10) {
            $savingsSignal = ['label' => 'Cukup (10-19%)', 'tone' => 'info'];
        } elseif ($savingsRate >= 0) {
            $savingsSignal = ['label' => 'Ketat (< 10%)', 'tone' => 'warning'];
        } else {
            $savingsSignal = ['label' => 'Defisit', 'tone' => 'danger'];
        }

        if ($runwayMonths === null) {
            $runwaySignal = ['label' => 'Belum Terukur', 'tone' => 'neutral'];
        } elseif ($runwayMonths >= 6) {
            $runwaySignal = ['label' => 'Kuat (>= 6 bln)', 'tone' => 'positive'];
        } elseif ($runwayMonths >= 3) {
            $runwaySignal = ['label' => 'Cukup (3-5.9 bln)', 'tone' => 'info'];
        } else {
            $runwaySignal = ['label' => 'Rentan (< 3 bln)', 'tone' => 'warning'];
        }

        if ($creditUtilization === null) {
            $creditSignal = ['label' => 'Tidak Ada Limit Kredit', 'tone' => 'neutral'];
        } elseif ($creditUtilization <= 30) {
            $creditSignal = ['label' => 'Aman (<= 30%)', 'tone' => 'positive'];
        } elseif ($creditUtilization <= 60) {
            $creditSignal = ['label' => 'Perhatikan (31-60%)', 'tone' => 'warning'];
        } else {
            $creditSignal = ['label' => 'Tinggi (> 60%)', 'tone' => 'danger'];
        }

        if ($budgetCount === 0) {
            $budgetSignal = ['label' => 'Belum Ada Anggaran', 'tone' => 'neutral'];
        } elseif ($overBudgetCount === 0) {
            $budgetSignal = ['label' => 'Semua Sesuai Batas', 'tone' => 'positive'];
        } else {
            $budgetSignal = ['label' => $overBudgetCount.' Kategori Melebihi Batas', 'tone' => 'danger'];
        }

        return [
            'savings' => $savingsSignal,
            'runway' => $runwaySignal,
            'credit' => $creditSignal,
            'budget' => $budgetSignal,
        ];
    }

    /**
     * Hasilkan daftar temuan deterministik langsung dari angka yang terhitung.
     *
     * @return list<array{tone: string, title: string, detail: string}>
     */
    private function generateInsights(
        array $periodComparison,
        array $categoryAnalysis,
        array $dailyPattern,
        array $budgetAnalysis,
        array $balanceSheet,
        ?float $runwayMonths,
        array $commitmentsForecast
    ): array {
        $insights = [];
        $curr = $periodComparison['current'];
        $expDeltaPct = $periodComparison['expense_delta_pct'];

        if ($curr['expense'] === 0 && $curr['income'] === 0) {
            return [[
                'tone' => 'info',
                'title' => 'Belum ada transaksi pada periode ini',
                'detail' => 'Catat pemasukan atau pengeluaran agar perbandingan tren, laju belanja harian, dan efisiensi anggaran dapat dihitung.',
            ]];
        }

        if ($expDeltaPct !== null && abs($expDeltaPct) >= 10) {
            $isUp = $expDeltaPct > 0;
            $insights[] = [
                'tone' => $isUp ? 'warning' : 'positive',
                'title' => $isUp
                    ? 'Pengeluaran naik '.abs($expDeltaPct).'% dibanding '.$periodComparison['prev_label']
                    : 'Pengeluaran turun '.abs($expDeltaPct).'% dibanding '.$periodComparison['prev_label'],
                'detail' => 'Selisih Rp '.number_format(abs($periodComparison['expense_delta']), 0, ',', '.').' dari periode sebelumnya.',
            ];
        }

        if ($categoryAnalysis['top_gainer'] !== null && $categoryAnalysis['top_gainer']->diff_amount > 0) {
            $gainer = $categoryAnalysis['top_gainer'];
            $insights[] = [
                'tone' => 'info',
                'title' => 'Kenaikan kategori terbesar: '.$gainer->name,
                'detail' => 'Bertambah Rp '.number_format($gainer->diff_amount, 0, ',', '.').' dibanding '.$periodComparison['prev_label'].'.',
            ];
        }

        if ($categoryAnalysis['active_category_count'] >= 3 && $categoryAnalysis['top3_concentration_pct'] >= 70) {
            $insights[] = [
                'tone' => 'info',
                'title' => 'Konsentrasi pengeluaran tinggi pada 3 kategori utama',
                'detail' => $categoryAnalysis['top3_concentration_pct'].'% dari total pengeluaran berasal dari 3 kategori terbesar.',
            ];
        }

        if ($dailyPattern['weekend_pct'] >= 40 && $curr['expense'] > 0) {
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Belanja akhir pekan menyumbang '.$dailyPattern['weekend_pct'].'% pengeluaran',
                'detail' => 'Total pengeluaran Sabtu-Minggu mencapai Rp '.number_format($dailyPattern['weekend_total'], 0, ',', '.').'.',
            ];
        }

        if ($budgetAnalysis['over_count'] > 0) {
            $insights[] = [
                'tone' => 'danger',
                'title' => $budgetAnalysis['over_count'].' kategori melewati batas anggaran',
                'detail' => 'Tinjau tabel efisiensi anggaran di bawah untuk melihat kategori yang perlu pengendalian.',
            ];
        } elseif ($budgetAnalysis['unbudgeted']->isNotEmpty()) {
            $topUnbudgeted = $budgetAnalysis['unbudgeted']->first();
            $insights[] = [
                'tone' => 'info',
                'title' => 'Kategori "'.$topUnbudgeted->name.'" belum memiliki anggaran',
                'detail' => 'Sudah mencatat pengeluaran Rp '.number_format($topUnbudgeted->amount, 0, ',', '.').' pada periode ini.',
            ];
        }

        if ($balanceSheet['credit_utilization'] !== null && $balanceSheet['credit_utilization'] > 30) {
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Utilisasi limit kredit berada di '.$balanceSheet['credit_utilization'].'%',
                'detail' => 'Menjaga rasio utang kredit di bawah 30% membantu kelonggaran arus kas bulanan.',
            ];
        }

        if ($runwayMonths !== null && $runwayMonths < 3 && $curr['expense'] > 0) {
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Ketahanan dana likuid di bawah 3 bulan ('.$runwayMonths.' bulan)',
                'detail' => 'Dihitung dari saldo kas likuid dibagi rata-rata pengeluaran bulanan terakhir.',
            ];
        }

        if ($commitmentsForecast['total_6_months'] > 0 && count($insights) < 4) {
            $insights[] = [
                'tone' => 'info',
                'title' => 'Komitmen rutin & cicilan 6 bulan ke depan: Rp '.number_format($commitmentsForecast['total_6_months'], 0, ',', '.'),
                'detail' => 'Mencakup jadwal tagihan rutin aktif dan angsuran kredit yang masih berjalan.',
            ];
        }

        return array_slice($insights, 0, 5);
    }

    private function percentChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(string $startDate, string $endDate, int $horizonMonths): array
    {
        return [
            'horizonMonths' => $horizonMonths,
            'periodComparison' => [
                'prev_label' => '-',
                'current' => ['income' => 0, 'expense' => 0, 'net' => 0, 'savings_rate' => null, 'expense_ratio' => null, 'tx_count' => 0],
                'previous' => ['income' => 0, 'expense' => 0, 'net' => 0, 'savings_rate' => null, 'expense_ratio' => null, 'tx_count' => 0],
                'income_delta' => 0,
                'income_delta_pct' => null,
                'expense_delta' => 0,
                'expense_delta_pct' => null,
                'net_delta' => 0,
            ],
            'balanceSheet' => [
                'liquid_cash' => 0,
                'investment_assets' => 0,
                'gross_assets' => 0,
                'credit_liabilities' => 0,
                'total_credit_limit' => 0,
                'net_worth' => 0,
                'credit_utilization' => null,
                'debt_to_asset_ratio' => null,
                'account_composition' => [],
            ],
            'runwayMonths' => null,
            'baselineMonthlyExpense' => 0,
            'dailyBurnRate' => 0,
            'projectedPeriodExpense' => 0,
            'elapsedDays' => 0,
            'totalDaysInPeriod' => 1,
            'healthSignals' => [
                'savings' => ['label' => 'Belum Ada Data', 'tone' => 'neutral'],
                'runway' => ['label' => 'Belum Terukur', 'tone' => 'neutral'],
                'credit' => ['label' => 'Tidak Ada Limit Kredit', 'tone' => 'neutral'],
                'budget' => ['label' => 'Belum Ada Anggaran', 'tone' => 'neutral'],
            ],
            'trendData' => ['months' => [], 'avg_income' => 0, 'avg_expense' => 0, 'avg_net' => 0],
            'dailyPattern' => [
                'cumulative' => ['labels' => [], 'current' => [], 'previous' => [], 'budget_line' => []],
                'weekdays' => [],
                'peak_day' => null,
                'weekday_total' => 0,
                'weekend_total' => 0,
                'weekend_pct' => 0.0,
            ],
            'categoryAnalysis' => [
                'items' => collect(),
                'top_gainer' => null,
                'top_saver' => null,
                'top3_concentration_pct' => 0.0,
                'active_category_count' => 0,
            ],
            'budgetAnalysis' => [
                'has_budgets' => false,
                'items' => collect(),
                'total_limit' => 0,
                'total_spent' => 0,
                'over_count' => 0,
                'unbudgeted' => collect(),
            ],
            'spendingStructure' => [
                'committed' => 0,
                'discretionary' => 0,
                'committed_pct' => 0.0,
                'discretionary_pct' => 0.0,
            ],
            'commitmentsForecast' => [
                'months' => [],
                'total_6_months' => 0,
                'remaining_principal' => 0,
                'remaining_interest_and_fee' => 0,
                'planned_short_plans_total' => 0,
            ],
            'memberBreakdown' => collect(),
            'topExpenses' => collect(),
            'insights' => [],
        ];
    }
}
