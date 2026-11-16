<?php

namespace App\Services;

use App\Models\Goal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdaptiveGoalService
{
    /**
     * Hitung analisis setoran adaptif per periode dan laju progres (pace) target tabungan.
     *
     * @return array{
     *     target_amount: float,
     *     current_amount: float,
     *     remaining_amount: float,
     *     progress_percentage: float,
     *     start_date: ?Carbon,
     *     target_date: ?Carbon,
     *     has_target_date: bool,
     *     total_days: ?int,
     *     elapsed_days: ?int,
     *     days_remaining: ?int,
     *     months_remaining: ?float,
     *     weeks_remaining: ?float,
     *     is_overdue: bool,
     *     is_achieved: bool,
     *     expected_amount_today: ?float,
     *     variance_amount: ?float,
     *     variance_percentage: ?float,
     *     pace_status: string,
     *     pace_label: string,
     *     pace_badge_class: string,
     *     pace_icon: string,
     *     required_monthly_deposit: ?float,
     *     required_weekly_deposit: ?float,
     *     required_daily_deposit: ?float,
     *     catch_up_lump_sum: float,
     *     daily_historical_rate: float,
     *     projected_completion_date: ?Carbon,
     *     projected_delay_days: ?int,
     *     recommendations: array<int, string>,
     *     summary_headline: string,
     *     summary_description: string
     * }
     */
    public function calculatePace(Goal $goal, ?Carbon $now = null): array
    {
        $today = $now
            ? $now->copy()->startOfDay()
            : ($goal->space ? $goal->space->now()->startOfDay() : Carbon::now('Asia/Jakarta')->startOfDay());

        $targetAmount = (float) $goal->target_amount;
        $currentAmount = (float) $goal->current_amount;
        $remainingAmount = max(0.0, round($targetAmount - $currentAmount, 2));

        $progressPercentage = $targetAmount > 0
            ? min(100.0, round(($currentAmount / $targetAmount) * 100, 1))
            : ($currentAmount > 0 ? 100.0 : 0.0);

        $isAchieved = $goal->isAchieved() || ($targetAmount > 0 && $remainingAmount <= 0.0);

        if (! $goal->target_date) {
            $paceStatus = $isAchieved ? 'achieved' : 'flexible';
            $paceLabel = $isAchieved ? 'Target Tercapai' : 'Fleksibel';
            $badgeClass = $isAchieved
                ? 'bg-forest/15 text-forest border border-forest/30'
                : 'bg-muted/15 text-muted border border-muted/30';
            $icon = $isAchieved ? '🎉' : '🎯';

            return [
                'target_amount' => $targetAmount,
                'current_amount' => $currentAmount,
                'remaining_amount' => $remainingAmount,
                'progress_percentage' => $progressPercentage,
                'start_date' => $goal->start_date ? $goal->start_date->copy()->startOfDay() : null,
                'target_date' => null,
                'has_target_date' => false,
                'total_days' => null,
                'elapsed_days' => null,
                'days_remaining' => null,
                'months_remaining' => null,
                'weeks_remaining' => null,
                'is_overdue' => false,
                'is_achieved' => $isAchieved,
                'expected_amount_today' => null,
                'variance_amount' => null,
                'variance_percentage' => null,
                'pace_status' => $paceStatus,
                'pace_label' => $paceLabel,
                'pace_badge_class' => $badgeClass,
                'pace_icon' => $icon,
                'required_monthly_deposit' => null,
                'required_weekly_deposit' => null,
                'required_daily_deposit' => null,
                'catch_up_lump_sum' => 0.0,
                'daily_historical_rate' => 0.0,
                'projected_completion_date' => null,
                'projected_delay_days' => null,
                'recommendations' => [
                    $isAchieved
                        ? 'Target tabungan telah tercapai 100%. Anda dapat mencairkan atau mengarsipkan target ini.'
                        : 'Target ini berstatus fleksibel tanpa tenggat waktu tertentu. Anda dapat menabung sesuai kelonggaran arus kas bulanan Anda.',
                ],
                'summary_headline' => $isAchieved ? 'Target Berhasil Dipenuhi' : 'Target Tabungan Fleksibel',
                'summary_description' => $isAchieved
                    ? 'Selamat! Seluruh target dana sebesar Rp '.number_format($targetAmount, 0, ',', '.').' telah terkumpul.'
                    : 'Target tabungan berjalan tanpa batas tenggat waktu. Terkumpul Rp '.number_format($currentAmount, 0, ',', '.').' dari sasaran Rp '.number_format($targetAmount, 0, ',', '.').'.',
            ];
        }

        $startDate = $goal->start_date
            ? $goal->start_date->copy()->startOfDay()
            : $today->copy();
        $targetDate = $goal->target_date->copy()->startOfDay();

        $totalDays = max(1, (int) $startDate->diffInDays($targetDate));
        $daysRemaining = (int) $today->diffInDays($targetDate, false);
        $isOverdue = $daysRemaining < 0 && ! $isAchieved;

        // Hitung durasi hari yang telah berjalan
        if ($today->lt($startDate)) {
            $elapsedDays = 0;
        } elseif ($today->gte($targetDate)) {
            $elapsedDays = $totalDays;
        } else {
            $elapsedDays = max(0, (int) $startDate->diffInDays($today));
        }

        // Rasio waktu dan target kumulatif ideal hari ini
        $timeRatio = min(1.0, max(0.0, $elapsedDays / $totalDays));
        $expectedAmountToday = round($targetAmount * $timeRatio, 2);
        $varianceAmount = round($currentAmount - $expectedAmountToday, 2);
        $variancePercentage = $targetAmount > 0
            ? round(($varianceAmount / $targetAmount) * 100, 1)
            : 0.0;

        // Hitung kebutuhan setoran adaptif per periode
        if ($isAchieved) {
            $requiredMonthly = 0.0;
            $requiredWeekly = 0.0;
            $requiredDaily = 0.0;
            $monthsRemaining = 0.0;
            $weeksRemaining = 0.0;
        } elseif ($daysRemaining <= 0) {
            $requiredMonthly = $remainingAmount;
            $requiredWeekly = $remainingAmount;
            $requiredDaily = $remainingAmount;
            $monthsRemaining = 0.0;
            $weeksRemaining = 0.0;
        } else {
            $monthsRemaining = round($daysRemaining / 30.4375, 2);
            $weeksRemaining = round($daysRemaining / 7.0, 2);

            $requiredDaily = round($remainingAmount / max(1, $daysRemaining), 2);

            if ($daysRemaining <= 7) {
                $requiredWeekly = $remainingAmount;
            } else {
                $requiredWeekly = round($remainingAmount / ($daysRemaining / 7.0), 2);
            }

            if ($daysRemaining <= 30) {
                $requiredMonthly = $remainingAmount;
            } else {
                $requiredMonthly = round($remainingAmount / ($daysRemaining / 30.4375), 2);
            }
        }

        // Tentukan Status Pace
        if ($isAchieved) {
            $paceStatus = 'achieved';
            $paceLabel = 'Target Tercapai';
            $badgeClass = 'bg-forest/15 text-forest border border-forest/30';
            $icon = '🎉';
        } elseif ($isOverdue) {
            $paceStatus = 'overdue';
            $paceLabel = 'Melewati Tenggat';
            $badgeClass = 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30';
            $icon = '⚠️';
        } elseif ($variancePercentage > 5.0) {
            $paceStatus = 'ahead';
            $paceLabel = 'Mendahului Target';
            $badgeClass = 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30';
            $icon = '🚀';
        } elseif ($variancePercentage >= -5.0) {
            $paceStatus = 'on_track';
            $paceLabel = 'Tepat Waktu';
            $badgeClass = 'bg-forest/15 text-forest border border-forest/30';
            $icon = '🟢';
        } elseif ($variancePercentage >= -25.0) {
            $paceStatus = 'behind';
            $paceLabel = 'Tertinggal';
            $badgeClass = 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30';
            $icon = '🟡';
        } else {
            $paceStatus = 'critical';
            $paceLabel = 'Tertinggal Kritis';
            $badgeClass = 'bg-rose-500/15 text-rose-700 dark:text-rose-400 border border-rose-500/30';
            $icon = '🔴';
        }

        // Setoran penyelamat sekaligus (Lump Sum Catch-up)
        $catchUpLumpSum = ($varianceAmount < 0 && ! $isAchieved) ? abs($varianceAmount) : 0.0;

        // Laju historis tabungan & estimasi tanggal selesai realistis
        $daysActive = max(1, (int) $startDate->diffInDays($today));
        $dailyHistoricalRate = $currentAmount > 0 ? round($currentAmount / $daysActive, 2) : 0.0;

        $projectedCompletionDate = null;
        $projectedDelayDays = null;

        if ($dailyHistoricalRate > 0 && $remainingAmount > 0) {
            $daysNeeded = (int) ceil($remainingAmount / $dailyHistoricalRate);
            $projectedCompletionDate = $today->copy()->addDays($daysNeeded);
            $projectedDelayDays = (int) $targetDate->diffInDays($projectedCompletionDate, false);
        }

        // Susun Rekomendasi Adaptif & Rangkuman Deskriptif
        $recommendations = [];

        if ($isAchieved) {
            $summaryHeadline = 'Target Berhasil Dipenuhi';
            $summaryDescription = 'Selamat! Seluruh target tabungan sebesar Rp '.number_format($targetAmount, 0, ',', '.').' berhasil tercapai.';
            $recommendations[] = 'Target tabungan telah terpenuhi 100%. Anda dapat mencairkan dana ke rekening asal kapan saja.';
        } elseif ($isOverdue) {
            $summaryHeadline = 'Tenggat Waktu Telah Terlewati';
            $summaryDescription = 'Batas target tanggal '.$targetDate->translatedFormat('d M Y').' telah terlewati dengan sisa kekurangan Rp '.number_format($remainingAmount, 0, ',', '.').'.';
            $recommendations[] = 'Setor sisa dana Rp '.number_format($remainingAmount, 0, ',', '.').' sekaligus untuk menyelesaikan target.';
            $recommendations[] = 'Atau perbarui tenggat target baru pada halaman edit agar rencana setoran kembali tertata rapi.';
        } elseif ($paceStatus === 'ahead') {
            $summaryHeadline = 'Mendahului Jadwal Tabungan';
            $summaryDescription = 'Progres tabungan Anda lebih cepat Rp '.number_format($varianceAmount, 0, ',', '.').' (+'.$variancePercentage.'%) dibanding jadwal ideal hari ini.';
            $recommendations[] = 'Pertahankan setoran adaptif Rp '.number_format($requiredMonthly, 0, ',', '.').'/bulan (atau Rp '.number_format($requiredWeekly, 0, ',', '.').'/minggu) untuk memastikan target selesai tepat waktu.';
            if ($projectedCompletionDate && $projectedDelayDays < 0) {
                $recommendations[] = 'Dengan laju cepat saat ini, target Anda diproyeksikan dapat tercapai lebih awal pada '.$projectedCompletionDate->translatedFormat('d M Y').' ('.abs($projectedDelayDays).' hari lebih cepat).';
            }
        } elseif ($paceStatus === 'on_track') {
            $summaryHeadline = 'Tabungan Berjalan Tepat Waktu';
            $summaryDescription = 'Realisasi saldo tabungan selaras dengan rencana waktu berjalan (toleransi ±5%).';
            $recommendations[] = 'Pertahankan disiplin menabung sebesar Rp '.number_format($requiredMonthly, 0, ',', '.').'/bulan (atau Rp '.number_format($requiredWeekly, 0, ',', '.').'/minggu) agar tercapai tepat pada '.$targetDate->translatedFormat('d M Y').'.';
        } elseif ($paceStatus === 'behind') {
            $summaryHeadline = 'Progres Tabungan Tertinggal';
            $summaryDescription = 'Saldo terkumpul saat ini tertinggal Rp '.number_format($catchUpLumpSum, 0, ',', '.').' dari ekspektasi ideal hari ini (Rp '.number_format($expectedAmountToday, 0, ',', '.').').';
            $recommendations[] = 'Opsi 1 (Setoran Baru Adaptif): Sesuaikan setoran menjadi Rp '.number_format($requiredMonthly, 0, ',', '.').'/bulan (atau Rp '.number_format($requiredWeekly, 0, ',', '.').'/minggu) untuk sisa '.$daysRemaining.' hari ke depan agar target tetap selesai tepat waktu.';
            $recommendations[] = 'Opsi 2 (Setoran Penyelamat Sekaligus): Lakukan satu kali setoran tambahan sebesar Rp '.number_format($catchUpLumpSum, 0, ',', '.').' hari ini untuk langsung kembali ke jadwal tepat waktu.';
            if ($projectedCompletionDate && $projectedDelayDays > 0) {
                $monthlyHistorical = round($dailyHistoricalRate * 30.4375);
                $recommendations[] = 'Opsi 3 (Penyesuaian Tenggat): Jika laju tabungan saat ini (~Rp '.number_format($monthlyHistorical, 0, ',', '.').'/bln) dipertahankan, target baru akan tercapai pada '.$projectedCompletionDate->translatedFormat('d M Y').' (mundur '.$projectedDelayDays.' hari). Anda dapat memperpanjang tanggal target di menu edit.';
            }
        } else {
            // critical
            $summaryHeadline = 'Peringatan: Progres Tertinggal Jauh';
            $summaryDescription = 'Saldo tabungan tertinggal Rp '.number_format($catchUpLumpSum, 0, ',', '.').' ('.abs($variancePercentage).'% di bawah jadwal ideal Rp '.number_format($expectedAmountToday, 0, ',', '.').').';
            $recommendations[] = 'Opsi 1 (Setoran Adaptif Baru): Naikkan setoran menjadi Rp '.number_format($requiredMonthly, 0, ',', '.').'/bulan (atau Rp '.number_format($requiredWeekly, 0, ',', '.').'/minggu) untuk mengejar sisa target.';
            $recommendations[] = 'Opsi 2 (Setoran Penyelamat Sekaligus): Lakukan setoran sebesar Rp '.number_format($catchUpLumpSum, 0, ',', '.').' untuk menutupi ketertinggalan akumulasi.';
            if ($projectedCompletionDate && $projectedDelayDays > 0) {
                $monthlyHistorical = round($dailyHistoricalRate * 30.4375);
                $recommendations[] = 'Opsi 3 (Ubah Tenggat Realistis): Pada laju tabungan berjalan saat ini (~Rp '.number_format($monthlyHistorical, 0, ',', '.').'/bln), target diproyeksikan selesai pada '.$projectedCompletionDate->translatedFormat('d M Y').' (terlambat '.$projectedDelayDays.' hari). Pertimbangkan menyesuaikan target nominal atau memperpanjang tenggat waktu.';
            }
        }

        return [
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'remaining_amount' => $remainingAmount,
            'progress_percentage' => $progressPercentage,
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'has_target_date' => true,
            'total_days' => $totalDays,
            'elapsed_days' => $elapsedDays,
            'days_remaining' => $daysRemaining,
            'months_remaining' => $monthsRemaining,
            'weeks_remaining' => $weeksRemaining,
            'is_overdue' => $isOverdue,
            'is_achieved' => $isAchieved,
            'expected_amount_today' => $expectedAmountToday,
            'variance_amount' => $varianceAmount,
            'variance_percentage' => $variancePercentage,
            'pace_status' => $paceStatus,
            'pace_label' => $paceLabel,
            'pace_badge_class' => $badgeClass,
            'pace_icon' => $icon,
            'required_monthly_deposit' => $requiredMonthly,
            'required_weekly_deposit' => $requiredWeekly,
            'required_daily_deposit' => $requiredDaily,
            'catch_up_lump_sum' => $catchUpLumpSum,
            'daily_historical_rate' => $dailyHistoricalRate,
            'projected_completion_date' => $projectedCompletionDate,
            'projected_delay_days' => $projectedDelayDays,
            'recommendations' => $recommendations,
            'summary_headline' => $summaryHeadline,
            'summary_description' => $summaryDescription,
        ];
    }

    /**
     * Hitung ringkasan komitmen tabungan adaptif untuk seluruh target dalam sebuah koleksi.
     *
     * @param  Collection<int, Goal>  $goals
     * @return array{
     *     total_goals: int,
     *     active_goals: int,
     *     on_track_or_ahead_count: int,
     *     behind_count: int,
     *     critical_count: int,
     *     overdue_count: int,
     *     total_monthly_commitment: float,
     *     total_weekly_commitment: float,
     *     total_catch_up_needed: float
     * }
     */
    public function calculateSummary(Collection $goals, ?Carbon $now = null): array
    {
        $activeGoals = 0;
        $onTrackOrAheadCount = 0;
        $behindCount = 0;
        $criticalCount = 0;
        $overdueCount = 0;
        $totalMonthlyCommitment = 0.0;
        $totalWeeklyCommitment = 0.0;
        $totalCatchUpNeeded = 0.0;

        foreach ($goals as $goal) {
            if ($goal->status !== 'active') {
                continue;
            }

            $activeGoals++;
            $pace = $this->calculatePace($goal, $now);

            if ($pace['pace_status'] === 'ahead' || $pace['pace_status'] === 'on_track') {
                $onTrackOrAheadCount++;
            } elseif ($pace['pace_status'] === 'behind') {
                $behindCount++;
            } elseif ($pace['pace_status'] === 'critical') {
                $criticalCount++;
            } elseif ($pace['pace_status'] === 'overdue') {
                $overdueCount++;
            }

            if ($pace['required_monthly_deposit'] !== null) {
                $totalMonthlyCommitment += $pace['required_monthly_deposit'];
            }
            if ($pace['required_weekly_deposit'] !== null) {
                $totalWeeklyCommitment += $pace['required_weekly_deposit'];
            }
            $totalCatchUpNeeded += $pace['catch_up_lump_sum'];
        }

        return [
            'total_goals' => $goals->count(),
            'active_goals' => $activeGoals,
            'on_track_or_ahead_count' => $onTrackOrAheadCount,
            'behind_count' => $behindCount,
            'critical_count' => $criticalCount,
            'overdue_count' => $overdueCount,
            'total_monthly_commitment' => round($totalMonthlyCommitment, 2),
            'total_weekly_commitment' => round($totalWeeklyCommitment, 2),
            'total_catch_up_needed' => round($totalCatchUpNeeded, 2),
        ];
    }
}
