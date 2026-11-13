<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\PlannedIncome;
use App\Models\RecurringSchedule;
use App\Models\ShortPlan;
use App\Models\Space;
use Carbon\Carbon;

class CashflowProjectionService
{
    /**
     * Hitung proyeksi saldo dan arus kas ke depan berdasarkan jadwal berulang, cicilan, dan rencana pemasukan.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function project(Space $space, array $options = []): array
    {
        $timezone = $space->timezone ?: 'Asia/Jakarta';
        $now = $space->now();

        // 1. Tentukan rentang horizon waktu proyeksi
        $startDateStr = ! empty($options['start_date'])
            ? Carbon::parse($options['start_date'], $timezone)->toDateString()
            : $now->toDateString();

        $horizonDays = (int) ($options['horizon_days'] ?? 30);
        if (! empty($options['end_date'])) {
            $endDateStr = Carbon::parse($options['end_date'], $timezone)->toDateString();
            if ($endDateStr < $startDateStr) {
                $endDateStr = $startDateStr;
            }
            $startC = Carbon::parse($startDateStr, $timezone)->startOfDay();
            $endC = Carbon::parse($endDateStr, $timezone)->endOfDay();
            $horizonDays = max(1, (int) $startC->diffInDays($endC) + 1);
        } else {
            $horizonDays = in_array($horizonDays, [30, 60, 90, 180, 365], true) ? $horizonDays : 30;
            $startC = Carbon::parse($startDateStr, $timezone)->startOfDay();
            $endC = $startC->copy()->addDays($horizonDays - 1)->endOfDay();
            $endDateStr = $endC->toDateString();
        }

        // 2. Filter akun (jika pengguna memilih rekening tertentu)
        $selectedAccountId = ! empty($options['account_id']) ? (int) $options['account_id'] : null;
        $selectedAccount = null;
        if ($selectedAccountId !== null) {
            $selectedAccount = $space->accounts()->whereNull('archived_at')->find($selectedAccountId);
            if (! $selectedAccount) {
                $selectedAccountId = null;
            }
        }

        $liquidAccounts = $space->accounts()
            ->whereNull('archived_at')
            ->liquidAssets()
            ->orderBy('name')
            ->get();

        // 3. Hitung Saldo Awal (Starting Balance)
        $startingBalance = 0.0;
        if ($selectedAccount) {
            $startingBalance = (float) $selectedAccount->currentBalance();
        } else {
            foreach ($liquidAccounts as $acc) {
                $startingBalance += (float) $acc->currentBalance();
            }
        }
        $startingBalance = round($startingBalance, 2);

        // 4. Pengaturan Komponen Simulasi
        $includeRecurring = array_key_exists('include_recurring', $options)
            ? (bool) $options['include_recurring']
            : true;

        $includeInstallments = array_key_exists('include_installments', $options)
            ? (bool) $options['include_installments']
            : true;

        $includePlannedIncomes = array_key_exists('include_planned_incomes', $options)
            ? (bool) $options['include_planned_incomes']
            : true;

        $includeShortPlans = array_key_exists('include_short_plans', $options)
            ? (bool) $options['include_short_plans']
            : false;

        $events = [];

        // Breakdown aggregates
        $breakdown = [
            'recurring_income' => 0.0,
            'planned_incomes' => 0.0,
            'recurring_expense' => 0.0,
            'installments' => 0.0,
            'short_plans' => 0.0,
        ];

        $counts = [
            'recurring_count' => 0,
            'installment_count' => 0,
            'planned_income_count' => 0,
            'short_plan_count' => 0,
        ];

        // 5. Ekstrak Transaksi Berulang (Recurring Schedules)
        if ($includeRecurring) {
            $this->extractRecurringEvents(
                space: $space,
                startDateStr: $startDateStr,
                endDateStr: $endDateStr,
                selectedAccountId: $selectedAccountId,
                events: $events,
                breakdown: $breakdown,
                counts: $counts
            );
        }

        // 6. Ekstrak Cicilan Jatuh Tempo (Installments)
        if ($includeInstallments) {
            $this->extractInstallmentEvents(
                space: $space,
                startDateStr: $startDateStr,
                endDateStr: $endDateStr,
                selectedAccountId: $selectedAccountId,
                events: $events,
                breakdown: $breakdown,
                counts: $counts
            );
        }

        // 7. Ekstrak Rencana Pemasukan (Planned Incomes)
        if ($includePlannedIncomes) {
            $this->extractPlannedIncomeEvents(
                space: $space,
                startDateStr: $startDateStr,
                endDateStr: $endDateStr,
                selectedAccountId: $selectedAccountId,
                events: $events,
                breakdown: $breakdown,
                counts: $counts
            );
        }

        // 8. Ekstrak Short Plan (Belanja Terencana Opsional)
        if ($includeShortPlans) {
            $this->extractShortPlanEvents(
                space: $space,
                startDateStr: $startDateStr,
                endDateStr: $endDateStr,
                selectedAccountId: $selectedAccountId,
                events: $events,
                breakdown: $breakdown,
                counts: $counts
            );
        }

        // 9. Urutkan seluruh kejadian proyeksi secara kronologis (Tanggal ASC, Inflow sebelum Outflow)
        usort($events, function (array $a, array $b): int {
            if ($a['date'] !== $b['date']) {
                return strcmp($a['date'], $b['date']);
            }
            // Pemasukan didahulukan sebelum pengeluaran di hari yang sama
            if ($a['flow'] !== $b['flow']) {
                return $a['flow'] === 'in' ? -1 : 1;
            }

            return strcmp((string) $a['id'], (string) $b['id']);
        });

        // 10. Hitung simulasi saldo kumulatif langkah demi langkah
        $runningBalance = $startingBalance;
        $lowestBalance = $startingBalance;
        $lowestBalanceDate = $startDateStr;
        $highestBalance = $startingBalance;
        $highestBalanceDate = $startDateStr;
        $deficitDate = null;
        $maxDeficit = 0.0;
        $totalInflows = 0.0;
        $totalOutflows = 0.0;

        foreach ($events as &$evt) {
            if ($evt['flow'] === 'in') {
                $runningBalance += (float) $evt['amount'];
                $totalInflows += (float) $evt['amount'];
            } else {
                $runningBalance -= (float) $evt['amount'];
                $totalOutflows += (float) $evt['amount'];
            }
            $runningBalance = round($runningBalance, 2);
            $evt['running_balance'] = $runningBalance;
            $evt['is_negative'] = $runningBalance < 0;

            if ($runningBalance < $lowestBalance) {
                $lowestBalance = $runningBalance;
                $lowestBalanceDate = $evt['date'];
            }
            if ($runningBalance > $highestBalance) {
                $highestBalance = $runningBalance;
                $highestBalanceDate = $evt['date'];
            }
            if ($runningBalance < 0) {
                if ($deficitDate === null) {
                    $deficitDate = $evt['date'];
                }
                $def = abs($runningBalance);
                if ($def > $maxDeficit) {
                    $maxDeficit = $def;
                }
            }
        }
        unset($evt);

        $endingBalance = $runningBalance;
        $netChange = round($totalInflows - $totalOutflows, 2);

        // 11. Bangun Data Series Harian untuk Visualisasi Grafik (Chart.js)
        $dailyPoints = $this->buildDailySeries(
            startDateStr: $startDateStr,
            endDateStr: $endDateStr,
            startingBalance: $startingBalance,
            events: $events,
            timezone: $timezone
        );

        return [
            'space' => $space,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'horizon_days' => $horizonDays,
            'selected_account' => $selectedAccount,
            'selected_account_id' => $selectedAccountId,
            'liquid_accounts' => $liquidAccounts,
            'starting_balance' => $startingBalance,
            'ending_balance' => $endingBalance,
            'net_change' => $netChange,
            'total_inflows' => round($totalInflows, 2),
            'total_outflows' => round($totalOutflows, 2),
            'lowest_balance' => round($lowestBalance, 2),
            'lowest_balance_date' => $lowestBalanceDate,
            'highest_balance' => round($highestBalance, 2),
            'highest_balance_date' => $highestBalanceDate,
            'is_deficit_risk' => $lowestBalance < 0,
            'deficit_date' => $deficitDate,
            'max_deficit_amount' => round($maxDeficit, 2),
            'timeline_events' => $events,
            'daily_points' => $dailyPoints,
            'breakdown' => [
                'recurring_income' => round($breakdown['recurring_income'], 2),
                'planned_incomes' => round($breakdown['planned_incomes'], 2),
                'recurring_expense' => round($breakdown['recurring_expense'], 2),
                'installments' => round($breakdown['installments'], 2),
                'short_plans' => round($breakdown['short_plans'], 2),
            ],
            'counts' => $counts,
            'options' => [
                'include_recurring' => $includeRecurring,
                'include_installments' => $includeInstallments,
                'include_planned_incomes' => $includePlannedIncomes,
                'include_short_plans' => $includeShortPlans,
            ],
        ];
    }

    /**
     * Ekstrak seluruh kejadian transaksi berulang yang jatuh pada rentang horizon.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<string, float>  $breakdown
     * @param  array<string, int>  $counts
     */
    protected function extractRecurringEvents(
        Space $space,
        string $startDateStr,
        string $endDateStr,
        ?int $selectedAccountId,
        array &$events,
        array &$breakdown,
        array &$counts
    ): void {
        $schedules = RecurringSchedule::query()
            ->where('space_id', $space->id)
            ->active()
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->get();

        $startCarbon = Carbon::parse($startDateStr)->startOfDay();
        $endCarbon = Carbon::parse($endDateStr)->endOfDay();

        foreach ($schedules as $schedule) {
            $amt = (float) ($schedule->amount ?? 0);
            if ($amt <= 0) {
                continue;
            }

            $type = $schedule->type; // expense, transfer, credit_payment, income
            $isIncome = ($type === 'income');
            $isExpense = in_array($type, ['expense', 'credit_payment'], true);
            $isTransfer = ($type === 'transfer');

            // Cek kecocokan akun bila pengguna memilih filter akun tunggal
            if ($selectedAccountId !== null) {
                if ($isIncome && (int) $schedule->destination_account_id !== $selectedAccountId) {
                    continue;
                }
                if ($isExpense && (int) $schedule->source_account_id !== $selectedAccountId) {
                    continue;
                }
                if ($isTransfer && (int) $schedule->source_account_id !== $selectedAccountId && (int) $schedule->destination_account_id !== $selectedAccountId) {
                    continue;
                }
            }

            // Bila tidak memilih akun spesifik (keseluruhan kas), transfer antar rekening kas net bernilai 0
            if ($selectedAccountId === null && $isTransfer) {
                continue;
            }

            // Tentukan tanggal jatuh tempo pertama yang masuk ke rentang
            $cursor = Carbon::parse($schedule->next_due_date)->startOfDay();

            // Bila next_due_date lampau, majukan hingga mencapai >= startDateStr
            $loopLimit = 0;
            while ($cursor->lt($startCarbon) && $loopLimit < 200) {
                $cursor = $schedule->calculateNextDueDate($cursor);
                $loopLimit++;
            }

            $scheduleEnd = $schedule->end_date ? Carbon::parse($schedule->end_date)->endOfDay() : null;
            $occurrenceCount = 0;

            while ($cursor->lte($endCarbon) && $occurrenceCount < 366) {
                if ($scheduleEnd && $cursor->gt($scheduleEnd)) {
                    break;
                }

                if ($cursor->gte($startCarbon)) {
                    $flow = 'out';
                    if ($isIncome) {
                        $flow = 'in';
                    } elseif ($isTransfer && $selectedAccountId !== null) {
                        $flow = ((int) $schedule->destination_account_id === $selectedAccountId) ? 'in' : 'out';
                    }

                    $dateStr = $cursor->toDateString();
                    $accountLabel = $isIncome
                        ? ($schedule->destinationAccount?->name ?? 'Kas / Bank')
                        : ($schedule->sourceAccount?->name ?? 'Kas / Bank');

                    $events[] = [
                        'id' => 'rec_'.$schedule->id.'_'.$cursor->format('Ymd'),
                        'date' => $dateStr,
                        'date_formatted' => $cursor->translatedFormat('d M Y'),
                        'day_name' => $cursor->translatedFormat('l'),
                        'source_type' => 'recurring',
                        'source_label' => $isIncome ? 'Pemasukan Rutin' : 'Tagihan Rutin',
                        'title' => $schedule->name,
                        'amount' => $amt,
                        'flow' => $flow,
                        'category_name' => $schedule->category?->name ?? ($isIncome ? 'Pemasukan' : 'Pengeluaran Rutin'),
                        'account_name' => $accountLabel,
                        'raw_id' => $schedule->id,
                        'detail_url' => route('recurring.show', $schedule->id),
                    ];

                    if ($flow === 'in') {
                        $breakdown['recurring_income'] += $amt;
                    } else {
                        $breakdown['recurring_expense'] += $amt;
                    }

                    $counts['recurring_count']++;
                }

                $cursor = $schedule->calculateNextDueDate($cursor);
                $occurrenceCount++;
            }
        }
    }

    /**
     * Ekstrak cicilan aktif yang jatuh tempo pada rentang horizon.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<string, float>  $breakdown
     * @param  array<string, int>  $counts
     */
    protected function extractInstallmentEvents(
        Space $space,
        string $startDateStr,
        string $endDateStr,
        ?int $selectedAccountId,
        array &$events,
        array &$breakdown,
        array &$counts
    ): void {
        $installments = Installment::query()
            ->where('space_id', $space->id)
            ->active()
            ->pending()
            ->whereBetween('due_date', [$startDateStr, $endDateStr])
            ->with(['installmentPlan.creditAccount'])
            ->get();

        foreach ($installments as $inst) {
            $amt = (float) $inst->total_amount;
            if ($amt <= 0) {
                continue;
            }

            $plan = $inst->installmentPlan;
            $planTitle = $plan?->name ?? 'Cicilan';
            $accountName = $plan?->creditAccount?->name ?? 'Kartu / PayLater';

            $dueDate = Carbon::parse($inst->due_date);

            $events[] = [
                'id' => 'inst_'.$inst->id,
                'date' => $dueDate->toDateString(),
                'date_formatted' => $dueDate->translatedFormat('d M Y'),
                'day_name' => $dueDate->translatedFormat('l'),
                'source_type' => 'installment',
                'source_label' => 'Cicilan Tempo',
                'title' => "Cicilan Ke-{$inst->installment_number} ({$planTitle})",
                'amount' => $amt,
                'flow' => 'out',
                'category_name' => 'Kewajiban Cicilan',
                'account_name' => $accountName,
                'raw_id' => $inst->id,
                'detail_url' => $plan ? route('credit.plans.show', $plan->id) : route('credit.index'),
            ];

            $breakdown['installments'] += $amt;
            $counts['installment_count']++;
        }
    }

    /**
     * Ekstrak seluruh rencana pemasukan aktif yang diperkirakan masuk pada rentang horizon.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<string, float>  $breakdown
     * @param  array<string, int>  $counts
     */
    protected function extractPlannedIncomeEvents(
        Space $space,
        string $startDateStr,
        string $endDateStr,
        ?int $selectedAccountId,
        array &$events,
        array &$breakdown,
        array &$counts
    ): void {
        $query = PlannedIncome::query()
            ->where('space_id', $space->id)
            ->planned()
            ->whereBetween('expected_date', [$startDateStr, $endDateStr])
            ->with(['account', 'category']);

        if ($selectedAccountId !== null) {
            $query->where(function ($q) use ($selectedAccountId) {
                $q->whereNull('account_id')
                    ->orWhere('account_id', $selectedAccountId);
            });
        }

        $plannedIncomes = $query->get();

        foreach ($plannedIncomes as $plan) {
            $amt = (float) $plan->amount;
            if ($amt <= 0) {
                continue;
            }

            $expectedDate = Carbon::parse($plan->expected_date);

            $events[] = [
                'id' => 'plan_in_'.$plan->id,
                'date' => $expectedDate->toDateString(),
                'date_formatted' => $expectedDate->translatedFormat('d M Y'),
                'day_name' => $expectedDate->translatedFormat('l'),
                'source_type' => 'planned_income',
                'source_label' => 'Rencana Pemasukan',
                'title' => $plan->title,
                'amount' => $amt,
                'flow' => 'in',
                'category_name' => $plan->category?->name ?? 'Pemasukan Terencana',
                'account_name' => $plan->account?->name ?? 'Rekening Terpilih',
                'raw_id' => $plan->id,
                'detail_url' => null,
            ];

            $breakdown['planned_incomes'] += $amt;
            $counts['planned_income_count']++;
        }
    }

    /**
     * Ekstrak Short Plan pengeluaran fleksibel yang direncanakan pada rentang horizon.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<string, float>  $breakdown
     * @param  array<string, int>  $counts
     */
    protected function extractShortPlanEvents(
        Space $space,
        string $startDateStr,
        string $endDateStr,
        ?int $selectedAccountId,
        array &$events,
        array &$breakdown,
        array &$counts
    ): void {
        $query = ShortPlan::query()
            ->where('space_id', $space->id)
            ->planned()
            ->whereNotNull('target_date')
            ->whereBetween('target_date', [$startDateStr, $endDateStr])
            ->with(['account', 'category']);

        if ($selectedAccountId !== null) {
            $query->where(function ($q) use ($selectedAccountId) {
                $q->whereNull('account_id')
                    ->orWhere('account_id', $selectedAccountId);
            });
        }

        $shortPlans = $query->get();

        foreach ($shortPlans as $plan) {
            $amt = (float) $plan->planned_amount;
            if ($amt <= 0) {
                continue;
            }

            $targetDate = Carbon::parse($plan->target_date);

            $events[] = [
                'id' => 'sp_'.$plan->id,
                'date' => $targetDate->toDateString(),
                'date_formatted' => $targetDate->translatedFormat('d M Y'),
                'day_name' => $targetDate->translatedFormat('l'),
                'source_type' => 'short_plan',
                'source_label' => 'Short Plan (Belanja)',
                'title' => $plan->title,
                'amount' => $amt,
                'flow' => 'out',
                'category_name' => $plan->category?->name ?? 'Rencana Pengeluaran',
                'account_name' => $plan->account?->name ?? 'Kas / Bank',
                'raw_id' => $plan->id,
                'detail_url' => route('short-plans.show', $plan->id),
            ];

            $breakdown['short_plans'] += $amt;
            $counts['short_plan_count']++;
        }
    }

    /**
     * Bangun deret titik harian untuk grafik visual proyeksi saldo dan arus masuk-keluar harian.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    protected function buildDailySeries(
        string $startDateStr,
        string $endDateStr,
        float $startingBalance,
        array $events,
        string $timezone
    ): array {
        // Kelompokkan event berdasarkan tanggal
        $eventsByDate = [];
        foreach ($events as $evt) {
            $eventsByDate[$evt['date']][] = $evt;
        }

        $startC = Carbon::parse($startDateStr, $timezone)->startOfDay();
        $endC = Carbon::parse($endDateStr, $timezone)->endOfDay();
        $diffDays = (int) $startC->diffInDays($endC) + 1;

        $series = [];
        $currentBal = $startingBalance;

        for ($i = 0; $i < $diffDays; $i++) {
            $day = $startC->copy()->addDays($i);
            $dayStr = $day->toDateString();

            $dayInflow = 0.0;
            $dayOutflow = 0.0;

            if (! empty($eventsByDate[$dayStr])) {
                foreach ($eventsByDate[$dayStr] as $ev) {
                    if ($ev['flow'] === 'in') {
                        $dayInflow += (float) $ev['amount'];
                    } else {
                        $dayOutflow += (float) $ev['amount'];
                    }
                }
            }

            $currentBal = round($currentBal + $dayInflow - $dayOutflow, 2);

            $series[] = [
                'date' => $dayStr,
                'label' => $day->translatedFormat('d M'),
                'full_label' => $day->translatedFormat('D, d M Y'),
                'inflow' => round($dayInflow, 2),
                'outflow' => round($dayOutflow, 2),
                'net' => round($dayInflow - $dayOutflow, 2),
                'balance' => $currentBal,
                'has_events' => ! empty($eventsByDate[$dayStr]),
                'events_count' => count($eventsByDate[$dayStr] ?? []),
            ];
        }

        return $series;
    }
}
