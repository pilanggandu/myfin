<?php

namespace App\Services;

use App\Models\Space;
use Carbon\Carbon;

class DebtPayoffPlannerService
{
    /**
     * Mengumpulkan semua utang dan cicilan aktif dari ruang yang dipilih.
     *
     * @return list<array<string, mixed>>
     */
    public function collectDebts(Space $space): array
    {
        $debts = [];

        // 1. Ambil semua Rencana Cicilan Aktif yang memiliki angsuran pending
        $activePlans = $space->installmentPlans()
            ->with(['creditAccount', 'category', 'pendingInstallments'])
            ->active()
            ->get();

        foreach ($activePlans as $plan) {
            $pendingInstallments = $plan->pendingInstallments;
            if ($pendingInstallments->isEmpty()) {
                continue;
            }

            $remainingPrincipal = (float) $plan->remainingPrincipal();
            if ($remainingPrincipal <= 0) {
                continue;
            }

            $remainingTotal = (float) $plan->remainingTotalAmount();
            $pendingCount = $pendingInstallments->count();

            // Rata-rata angsuran bulanan yang harus dibayar
            $avgMonthlyPayment = $pendingCount > 0
                ? round($remainingTotal / $pendingCount, 2)
                : $remainingPrincipal;

            // Suku bunga tahunan (APR)
            $interestRate = (float) ($plan->interest_rate ?? 0);
            if ($interestRate <= 0 && $plan->totalInterest() > 0 && $plan->total_amount > 0) {
                // Perkirakan suku bunga tahunan dari total bunga dibagi tenor
                $tenor = max(1, (int) $plan->tenor);
                $interestRate = round(($plan->totalInterest() / $plan->total_amount) * (12 / $tenor) * 100, 2);
            }

            $earliestDueDate = $pendingInstallments->min('due_date');
            $latestDueDate = $pendingInstallments->max('due_date');

            $debts[] = [
                'id' => 'plan_'.$plan->id,
                'name' => $plan->name,
                'account_name' => $plan->creditAccount?->name ?? 'Cicilan Mandiri',
                'account_type' => $plan->creditAccount?->type ?? 'installment',
                'category_name' => $plan->category?->name ?? 'Kewajiban',
                'remaining_principal' => $remainingPrincipal,
                'remaining_total' => $remainingTotal,
                'remaining_tenor' => $pendingCount,
                'monthly_min_payment' => $avgMonthlyPayment,
                'annual_interest_rate' => $interestRate,
                'monthly_interest_rate' => round($interestRate / 12, 4),
                'total_interest_remaining' => (float) $pendingInstallments->sum('interest_amount'),
                'total_fees_remaining' => (float) $pendingInstallments->sum('fee_amount'),
                'earliest_due_date' => $earliestDueDate ? Carbon::parse($earliestDueDate)->toDateString() : null,
                'latest_due_date' => $latestDueDate ? Carbon::parse($latestDueDate)->toDateString() : null,
                'source_type' => 'installment_plan',
                'source_id' => $plan->id,
            ];
        }

        // 2. Ambil Akun Kredit aktif yang memiliki saldo utang non-cicilan (revolving credit card debt)
        $creditAccounts = $space->creditAccounts()
            ->whereNull('archived_at')
            ->get();

        foreach ($creditAccounts as $acc) {
            $nonInstallmentDebt = (float) $acc->nonInstallmentLiability();
            if ($nonInstallmentDebt <= 100) {
                continue;
            }

            // Estimasi pembayaran minimum kartu kredit: 5% - 10% atau minimal Rp 50.000
            $minPayment = max(50000.0, round($nonInstallmentDebt * 0.05, 2));
            $estimatedTenor = max(1, (int) ceil($nonInstallmentDebt / $minPayment));

            // Suku bunga kartu kredit standar Indonesia: ~21% per tahun (1.75% per bulan)
            $annualRate = 21.0;

            $debts[] = [
                'id' => 'acc_'.$acc->id,
                'name' => 'Tagihan Berjalan '.$acc->name,
                'account_name' => $acc->name,
                'account_type' => $acc->type,
                'category_name' => 'Kartu Kredit / PayLater',
                'remaining_principal' => $nonInstallmentDebt,
                'remaining_total' => $nonInstallmentDebt,
                'remaining_tenor' => $estimatedTenor,
                'monthly_min_payment' => $minPayment,
                'annual_interest_rate' => $annualRate,
                'monthly_interest_rate' => 1.75,
                'total_interest_remaining' => 0.0,
                'total_fees_remaining' => 0.0,
                'earliest_due_date' => now()->addMonth()->toDateString(),
                'latest_due_date' => now()->addMonths($estimatedTenor)->toDateString(),
                'source_type' => 'credit_account',
                'source_id' => $acc->id,
            ];
        }

        return $debts;
    }

    /**
     * Hitung perbandingan 3 strategi pelunasan utang: Standard, Avalanche, dan Snowball.
     *
     * @param  list<array<string, mixed>>  $debts
     * @return array<string, mixed>
     */
    public function calculateStrategies(
        array $debts,
        float $extraMonthlyPayment = 0.0,
        float $oneTimePayment = 0.0,
        ?Carbon $startDate = null
    ): array {
        $start = $startDate ?? now()->startOfMonth();

        if (empty($debts)) {
            return [
                'has_debts' => false,
                'total_debt_amount' => 0.0,
                'total_monthly_min' => 0.0,
                'debts_count' => 0,
                'extra_monthly' => $extraMonthlyPayment,
                'one_time_payment' => $oneTimePayment,
                'strategies' => [
                    'standard' => $this->emptyStrategyResult('Standard (Minimum Saja)'),
                    'avalanche' => $this->emptyStrategyResult('Debt Avalanche (Bunga Tertinggi)'),
                    'snowball' => $this->emptyStrategyResult('Debt Snowball (Saldo Terkecil)'),
                ],
                'comparison' => [
                    'avalanche_vs_standard' => ['interest_saved' => 0.0, 'months_saved' => 0],
                    'snowball_vs_standard' => ['interest_saved' => 0.0, 'months_saved' => 0],
                    'avalanche_vs_snowball' => ['interest_difference' => 0.0, 'months_difference' => 0],
                ],
            ];
        }

        $totalDebt = (float) array_sum(array_column($debts, 'remaining_principal'));
        $totalMonthlyMin = (float) array_sum(array_column($debts, 'monthly_min_payment'));

        // 1. Simulasikan Strategi Standard (Tanpa dana ekstra, tanpa rollover)
        $standard = $this->simulateStrategy($debts, 'standard', 0.0, 0.0, $start);

        // 2. Simulasikan Strategi Avalanche (Bunga tertinggi dulu + dana ekstra + rollover)
        $avalanche = $this->simulateStrategy($debts, 'avalanche', $extraMonthlyPayment, $oneTimePayment, $start);

        // 3. Simulasikan Strategi Snowball (Saldo terkecil dulu + dana ekstra + rollover)
        $snowball = $this->simulateStrategy($debts, 'snowball', $extraMonthlyPayment, $oneTimePayment, $start);

        // Hitung penghematan
        $avalancheInterestSaved = max(0.0, $standard['total_interest_paid'] - $avalanche['total_interest_paid']);
        $avalancheMonthsSaved = max(0, $standard['total_months'] - $avalanche['total_months']);

        $snowballInterestSaved = max(0.0, $standard['total_interest_paid'] - $snowball['total_interest_paid']);
        $snowballMonthsSaved = max(0, $standard['total_months'] - $snowball['total_months']);

        $avalancheAdvantage = max(0.0, $snowball['total_interest_paid'] - $avalanche['total_interest_paid']);

        return [
            'has_debts' => true,
            'total_debt_amount' => $totalDebt,
            'total_monthly_min' => $totalMonthlyMin,
            'total_budget_avalanche' => $totalMonthlyMin + $extraMonthlyPayment,
            'debts_count' => count($debts),
            'extra_monthly' => $extraMonthlyPayment,
            'one_time_payment' => $oneTimePayment,
            'debts' => $debts,
            'strategies' => [
                'standard' => $standard,
                'avalanche' => $avalanche,
                'snowball' => $snowball,
            ],
            'comparison' => [
                'avalanche_vs_standard' => [
                    'interest_saved' => round($avalancheInterestSaved, 2),
                    'months_saved' => $avalancheMonthsSaved,
                ],
                'snowball_vs_standard' => [
                    'interest_saved' => round($snowballInterestSaved, 2),
                    'months_saved' => $snowballMonthsSaved,
                ],
                'avalanche_vs_snowball' => [
                    'interest_difference' => round($avalancheAdvantage, 2),
                    'months_difference' => $snowball['total_months'] - $avalanche['total_months'],
                ],
            ],
            'recommendation' => [
                'financial_winner' => 'avalanche',
                'avalanche_summary' => 'Strategi Avalanche menghemat bunga sebesar Rp '.number_format($avalancheInterestSaved, 0, ',', '.')." dan lunas {$avalancheMonthsSaved} bulan lebih cepat dibandingkan jadwal reguler.",
                'snowball_summary' => 'Strategi Snowball menghemat bunga sebesar Rp '.number_format($snowballInterestSaved, 0, ',', '.').' dan memberikan kemenangan psikologis dengan melunasi pos utang terkecil terlebih dahulu.',
            ],
        ];
    }

    /**
     * Mensimulasikan jadwal amortisasi bulan demi bulan untuk satu strategi tertentu.
     *
     * @param  list<array<string, mixed>>  $debts
     * @return array<string, mixed>
     */
    protected function simulateStrategy(
        array $debts,
        string $strategyName,
        float $extraMonthly,
        float $oneTimeLumpSum,
        Carbon $startDate
    ): array {
        // Buat kloningan data utang yang bisa dimutasi selama simulasi
        $items = [];
        foreach ($debts as $d) {
            $items[$d['id']] = [
                'id' => $d['id'],
                'name' => $d['name'],
                'balance' => (float) $d['remaining_principal'],
                'min_payment' => (float) $d['monthly_min_payment'],
                'annual_rate' => (float) $d['annual_interest_rate'],
                'monthly_rate' => ((float) $d['annual_interest_rate']) / 100 / 12,
                'paid_off_month' => null,
                'total_interest_paid' => 0.0,
                'total_principal_paid' => 0.0,
            ];
        }

        $monthlySchedule = [];
        $payoffOrder = [];
        $totalInterestPaid = 0.0;
        $totalPrincipalPaid = 0.0;
        $maxMonths = 360; // Batas pengaman 30 tahun

        $currentDate = $startDate->copy();
        $month = 0;

        // Tentukan urutan prioritas penyerangan utang berdasarkan strategi
        $targetPriorityOrder = [];
        $sortedForPriority = $items;
        if ($strategyName === 'avalanche') {
            uasort($sortedForPriority, function ($a, $b) {
                if (abs($b['annual_rate'] - $a['annual_rate']) > 0.001) {
                    return $b['annual_rate'] <=> $a['annual_rate'];
                }

                return $a['balance'] <=> $b['balance'];
            });
        } elseif ($strategyName === 'snowball') {
            uasort($sortedForPriority, function ($a, $b) {
                if (abs($a['balance'] - $b['balance']) > 0.001) {
                    return $a['balance'] <=> $b['balance'];
                }

                return $b['annual_rate'] <=> $a['annual_rate'];
            });
        }

        $rank = 1;
        foreach ($sortedForPriority as $id => $item) {
            $targetPriorityOrder[] = [
                'rank' => $rank++,
                'id' => $id,
                'name' => $item['name'],
                'balance' => $item['balance'],
                'annual_rate' => $item['annual_rate'],
                'min_payment' => $item['min_payment'],
            ];
        }

        // Jika ada pembayaran sekaligus (one-time lump sum) di awal
        if ($oneTimeLumpSum > 0 && $strategyName !== 'standard') {
            $priorityDebtId = $this->determinePriorityDebt($items, $strategyName);
            if ($priorityDebtId && isset($items[$priorityDebtId])) {
                $payAmount = min($oneTimeLumpSum, $items[$priorityDebtId]['balance']);
                $items[$priorityDebtId]['balance'] -= $payAmount;
                $items[$priorityDebtId]['total_principal_paid'] += $payAmount;
                $totalPrincipalPaid += $payAmount;

                if ($items[$priorityDebtId]['balance'] <= 0.01) {
                    $items[$priorityDebtId]['balance'] = 0.0;
                    $items[$priorityDebtId]['paid_off_month'] = 0;
                    $payoffOrder[] = [
                        'id' => $priorityDebtId,
                        'name' => $items[$priorityDebtId]['name'],
                        'month' => 0,
                        'date' => $currentDate->format('M Y'),
                    ];
                }
            }
        }

        while ($this->hasUnpaidDebts($items) && $month < $maxMonths) {
            $month++;
            $monthDate = $currentDate->copy()->addMonths($month - 1);

            $monthInterest = 0.0;
            $monthPrincipal = 0.0;
            $debtsPaidThisMonth = [];

            // 1. Akumulasi bunga bulan berjalan untuk semua utang yang masih ada saldo
            foreach ($items as $id => &$debt) {
                if ($debt['balance'] > 0) {
                    $interest = round($debt['balance'] * $debt['monthly_rate'], 2);
                    $debt['total_interest_paid'] += $interest;
                    $totalInterestPaid += $interest;
                    $monthInterest += $interest;
                    $debt['balance'] += $interest;
                }
            }
            unset($debt);

            // 2. Pembayaran minimum wajib untuk setiap utang aktif
            foreach ($items as $id => &$debt) {
                if ($debt['balance'] > 0) {
                    $payment = min($debt['min_payment'], $debt['balance']);
                    $debt['balance'] -= $payment;
                    $debt['total_principal_paid'] += $payment;
                    $totalPrincipalPaid += $payment;
                    $monthPrincipal += $payment;

                    if ($debt['balance'] <= 0.01) {
                        $debt['balance'] = 0.0;
                        $debt['paid_off_month'] = $month;
                        $debtsPaidThisMonth[] = $debt['name'];
                        $payoffOrder[] = [
                            'id' => $id,
                            'name' => $debt['name'],
                            'month' => $month,
                            'date' => $monthDate->translatedFormat('F Y'),
                        ];
                    }
                }
            }
            unset($debt);

            // 3. Alokasikan dana ekstra & rollover cicilan utang yang telah lunas
            if ($strategyName !== 'standard') {
                $rolloverPool = 0.0;
                foreach ($items as $id => $debt) {
                    if ($debt['balance'] <= 0) {
                        $rolloverPool += $debt['min_payment'];
                    }
                }

                $availableExtra = $extraMonthly + $rolloverPool;

                while ($availableExtra > 0.01 && $this->hasUnpaidDebts($items)) {
                    $targetId = $this->determinePriorityDebt($items, $strategyName);
                    if (! $targetId || ! isset($items[$targetId])) {
                        break;
                    }

                    $extraToApply = min($availableExtra, $items[$targetId]['balance']);
                    $items[$targetId]['balance'] -= $extraToApply;
                    $items[$targetId]['total_principal_paid'] += $extraToApply;
                    $totalPrincipalPaid += $extraToApply;
                    $monthPrincipal += $extraToApply;
                    $availableExtra -= $extraToApply;

                    if ($items[$targetId]['balance'] <= 0.01) {
                        $items[$targetId]['balance'] = 0.0;
                        $items[$targetId]['paid_off_month'] = $month;
                        if (! in_array($items[$targetId]['name'], $debtsPaidThisMonth, true)) {
                            $debtsPaidThisMonth[] = $items[$targetId]['name'];
                        }
                        $payoffOrder[] = [
                            'id' => $targetId,
                            'name' => $items[$targetId]['name'],
                            'month' => $month,
                            'date' => $monthDate->translatedFormat('F Y'),
                        ];
                    }
                }
            }

            $remainingTotal = (float) array_sum(array_column($items, 'balance'));

            // Catat jadwal bulan ini (ambil 60 bulan pertama untuk efisiensi transfer data)
            if ($month <= 60 || ! $this->hasUnpaidDebts($items)) {
                $monthlySchedule[] = [
                    'month_index' => $month,
                    'date_formatted' => $monthDate->translatedFormat('M Y'),
                    'principal_paid' => round($monthPrincipal, 2),
                    'interest_paid' => round($monthInterest, 2),
                    'total_paid' => round($monthPrincipal + $monthInterest, 2),
                    'remaining_debt' => round($remainingTotal, 2),
                    'paid_off_debts' => $debtsPaidThisMonth,
                ];
            }
        }

        $finalDate = $startDate->copy()->addMonths($month);

        return [
            'strategy' => $strategyName,
            'title' => $this->getStrategyTitle($strategyName),
            'total_months' => $month,
            'debt_free_date' => $finalDate->translatedFormat('F Y'),
            'total_interest_paid' => round($totalInterestPaid, 2),
            'total_principal_paid' => round($totalPrincipalPaid, 2),
            'total_amount_paid' => round($totalPrincipalPaid + $totalInterestPaid, 2),
            'target_priority_order' => $targetPriorityOrder,
            'payoff_order' => $payoffOrder,
            'schedule' => $monthlySchedule,
            'items' => $items,
        ];
    }

    /**
     * Memilih utang yang diprioritaskan untuk menerima pembayaran ekstra.
     *
     * @param  array<string, array<string, mixed>>  $items
     */
    protected function determinePriorityDebt(array $items, string $strategy): ?string
    {
        $activeDebts = array_filter($items, fn ($d) => $d['balance'] > 0.01);
        if (empty($activeDebts)) {
            return null;
        }

        if ($strategy === 'avalanche') {
            // Prioritas: Suku bunga tahunan tertinggi. Jika sama, saldo terkecil.
            uasort($activeDebts, function ($a, $b) {
                if (abs($b['annual_rate'] - $a['annual_rate']) > 0.001) {
                    return $b['annual_rate'] <=> $a['annual_rate'];
                }

                return $a['balance'] <=> $b['balance'];
            });
        } elseif ($strategy === 'snowball') {
            // Prioritas: Saldo sisa terkecil. Jika sama, suku bunga tertinggi.
            uasort($activeDebts, function ($a, $b) {
                if (abs($a['balance'] - $b['balance']) > 0.001) {
                    return $a['balance'] <=> $b['balance'];
                }

                return $b['annual_rate'] <=> $a['annual_rate'];
            });
        }

        return array_key_first($activeDebts);
    }

    /**
     * Cek apakah masih ada utang yang belum lunas.
     *
     * @param  array<string, array<string, mixed>>  $items
     */
    protected function hasUnpaidDebts(array $items): bool
    {
        foreach ($items as $item) {
            if ($item['balance'] > 0.01) {
                return true;
            }
        }

        return false;
    }

    /**
     * Label strategi ramah pengguna.
     */
    protected function getStrategyTitle(string $strategy): string
    {
        return match ($strategy) {
            'avalanche' => 'Debt Avalanche (Fokus Bunga Tertinggi)',
            'snowball' => 'Debt Snowball (Fokus Saldo Terkecil)',
            'standard' => 'Pembayaran Minimum / Jadwal Standar',
            default => 'Strategi Pelunasan',
        };
    }

    /**
     * Hasil strategi kosong saat tidak ada utang.
     *
     * @return array<string, mixed>
     */
    protected function emptyStrategyResult(string $title): array
    {
        return [
            'strategy' => 'none',
            'title' => $title,
            'total_months' => 0,
            'debt_free_date' => now()->translatedFormat('F Y'),
            'total_interest_paid' => 0.0,
            'total_principal_paid' => 0.0,
            'total_amount_paid' => 0.0,
            'target_priority_order' => [],
            'payoff_order' => [],
            'schedule' => [],
            'items' => [],
        ];
    }
}
