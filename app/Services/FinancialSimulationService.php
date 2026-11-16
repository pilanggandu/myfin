<?php

namespace App\Services;

use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialSimulationService
{
    /**
     * Hitung baseline keuangan riil dari ruang aktif (Arus Kas, Utang, Saldo Likuid).
     *
     * @return array{
     *     monthly_income: float,
     *     monthly_expense: float,
     *     monthly_debt: float,
     *     liquid_cash: float,
     *     free_cashflow: float,
     *     dsr: float,
     *     runway_months: float
     * }
     */
    public function getBaseline(Space $space): array
    {
        // 1. Saldo Kas & Aset Likuid Saat Ini
        $liquidCash = (float) $space->accounts()
            ->whereNull('archived_at')
            ->liquidAssets()
            ->get()
            ->sum(fn ($account) => $account->currentBalance());

        // 2. Rata-rata Pemasukan Bulanan (90 hari terakhir / 3 bulan)
        $threeMonthsAgo = Carbon::now($space->timezone ?: 'Asia/Jakarta')->subDays(90)->startOfDay();

        $incomeTotal90Days = (float) $space->financialTransactions()
            ->posted()
            ->income()
            ->where('transaction_date', '>=', $threeMonthsAgo->toDateString())
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->sum('transaction_entries.amount_signed');

        $monthlyIncome = $incomeTotal90Days > 0
            ? round($incomeTotal90Days / 3, 2)
            : (float) $space->plannedIncomes()->where('status', 'planned')->sum('amount');

        // 3. Rata-rata Pengeluaran Rutin Bulanan (90 hari terakhir, tidak termasuk bayar utang kartu kredit)
        $expenseTotal90Days = (float) $space->financialTransactions()
            ->posted()
            ->expense()
            ->where('transaction_date', '>=', $threeMonthsAgo->toDateString())
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->sum(DB::raw('ABS(transaction_entries.amount_signed)'));

        $monthlyExpense = $expenseTotal90Days > 0
            ? round($expenseTotal90Days / 3, 2)
            : 0.0;

        // 4. Beban Cicilan & Kewajiban Bulanan Berjalan
        $activePlans = $space->installmentPlans()
            ->with('pendingInstallments')
            ->active()
            ->get();

        $monthlyDebt = 0.0;
        foreach ($activePlans as $plan) {
            $pendingCount = $plan->pendingInstallments->count();
            if ($pendingCount > 0) {
                if ($plan->monthly_installment && (float) $plan->monthly_installment > 0) {
                    $monthlyDebt += (float) $plan->monthly_installment;
                } else {
                    $monthlyDebt += round((float) $plan->remainingTotalAmount() / $pendingCount, 2);
                }
            }
        }

        // 5. Arus Kas Bersih (Free Cashflow)
        $freeCashflow = round($monthlyIncome - $monthlyExpense - $monthlyDebt, 2);

        // 6. Debt Service Ratio (DSR)
        $dsr = $monthlyIncome > 0
            ? round(($monthlyDebt / $monthlyIncome) * 100, 1)
            : 0.0;

        // 7. Runway Dana Darurat (Bulan ketahanan pengeluaran jika pemasukan berhenti)
        $monthlyObligations = $monthlyExpense + $monthlyDebt;
        $runwayMonths = $monthlyObligations > 0
            ? round($liquidCash / $monthlyObligations, 1)
            : ($liquidCash > 0 ? 99.0 : 0.0);

        return [
            'monthly_income' => max(0.0, $monthlyIncome),
            'monthly_expense' => max(0.0, $monthlyExpense),
            'monthly_debt' => max(0.0, $monthlyDebt),
            'liquid_cash' => round($liquidCash, 2),
            'free_cashflow' => $freeCashflow,
            'dsr' => $dsr,
            'runway_months' => $runwayMonths,
        ];
    }

    /**
     * Jalankan simulasi keputusan keuangan berdasarkan baseline dan parameter skenario.
     *
     * @param  array<string, mixed>  $baseline
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function simulate(array $baseline, string $scenarioType, array $parameters): array
    {
        $bIncome = max(0.0, (float) ($baseline['monthly_income'] ?? 0));
        $bExpense = max(0.0, (float) ($baseline['monthly_expense'] ?? 0));
        $bDebt = max(0.0, (float) ($baseline['monthly_debt'] ?? 0));
        $bLiquidCash = (float) ($baseline['liquid_cash'] ?? 0);

        $upfrontOutflow = 0.0;
        $netCashReceived = 0.0;
        $monthlyDebtAddon = 0.0;
        $monthlyExpenseAddon = 0.0;
        $monthlySavingsAddon = 0.0;
        $totalInterest = 0.0;
        $totalCostOfDecision = 0.0;
        $scenarioDetails = [];

        switch ($scenarioType) {
            case 'asset_purchase':
                $price = max(0.0, (float) ($parameters['purchase_price'] ?? 0));
                $method = ($parameters['payment_method'] ?? 'credit') === 'cash' ? 'cash' : 'credit';
                $runningCost = max(0.0, (float) ($parameters['running_cost_monthly'] ?? 0));
                $monthlyExpenseAddon = $runningCost;

                if ($method === 'cash') {
                    $upfrontOutflow = $price;
                    $monthlyDebtAddon = 0.0;
                    $totalInterest = 0.0;
                    $totalCostOfDecision = $price;
                    $scenarioDetails = [
                        'method' => 'Tunai (Cash Lunas)',
                        'purchase_price' => $price,
                        'down_payment' => $price,
                        'loan_amount' => 0.0,
                        'tenor_months' => 0,
                        'monthly_installment' => 0.0,
                        'running_cost_monthly' => $runningCost,
                        'total_interest' => 0.0,
                        'total_cost' => $price,
                    ];
                } else {
                    $dp = max(0.0, (float) ($parameters['down_payment'] ?? 0));
                    $tenor = max(1, (int) ($parameters['tenor_months'] ?? 12));
                    $interestRatePa = max(0.0, (float) ($parameters['interest_rate_p_a'] ?? 0));
                    $manualInstallment = max(0.0, (float) ($parameters['monthly_installment'] ?? 0));

                    $loanPrincipal = max(0.0, $price - $dp);

                    if ($manualInstallment > 0) {
                        $monthlyDebtAddon = $manualInstallment;
                        $totalRepay = $manualInstallment * $tenor;
                        $totalInterest = max(0.0, $totalRepay - $loanPrincipal);
                    } else {
                        // Perhitungan bunga flat tahunan
                        $totalInterest = round($loanPrincipal * ($interestRatePa / 100) * ($tenor / 12), 2);
                        $monthlyDebtAddon = $tenor > 0
                            ? round(($loanPrincipal + $totalInterest) / $tenor, 2)
                            : 0.0;
                    }

                    $upfrontOutflow = $dp;
                    $totalCostOfDecision = $dp + ($monthlyDebtAddon * $tenor);

                    $scenarioDetails = [
                        'method' => 'Kredit / Cicilan',
                        'purchase_price' => $price,
                        'down_payment' => $dp,
                        'loan_amount' => $loanPrincipal,
                        'tenor_months' => $tenor,
                        'interest_rate_p_a' => $interestRatePa,
                        'monthly_installment' => $monthlyDebtAddon,
                        'running_cost_monthly' => $runningCost,
                        'total_interest' => $totalInterest,
                        'total_cost' => $totalCostOfDecision,
                    ];
                }
                break;

            case 'loan_installment':
                $loanAmount = max(0.0, (float) ($parameters['loan_amount'] ?? 0));
                $tenor = max(1, (int) ($parameters['tenor_months'] ?? 12));
                $interestRatePa = max(0.0, (float) ($parameters['interest_rate_p_a'] ?? 0));
                $adminFee = max(0.0, (float) ($parameters['admin_fee'] ?? 0));
                $adminDeducted = (bool) ($parameters['admin_fee_deducted'] ?? true);
                $manualInstallment = max(0.0, (float) ($parameters['monthly_installment'] ?? 0));

                if ($manualInstallment > 0) {
                    $monthlyDebtAddon = $manualInstallment;
                    $totalInterest = max(0.0, ($manualInstallment * $tenor) - $loanAmount);
                } else {
                    $totalInterest = round($loanAmount * ($interestRatePa / 100) * ($tenor / 12), 2);
                    $monthlyDebtAddon = $tenor > 0
                        ? round(($loanAmount + $totalInterest) / $tenor, 2)
                        : 0.0;
                }

                if ($adminDeducted) {
                    $upfrontOutflow = 0.0;
                    $netCashReceived = max(0.0, $loanAmount - $adminFee);
                } else {
                    $upfrontOutflow = $adminFee;
                    $netCashReceived = $loanAmount;
                }

                $totalCostOfDecision = ($monthlyDebtAddon * $tenor) + $adminFee;

                $scenarioDetails = [
                    'loan_amount' => $loanAmount,
                    'tenor_months' => $tenor,
                    'interest_rate_p_a' => $interestRatePa,
                    'admin_fee' => $adminFee,
                    'admin_fee_deducted' => $adminDeducted,
                    'net_cash_received' => $netCashReceived,
                    'monthly_installment' => $monthlyDebtAddon,
                    'total_interest' => $totalInterest,
                    'total_repayment' => ($monthlyDebtAddon * $tenor) + $adminFee,
                ];
                break;

            case 'saving_target':
                $targetAmount = max(0.0, (float) ($parameters['target_amount'] ?? 0));
                $targetMonths = max(1, (int) ($parameters['target_months'] ?? 12));
                $returnRatePa = max(0.0, (float) ($parameters['annual_return_rate'] ?? 0));
                $manualDeposit = max(0.0, (float) ($parameters['monthly_deposit'] ?? 0));

                if ($manualDeposit > 0) {
                    $monthlySavingsAddon = $manualDeposit;
                    // Estimasi bulan tercapai jika deposit manual
                    $estMonths = $returnRatePa > 0
                        ? $this->calculateMonthsToReachTarget($targetAmount, $manualDeposit, $returnRatePa)
                        : ($manualDeposit > 0 ? (int) ceil($targetAmount / $manualDeposit) : $targetMonths);
                    $targetMonths = max(1, $estMonths);
                } else {
                    // Hitung setoran bulanan yang dibutuhkan
                    if ($returnRatePa > 0) {
                        $monthlyRate = ($returnRatePa / 100) / 12;
                        $fvFactor = ((pow(1 + $monthlyRate, $targetMonths) - 1) / $monthlyRate) * (1 + $monthlyRate);
                        $monthlySavingsAddon = $fvFactor > 0 ? round($targetAmount / $fvFactor, 2) : round($targetAmount / $targetMonths, 2);
                    } else {
                        $monthlySavingsAddon = round($targetAmount / $targetMonths, 2);
                    }
                }

                $totalPrincipalSaved = round($monthlySavingsAddon * $targetMonths, 2);
                $estimatedReturn = max(0.0, round($targetAmount - $totalPrincipalSaved, 2));

                $totalCostOfDecision = $totalPrincipalSaved;

                $scenarioDetails = [
                    'target_amount' => $targetAmount,
                    'target_months' => $targetMonths,
                    'annual_return_rate' => $returnRatePa,
                    'monthly_deposit' => $monthlySavingsAddon,
                    'total_principal_saved' => $totalPrincipalSaved,
                    'estimated_return' => $estimatedReturn,
                ];
                break;
        }

        // Hitung dampak Sebelum vs Sesudah
        $beforeFreeCashflow = round($bIncome - $bExpense - $bDebt, 2);
        $beforeDsr = $bIncome > 0 ? round(($bDebt / $bIncome) * 100, 1) : 0.0;
        $beforeObligations = $bExpense + $bDebt;
        $beforeRunway = $beforeObligations > 0
            ? round($bLiquidCash / $beforeObligations, 1)
            : ($bLiquidCash > 0 ? 99.0 : 0.0);

        // Nilai sesudah keputusan
        $afterExpense = round($bExpense + $monthlyExpenseAddon, 2);
        $afterDebt = round($bDebt + $monthlyDebtAddon, 2);
        $afterLiquidCash = round(max(0.0, $bLiquidCash - $upfrontOutflow + $netCashReceived), 2);
        $afterFreeCashflow = round($bIncome - $afterExpense - $afterDebt - $monthlySavingsAddon, 2);
        $afterDsr = $bIncome > 0 ? round(($afterDebt / $bIncome) * 100, 1) : 0.0;
        $afterObligations = $afterExpense + $afterDebt;
        $afterRunway = $afterObligations > 0
            ? round($afterLiquidCash / $afterObligations, 1)
            : ($afterLiquidCash > 0 ? 99.0 : 0.0);

        // Deltas
        $deltaLiquidCash = round($afterLiquidCash - $bLiquidCash, 2);
        $deltaMonthlyDebt = round($afterDebt - $bDebt, 2);
        $deltaFreeCashflow = round($afterFreeCashflow - $beforeFreeCashflow, 2);
        $deltaDsr = round($afterDsr - $beforeDsr, 1);
        $deltaRunway = round($afterRunway - $beforeRunway, 1);

        // Evaluasi Kesehatan & Kelayakan Keputusan
        $evaluation = $this->evaluateDecision(
            scenarioType: $scenarioType,
            beforeLiquidCash: $bLiquidCash,
            afterLiquidCash: $afterLiquidCash,
            afterDsr: $afterDsr,
            afterFreeCashflow: $afterFreeCashflow,
            afterRunway: $afterRunway,
            monthlyDebtAddon: $monthlyDebtAddon,
            monthlySavingsAddon: $monthlySavingsAddon,
            upfrontOutflow: $upfrontOutflow
        );

        return [
            'scenario_type' => $scenarioType,
            'scenario_details' => $scenarioDetails,
            'summary' => [
                'upfront_outflow' => $upfrontOutflow,
                'net_cash_received' => $netCashReceived,
                'monthly_debt_addon' => $monthlyDebtAddon,
                'monthly_expense_addon' => $monthlyExpenseAddon,
                'monthly_savings_addon' => $monthlySavingsAddon,
                'total_monthly_impact' => round($monthlyDebtAddon + $monthlyExpenseAddon + $monthlySavingsAddon, 2),
                'total_cost_of_decision' => $totalCostOfDecision,
            ],
            'comparison' => [
                'before' => [
                    'income' => $bIncome,
                    'expense' => $bExpense,
                    'debt' => $bDebt,
                    'liquid_cash' => $bLiquidCash,
                    'free_cashflow' => $beforeFreeCashflow,
                    'dsr' => $beforeDsr,
                    'runway_months' => $beforeRunway,
                ],
                'after' => [
                    'income' => $bIncome,
                    'expense' => $afterExpense,
                    'debt' => $afterDebt,
                    'savings_addon' => $monthlySavingsAddon,
                    'liquid_cash' => $afterLiquidCash,
                    'free_cashflow' => $afterFreeCashflow,
                    'dsr' => $afterDsr,
                    'runway_months' => $afterRunway,
                ],
                'delta' => [
                    'liquid_cash' => $deltaLiquidCash,
                    'monthly_debt' => $deltaMonthlyDebt,
                    'free_cashflow' => $deltaFreeCashflow,
                    'dsr' => $deltaDsr,
                    'runway_months' => $deltaRunway,
                ],
            ],
            'health_status' => $evaluation['status'], // safe, warning, danger
            'health_score' => $evaluation['score'], // 0 - 100
            'verdict_title' => $evaluation['title'],
            'verdict_summary' => $evaluation['summary'],
            'recommendations' => $evaluation['recommendations'],
            'considerations' => $evaluation['considerations'],
        ];
    }

    /**
     * Evaluasi skor kesehatan dan rekomendasi finansial.
     *
     * @return array{
     *     status: string,
     *     score: int,
     *     title: string,
     *     summary: string,
     *     recommendations: list<string>,
     *     considerations: list<string>
     * }
     */
    protected function evaluateDecision(
        string $scenarioType,
        float $beforeLiquidCash,
        float $afterLiquidCash,
        float $afterDsr,
        float $afterFreeCashflow,
        float $afterRunway,
        float $monthlyDebtAddon,
        float $monthlySavingsAddon,
        float $upfrontOutflow
    ): array {
        $recommendations = [];
        $considerations = [];
        $score = 100;

        // 1. Cek Arus Kas Bersih (Free Cashflow)
        if ($afterFreeCashflow < 0) {
            $deficit = abs($afterFreeCashflow);
            $score -= 40;
            $recommendations[] = 'Arus kas bulanan akan mengalami defisit sebesar Rp '.number_format($deficit, 0, ',', '.').'/bulan. Anda wajib memangkas pos pengeluaran lain atau mencari sumber pemasukan tambahan sebelum mengambil komitmen ini.';
            $considerations[] = 'Defisit kas rutin dapat memaksa Anda berutang gali lubang tutup lubang atau menguras tabungan setiap bulan.';
        } elseif ($afterFreeCashflow < 500000) {
            $score -= 15;
            $recommendations[] = 'Sisa arus kas bebas sangat tipis (di bawah Rp 500.000/bulan). Sisakan buffer yang lebih longgar untuk kebutuhan tidak terduga.';
        }

        // 2. Cek Debt Service Ratio (DSR)
        if ($afterDsr > 40) {
            $score -= 35;
            $recommendations[] = "Rasio cicilan (DSR) melonjak hingga {$afterDsr}% (melebihi batas aman perbankan 30%). Tingkat utang ini sangat berisiko membebani ruang gerak finansial Anda.";
            $considerations[] = 'Pertimbangkan memperpanjang tenor kredit atau memperbesar DP agar cicilan bulanan turun di bawah 30% pemasukan.';
        } elseif ($afterDsr > 30) {
            $score -= 15;
            $recommendations[] = "Rasio cicilan (DSR) berada di angka {$afterDsr}% (zona waspada 30-40%). Pastikan tidak ada utang konsumtif baru lainnya.";
        }

        // 3. Cek Ketahanan Dana Darurat (Runway)
        if ($afterRunway < 1.0) {
            $score -= 25;
            $recommendations[] = "Kas likuid tersisa hanya bertahan {$afterRunway} bulan jika terjadi krisis pendapatan. Pertahankan dana darurat minimal 3 bulan sebelum mengeluarkan DP besar.";
            $considerations[] = 'Pengeluaran dana awal (DP/Tunai) menguras bantalan keamanan finansial keluarga Anda.';
        } elseif ($afterRunway < 3.0) {
            $score -= 10;
            $recommendations[] = "Dana cadangan tersisa {$afterRunway} bulan pengeluaran. Idealnya siapkan minimal 3 hingga 6 bulan pengeluaran rutin sebagai dana darurat.";
        }

        // Khusus skenario
        if ($scenarioType === 'asset_purchase' && $upfrontOutflow > ($beforeLiquidCash * 0.7) && $beforeLiquidCash > 0) {
            $considerations[] = 'Membeli secara tunai atau membayar DP ini menghabiskan lebih dari 70% saldo kas likuid Anda saat ini.';
        }

        if ($scenarioType === 'saving_target') {
            if ($afterFreeCashflow >= 0) {
                $recommendations[] = 'Target kenaikan tabungan ini sangat positif untuk masa depan dan arus kas Anda tetap aman surplus.';
            }
        }

        $score = max(0, min(100, $score));

        if ($afterFreeCashflow < 0 || $afterDsr > 45 || $afterRunway < 0.5) {
            $status = 'danger';
            $title = 'Berisiko Tinggi / Tidak Disarankan';
            $summary = 'Keputusan ini berpotensi menyebabkan defisit arus kas bulanan atau beban utang yang melampaui batas kemampuan finansial.';
        } elseif ($score < 75 || $afterDsr > 30 || $afterRunway < 3.0) {
            $status = 'warning';
            $title = 'Cukup Layak dengan Penyesuaian';
            $summary = 'Keputusan ini masih dapat direalisasikan, namun menyempitkan ruang gerak kas dan menuntut disiplin anggaran yang ketat.';
        } else {
            $status = 'safe';
            $title = 'Sangat Layak & Aman Secara Finansial';
            $summary = 'Kondisi arus kas, rasio cicilan (DSR), dan dana darurat Anda berada dalam rentang sehat untuk mengeksekusi rencana ini.';
        }

        return [
            'status' => $status,
            'score' => $score,
            'title' => $title,
            'summary' => $summary,
            'recommendations' => $recommendations,
            'considerations' => $considerations,
        ];
    }

    /**
     * Estimasi bulan yang dibutuhkan untuk mencapai target tabungan dengan imbal hasil majemuk.
     */
    protected function calculateMonthsToReachTarget(float $target, float $deposit, float $annualReturnRate): int
    {
        if ($deposit <= 0) {
            return 999;
        }

        $monthlyRate = ($annualReturnRate / 100) / 12;
        $balance = 0.0;
        $months = 0;

        while ($balance < $target && $months < 360) {
            $months++;
            $balance = ($balance + $deposit) * (1 + $monthlyRate);
        }

        return $months;
    }
}
