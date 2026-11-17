<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CreditAccount;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NetWorthAnalysisService
{
    /**
     * Neraca kekayaan bersih saat ini (Aset, Kewajiban, Rasio D/A, Breakdown).
     *
     * @return array<string, mixed>
     */
    public function getCurrentBalanceSheet(Space $space): array
    {
        $accounts = $space->activeAccounts()->get();

        // Ambil saldo berjalan dari semua transaksi posted
        $entrySums = DB::table('transaction_entries')
            ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
            ->where('financial_transactions.space_id', $space->id)
            ->where('financial_transactions.status', 'posted')
            ->groupBy('transaction_entries.account_id')
            ->select('transaction_entries.account_id', DB::raw('SUM(transaction_entries.amount_signed) as total_signed'))
            ->pluck('total_signed', 'account_id');

        $liquidCash = 0.0;
        $investmentAssets = 0.0;
        $assetComposition = [];

        foreach ($accounts as $acc) {
            if (! in_array($acc->type, ['bank', 'e-wallet', 'cash', 'investment'], true)) {
                continue;
            }

            $bal = round((float) $acc->opening_balance + (float) ($entrySums[$acc->id] ?? 0), 2);
            if ($acc->type === 'investment') {
                $investmentAssets += $bal;
            } else {
                $liquidCash += $bal;
            }

            $assetComposition[] = [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type,
                'type_label' => $this->accountTypeLabel($acc->type),
                'balance' => $bal,
            ];
        }

        $grossAssets = round($liquidCash + $investmentAssets, 2);

        // Urutkan aset dari terbesar ke terkecil & hitung persentase
        usort($assetComposition, fn ($a, $b) => $b['balance'] <=> $a['balance']);
        foreach ($assetComposition as &$item) {
            $item['percentage'] = $grossAssets > 0 ? round(($item['balance'] / $grossAssets) * 100, 1) : 0.0;
        }
        unset($item);

        // Kewajiban: Kartu Kredit & Paylater
        $creditAccounts = $space->creditAccounts()->active()->with(['ledgerAccount', 'creditFacility'])->get();
        $creditCardDebt = 0.0;
        $totalCreditLimit = (float) $space->totalCreditLimit();
        $creditCardComposition = [];

        foreach ($creditAccounts as $ca) {
            $liab = round((float) $ca->currentLiability(), 2);
            $limit = round((float) $ca->effectiveLimit(), 2);
            $creditCardDebt += $liab;

            $creditCardComposition[] = [
                'id' => $ca->id,
                'name' => $ca->name,
                'type' => $ca->type,
                'liability' => $liab,
                'credit_limit' => $limit,
                'utilization' => $ca->utilizationRate(),
            ];
        }

        // Kewajiban: Cicilan Berjalan (Installment Plans)
        $activePlans = $space->installmentPlans()
            ->active()
            ->with(['category', 'creditAccount'])
            ->get();

        $installmentDebt = 0.0;
        $installmentComposition = [];

        foreach ($activePlans as $plan) {
            $remainingPrincipal = (float) $plan->remainingPrincipal();
            $installmentDebt += $remainingPrincipal;

            $pendingCount = $plan->pendingInstallments()->count();
            $installmentComposition[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'category_name' => $plan->category?->name ?? 'Umum',
                'credit_account_name' => $plan->creditAccount?->name ?? 'Pinjaman',
                'monthly_installment' => (float) ($plan->monthly_installment ?? ($pendingCount > 0 ? $plan->remainingTotalAmount() / $pendingCount : 0)),
                'remaining_principal' => $remainingPrincipal,
                'remaining_tenor' => $pendingCount,
                'total_amount' => (float) $plan->total_amount,
            ];
        }

        $grossLiabilities = round($creditCardDebt, 2);
        // Catatan: Jika ada rencana cicilan non-kartu kredit atau jika installmentPrincipal sudah termasuk dalam currentLiability kartu kredit
        // Di CreditAccount::currentLiability(), pendingPrincipal kartu kredit sudah dihitung di dalamnya.
        // Untuk cicilan yang TIDAK terhubung ke creditAccount (credit_account_id is null):
        $unlinkedInstallmentDebt = (float) InstallmentPlan::query()
            ->where('space_id', $space->id)
            ->active()
            ->whereNull('credit_account_id')
            ->get()
            ->sum(fn ($p) => $p->remainingPrincipal());

        $totalLiabilities = round($creditCardDebt + $unlinkedInstallmentDebt, 2);
        $netWorth = round($grossAssets - $totalLiabilities, 2);

        $creditUtilization = $totalCreditLimit > 0
            ? round(($creditCardDebt / $totalCreditLimit) * 100, 1)
            : null;

        $debtToAssetRatio = $grossAssets > 0
            ? round(($totalLiabilities / $grossAssets) * 100, 1)
            : null;

        $solvencyStatus = $this->determineSolvencyStatus($grossAssets, $totalLiabilities, $netWorth, $debtToAssetRatio);

        return [
            'liquid_cash' => $liquidCash,
            'investment_assets' => $investmentAssets,
            'gross_assets' => $grossAssets,
            'credit_card_debt' => $creditCardDebt,
            'installment_debt' => $installmentDebt,
            'total_credit_limit' => $totalCreditLimit,
            'total_liabilities' => $totalLiabilities,
            'net_worth' => $netWorth,
            'credit_utilization' => $creditUtilization,
            'debt_to_asset_ratio' => $debtToAssetRatio,
            'solvency_status' => $solvencyStatus,
            'asset_composition' => $assetComposition,
            'credit_card_composition' => $creditCardComposition,
            'installment_composition' => $installmentComposition,
        ];
    }

    /**
     * Hitung tren historis kekayaan bersih, aset, dan riwayat utang dari waktu ke waktu.
     *
     * @return array<string, mixed>
     */
    public function getHistoricalNetWorthTrend(
        Space $space,
        int $horizonMonths = 6,
        string $periodType = 'month',
        ?int $anchorYear = null,
        ?int $anchorMonth = null
    ): array {
        $timezone = $space->timezone ?: 'Asia/Jakarta';
        $now = $space->now();

        $horizonMonths = in_array($horizonMonths, [3, 6, 12, 24], true) ? $horizonMonths : 6;

        $anchor = ($anchorYear && $anchorMonth)
            ? Carbon::createFromDate($anchorYear, $anchorMonth, 1, $timezone)->endOfMonth()
            : $now->copy()->endOfMonth();

        // Susun daftar bulan mundur dari anchor
        $monthsList = [];
        for ($i = $horizonMonths - 1; $i >= 0; $i--) {
            $target = $anchor->copy()->subMonthsNoOverflow($i);
            $monthsList[] = [
                'year' => $target->year,
                'month' => $target->month,
                'start_date' => $target->copy()->startOfMonth()->toDateString(),
                'end_date' => $target->copy()->endOfMonth()->toDateString(),
                'label' => $target->translatedFormat('M Y'),
                'full_label' => $target->translatedFormat('F Y'),
                'key' => $target->format('Y-m'),
            ];
        }

        $startDateStr = $monthsList[0]['start_date'];
        $endDateStr = $monthsList[count($monthsList) - 1]['end_date'];

        // 1. Ambil akun aktif & pembukaannya
        $assetAccounts = $space->activeAccounts()
            ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment'])
            ->get();

        $creditAccounts = $space->creditAccounts()->active()->with('ledgerAccount')->get();

        // 2. Query transaksi posted dalam space hingga end_date
        $allEntries = DB::table('transaction_entries')
            ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
            ->where('financial_transactions.space_id', $space->id)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.transaction_date', '<=', $endDateStr)
            ->select(
                'transaction_entries.account_id',
                'financial_transactions.transaction_date',
                'transaction_entries.amount_signed'
            )
            ->get();

        // Kelompokkan entries per account
        $entriesByAccount = $allEntries->groupBy('account_id');

        // 3. Ambil seluruh data cicilan aktif
        $allInstallments = Installment::query()
            ->whereHas('installmentPlan', fn ($q) => $q->where('space_id', $space->id))
            ->with('installmentPlan')
            ->get();

        // 4. Hitung posisi neraca pada setiap akhir bulan
        $trendData = [];
        $prevNetWorth = null;

        foreach ($monthsList as $m) {
            $monthEndDate = $m['end_date'];

            // ASET
            $monthLiquid = 0.0;
            $monthInvestment = 0.0;

            foreach ($assetAccounts as $acc) {
                if ($acc->opened_on && $acc->opened_on > $monthEndDate) {
                    continue;
                }

                $entriesUpToMonth = ($entriesByAccount->get($acc->id) ?? collect())
                    ->filter(fn ($e) => $e->transaction_date <= $monthEndDate)
                    ->sum('amount_signed');

                $bal = round((float) $acc->opening_balance + (float) $entriesUpToMonth, 2);
                if ($acc->type === 'investment') {
                    $monthInvestment += $bal;
                } else {
                    $monthLiquid += $bal;
                }
            }

            $monthAssets = round($monthLiquid + $monthInvestment, 2);

            // KEWAJIBAN / UTANG
            // a. Kartu kredit & Paylater
            $monthCreditDebt = 0.0;
            foreach ($creditAccounts as $ca) {
                if ($ca->ledgerAccount) {
                    $ledgerId = $ca->ledgerAccount->id;
                    $entriesUpToMonth = ($entriesByAccount->get($ledgerId) ?? collect())
                        ->filter(fn ($e) => $e->transaction_date <= $monthEndDate)
                        ->sum('amount_signed');

                    $ledgerBal = round((float) $ca->ledgerAccount->opening_balance + (float) $entriesUpToMonth, 2);
                    if ($ledgerBal < 0) {
                        $monthCreditDebt += abs($ledgerBal);
                    }
                }
            }

            // b. Pokok cicilan yang belum lunas pada akhir bulan ini
            $monthInstallmentDebt = 0.0;
            foreach ($allInstallments as $inst) {
                $plan = $inst->installmentPlan;
                if (! $plan || ($plan->start_date && $plan->start_date->toDateString() > $monthEndDate)) {
                    continue;
                }

                // Cicilan dianggap belum lunas di bulan ini jika statusnya pending ATAU paid_at terjadi setelah akhir bulan ini
                $isUnpaidAtDate = ($inst->status === 'pending') || ($inst->paid_at && $inst->paid_at->toDateString() > $monthEndDate);

                if ($isUnpaidAtDate) {
                    $monthInstallmentDebt += (float) $inst->principal_amount;
                }
            }

            // Total liabilitas = utang kartu kredit (non-installment) + sisa pokok cicilan
            $monthLiabilities = round($monthCreditDebt + $monthInstallmentDebt, 2);
            $monthNetWorth = round($monthAssets - $monthLiabilities, 2);

            $deltaNetWorth = $prevNetWorth !== null ? round($monthNetWorth - $prevNetWorth, 2) : 0.0;
            $deltaNetWorthPct = ($prevNetWorth !== null && $prevNetWorth != 0)
                ? round(($deltaNetWorth / abs($prevNetWorth)) * 100, 1)
                : 0.0;

            $daRatio = $monthAssets > 0 ? round(($monthLiabilities / $monthAssets) * 100, 1) : 0.0;

            $trendData[] = [
                'year' => $m['year'],
                'month' => $m['month'],
                'label' => $m['label'],
                'full_label' => $m['full_label'],
                'key' => $m['key'],
                'date' => $monthEndDate,
                'assets' => $monthAssets,
                'liquid_assets' => $monthLiquid,
                'investment_assets' => $monthInvestment,
                'liabilities' => $monthLiabilities,
                'credit_debt' => $monthCreditDebt,
                'installment_debt' => $monthInstallmentDebt,
                'net_worth' => $monthNetWorth,
                'delta' => $deltaNetWorth,
                'delta_percentage' => $deltaNetWorthPct,
                'debt_to_asset_ratio' => $daRatio,
            ];

            $prevNetWorth = $monthNetWorth;
        }

        // Ringkasan Tren
        $firstMonth = $trendData[0];
        $lastMonth = $trendData[count($trendData) - 1];

        $totalGrowth = round($lastMonth['net_worth'] - $firstMonth['net_worth'], 2);
        $totalGrowthPct = ($firstMonth['net_worth'] != 0)
            ? round(($totalGrowth / abs($firstMonth['net_worth'])) * 100, 1)
            : 0.0;

        $debtChange = round($lastMonth['liabilities'] - $firstMonth['liabilities'], 2);
        $assetChange = round($lastMonth['assets'] - $firstMonth['assets'], 2);

        $highestNetWorth = collect($trendData)->sortByDesc('net_worth')->first();
        $lowestNetWorth = collect($trendData)->sortBy('net_worth')->first();

        // Hitung batas chart SVG
        $maxChartVal = max(
            collect($trendData)->max('assets') ?: 1,
            collect($trendData)->max('net_worth') ?: 1,
            collect($trendData)->max('liabilities') ?: 1
        );

        return [
            'horizon_months' => $horizonMonths,
            'trend' => $trendData,
            'summary' => [
                'start_net_worth' => $firstMonth['net_worth'],
                'end_net_worth' => $lastMonth['net_worth'],
                'total_growth' => $totalGrowth,
                'total_growth_percentage' => $totalGrowthPct,
                'asset_change' => $assetChange,
                'debt_change' => $debtChange,
                'is_deleveraging' => $debtChange < 0,
                'highest_month' => $highestNetWorth,
                'lowest_month' => $lowestNetWorth,
                'max_chart_val' => $maxChartVal,
            ],
        ];
    }

    /**
     * Riwayat komprehensif pelunasan utang dan progres cicilan.
     *
     * @return array<string, mixed>
     */
    public function getDebtHistorySummary(Space $space): array
    {
        $plans = $space->installmentPlans()->with(['installments', 'category', 'creditAccount'])->get();

        $totalPrincipalBorrowed = 0.0;
        $totalPrincipalPaid = 0.0;
        $totalInterestPaid = 0.0;
        $totalRemainingPrincipal = 0.0;
        $activePlansCount = 0;
        $completedPlansCount = 0;

        $planSummaries = [];

        foreach ($plans as $p) {
            $totalPrincipal = (float) $p->total_amount;
            $paidPrincipal = (float) $p->paidPrincipal();
            $remainingPrincipal = (float) $p->remainingPrincipal();

            $totalPrincipalBorrowed += $totalPrincipal;
            $totalPrincipalPaid += $paidPrincipal;
            $totalRemainingPrincipal += $remainingPrincipal;

            if ($p->isCompleted()) {
                $completedPlansCount++;
            } elseif ($p->isActive()) {
                $activePlansCount++;
            }

            $progressPct = $totalPrincipal > 0 ? round(($paidPrincipal / $totalPrincipal) * 100, 1) : 0.0;

            $planSummaries[] = [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name ?? 'Lainnya',
                'credit_account' => $p->creditAccount?->name ?? 'Mandiri / Tunai',
                'start_date' => $p->start_date ? $p->start_date->translatedFormat('d M Y') : '-',
                'total_principal' => $totalPrincipal,
                'paid_principal' => $paidPrincipal,
                'remaining_principal' => $remainingPrincipal,
                'tenor' => $p->tenor,
                'status' => $p->status,
                'progress_percentage' => min(100.0, $progressPct),
            ];
        }

        $overallPayoffProgress = $totalPrincipalBorrowed > 0
            ? round(($totalPrincipalPaid / $totalPrincipalBorrowed) * 100, 1)
            : 100.0;

        return [
            'total_borrowed' => $totalPrincipalBorrowed,
            'total_paid' => $totalPrincipalPaid,
            'total_remaining' => $totalRemainingPrincipal,
            'overall_payoff_percentage' => $overallPayoffProgress,
            'active_plans_count' => $activePlansCount,
            'completed_plans_count' => $completedPlansCount,
            'plans' => $planSummaries,
        ];
    }

    /**
     * Wawasan dan analisis finansial otomatis dari data kekayaan bersih.
     *
     * @param  array<string, mixed>  $balanceSheet
     * @param  array<string, mixed>  $trend
     * @param  array<string, mixed>  $debtHistory
     * @return list<string>
     */
    public function generateInsights(array $balanceSheet, array $trend, array $debtHistory): array
    {
        $insights = [];
        $netWorth = $balanceSheet['net_worth'];
        $grossAssets = $balanceSheet['gross_assets'];
        $totalLiabilities = $balanceSheet['total_liabilities'];
        $daRatio = $balanceSheet['debt_to_asset_ratio'];
        $summary = $trend['summary'] ?? [];

        // 1. Solvabilitas & Kekayaan Bersih
        if ($netWorth > 0) {
            if ($daRatio !== null && $daRatio <= 20) {
                $insights[] = 'Tingkat solvabilitas Anda sangat kuat dengan rasio utang terhadap aset (D/A) hanya '.$daRatio.'%. Sebagian besar aset Anda bebas dari beban kewajiban.';
            } elseif ($daRatio !== null && $daRatio <= 50) {
                $insights[] = 'Struktur neraca berada dalam rentang sehat dengan rasio utang '.$daRatio.'% terhadap aset.';
            } else {
                $insights[] = 'Rasio utang Anda mencapai '.($daRatio ?? 0).'% dari total aset. Prioritaskan percepatan pelunasan utang berbunga tinggi untuk memperkuat neraca.';
            }
        } else {
            $insights[] = 'Kekayaan bersih Anda saat ini berada dalam posisi defisit (kewajiban melampaui aset). Fokus utama saat ini adalah restrukturisasi utang dan memotong pengeluaran konsumtif.';
        }

        // 2. Tren Perubahan dari Waktu ke Waktu
        $growth = $summary['total_growth'] ?? 0;
        $growthPct = $summary['total_growth_percentage'] ?? 0;
        if ($growth > 0) {
            $insights[] = 'Tren kekayaan bersih bertumbuh positif sebesar Rp '.number_format($growth, 0, ',', '.').' (+'.$growthPct.'%) selama '.($trend['horizon_months'] ?? 6).' bulan terakhir.';
        } elseif ($growth < 0) {
            $insights[] = 'Kekayaan bersih terkoreksi sebesar Rp '.number_format(abs($growth), 0, ',', '.').' ('.$growthPct.'%) selama periode ini, yang menandakan pengeluaran atau utang baru melampaui laju akumulasi aset.';
        }

        // 3. Progres Pelunasan Utang (Deleveraging)
        if (! empty($summary['is_deleveraging'])) {
            $insights[] = 'Anda berhasil melakukan deleveraging: total kewajiban berhasil diturunkan sebesar Rp '.number_format(abs($summary['debt_change']), 0, ',', '.').' sepanjang periode pemantauan.';
        }

        if (($debtHistory['overall_payoff_percentage'] ?? 0) > 0 && ($debtHistory['total_borrowed'] ?? 0) > 0) {
            $insights[] = 'Progres pelunasan pokok cicilan Anda telah mencapai '.$debtHistory['overall_payoff_percentage'].'% dari total pinjaman yang pernah dicatat.';
        }

        return $insights;
    }

    protected function determineSolvencyStatus(float $assets, float $liabilities, float $netWorth, ?float $daRatio): array
    {
        if ($netWorth < 0) {
            return [
                'code' => 'insolvent',
                'label' => 'Defisit Kekayaan Bersih (Insolven)',
                'badge_class' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300',
            ];
        }

        if ($daRatio === null || $daRatio <= 20) {
            return [
                'code' => 'excellent',
                'label' => 'Sangat Kuat & Solven',
                'badge_class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
            ];
        }

        if ($daRatio <= 40) {
            return [
                'code' => 'healthy',
                'label' => 'Sehat & Terkendali',
                'badge_class' => 'bg-forest/10 text-forest border border-forest/20',
            ];
        }

        if ($daRatio <= 60) {
            return [
                'code' => 'moderate',
                'label' => 'Waspada (Beban Utang Sedang)',
                'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
            ];
        }

        return [
            'code' => 'high_debt',
            'label' => 'Beban Utang Tinggi (Leveraged)',
            'badge_class' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300',
        ];
    }

    protected function accountTypeLabel(string $type): string
    {
        return match ($type) {
            'bank' => 'Rekening Bank',
            'cash' => 'Uang Tunai / Kas',
            'e-wallet' => 'Dompet Digital',
            'investment' => 'Portofolio Investasi',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
