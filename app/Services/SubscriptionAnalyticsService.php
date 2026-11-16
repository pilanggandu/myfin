<?php

namespace App\Services;

use App\Models\RecurringSchedule;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SubscriptionAnalyticsService
{
    /**
     * Hitung analisis langganan dan pembayaran berulang untuk sebuah ruang.
     *
     * @param  array{
     *     frequency?: ?string,
     *     category_id?: ?int,
     *     source_account_id?: ?int,
     *     member_id?: ?int,
     *     has_price_hike?: ?bool,
     *     status?: ?string,
     *     search?: ?string
     * }  $filters
     * @return array{
     *     summary: array{
     *         total_subscriptions_count: int,
     *         active_subscriptions_count: int,
     *         paused_subscriptions_count: int,
     *         total_monthly_cost: float,
     *         total_annual_cost: float,
     *         price_hike_count: int,
     *         total_monthly_increase: float,
     *         total_annual_increase: float,
     *         upcoming_7_days_total: float,
     *         upcoming_30_days_total: float,
     *         upcoming_overdue_count: int,
     *         upcoming_due_today_count: int
     *     },
     *     subscriptions: Collection<int, array>,
     *     price_hikes: array<int, array>,
     *     by_category: array<int, array>,
     *     by_frequency: array<string, array>,
     *     by_account: array<int, array>,
     *     by_member: array<int, array>,
     *     upcoming_timeline: array{
     *         overdue: array{label: string, items: array, count: int, total_amount: float},
     *         due_today: array{label: string, items: array, count: int, total_amount: float},
     *         this_week: array{label: string, items: array, count: int, total_amount: float},
     *         this_month: array{label: string, items: array, count: int, total_amount: float},
     *         later: array{label: string, items: array, count: int, total_amount: float}
     *     }
     * }
     */
    public function getAnalytics(Space $space, array $filters = [], ?Carbon $now = null): array
    {
        $today = $now
            ? $now->copy()->startOfDay()
            : ($space->now() ? $space->now()->startOfDay() : Carbon::now('Asia/Jakarta')->startOfDay());

        $statusFilter = $filters['status'] ?? 'active';

        $query = RecurringSchedule::where('space_id', $space->id)
            ->where('type', 'expense')
            ->with([
                'category',
                'sourceAccount.creditAccount',
                'member',
                'occurrences' => fn ($q) => $q->where('status', 'confirmed')->orderBy('due_date', 'asc'),
            ]);

        if ($statusFilter === 'all') {
            $query->whereNull('archived_at');
        } elseif ($statusFilter === 'paused') {
            $query->paused();
        } else {
            $query->active();
        }

        if (! empty($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['source_account_id'])) {
            $query->where('source_account_id', $filters['source_account_id']);
        }

        if (! empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $rawSchedules = $query->orderBy('next_due_date', 'asc')->get();

        $analyzedList = collect();
        $priceHikes = [];

        foreach ($rawSchedules as $schedule) {
            $analyzed = $this->analyzeSchedule($schedule, $today);

            if (! empty($filters['has_price_hike']) && ! $analyzed['has_price_increase']) {
                continue;
            }

            $analyzedList->push($analyzed);

            if ($analyzed['has_price_increase']) {
                $priceHikes[] = $analyzed;
            }
        }

        // 1. Ringkasan Keseluruhan (Summary Metrics)
        $totalMonthlyCost = (float) $analyzedList->sum('normalized_monthly_cost');
        $totalAnnualCost = (float) $analyzedList->sum('annual_cost');
        $totalMonthlyIncrease = (float) collect($priceHikes)->sum('monthly_increase');
        $totalAnnualIncrease = (float) collect($priceHikes)->sum('annual_increase');

        $upcoming7DaysTotal = 0.0;
        $upcoming30DaysTotal = 0.0;
        $upcomingOverdueCount = 0;
        $upcomingDueTodayCount = 0;

        foreach ($analyzedList as $sub) {
            if ($sub['status'] !== 'active') {
                continue;
            }

            $days = $sub['days_until_due'];
            $amt = (float) $sub['raw_amount'];

            if ($days < 0) {
                $upcomingOverdueCount++;
                $upcoming7DaysTotal += $amt;
                $upcoming30DaysTotal += $amt;
            } elseif ($days === 0) {
                $upcomingDueTodayCount++;
                $upcoming7DaysTotal += $amt;
                $upcoming30DaysTotal += $amt;
            } elseif ($days <= 7) {
                $upcoming7DaysTotal += $amt;
                $upcoming30DaysTotal += $amt;
            } elseif ($days <= 30) {
                $upcoming30DaysTotal += $amt;
            }
        }

        $summary = [
            'total_subscriptions_count' => $analyzedList->count(),
            'active_subscriptions_count' => $analyzedList->where('status', 'active')->count(),
            'paused_subscriptions_count' => $analyzedList->where('status', 'paused')->count(),
            'total_monthly_cost' => round($totalMonthlyCost, 2),
            'total_annual_cost' => round($totalAnnualCost, 2),
            'price_hike_count' => count($priceHikes),
            'total_monthly_increase' => round($totalMonthlyIncrease, 2),
            'total_annual_increase' => round($totalAnnualIncrease, 2),
            'upcoming_7_days_total' => round($upcoming7DaysTotal, 2),
            'upcoming_30_days_total' => round($upcoming30DaysTotal, 2),
            'upcoming_overdue_count' => $upcomingOverdueCount,
            'upcoming_due_today_count' => $upcomingDueTodayCount,
        ];

        // 2. Pengelompokan Berdasarkan Kategori
        $byCategoryMap = [];
        foreach ($analyzedList as $item) {
            $catKey = $item['category']['id'] ?? 'uncategorized';
            if (! isset($byCategoryMap[$catKey])) {
                $byCategoryMap[$catKey] = [
                    'category_id' => $item['category']['id'],
                    'category_name' => $item['category']['name'],
                    'category_color' => $item['category']['color'],
                    'category_icon' => $item['category']['icon'],
                    'items' => [],
                    'count' => 0,
                    'monthly_cost' => 0.0,
                    'annual_cost' => 0.0,
                    'share_percentage' => 0.0,
                ];
            }
            $byCategoryMap[$catKey]['items'][] = $item;
            $byCategoryMap[$catKey]['count']++;
            $byCategoryMap[$catKey]['monthly_cost'] += $item['normalized_monthly_cost'];
            $byCategoryMap[$catKey]['annual_cost'] += $item['annual_cost'];
        }

        foreach ($byCategoryMap as &$catGroup) {
            $catGroup['monthly_cost'] = round($catGroup['monthly_cost'], 2);
            $catGroup['annual_cost'] = round($catGroup['annual_cost'], 2);
            $catGroup['share_percentage'] = $totalMonthlyCost > 0
                ? round(($catGroup['monthly_cost'] / $totalMonthlyCost) * 100, 1)
                : 0.0;
        }
        unset($catGroup);

        usort($byCategoryMap, fn ($a, $b) => $b['monthly_cost'] <=> $a['monthly_cost']);

        // 3. Pengelompokan Berdasarkan Frekuensi Pembayaran
        $byFrequency = [
            'monthly' => [
                'frequency' => 'monthly',
                'label' => 'Bulanan',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
                'normalized_monthly_cost' => 0.0,
                'annual_cost' => 0.0,
                'share_percentage' => 0.0,
            ],
            'yearly' => [
                'frequency' => 'yearly',
                'label' => 'Tahunan',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
                'normalized_monthly_cost' => 0.0,
                'annual_cost' => 0.0,
                'share_percentage' => 0.0,
            ],
            'weekly' => [
                'frequency' => 'weekly',
                'label' => 'Mingguan',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
                'normalized_monthly_cost' => 0.0,
                'annual_cost' => 0.0,
                'share_percentage' => 0.0,
            ],
        ];

        foreach ($analyzedList as $item) {
            $freq = $item['frequency'] ?? 'monthly';
            if (! isset($byFrequency[$freq])) {
                $freq = 'monthly';
            }
            $byFrequency[$freq]['items'][] = $item;
            $byFrequency[$freq]['count']++;
            $byFrequency[$freq]['total_amount'] += $item['raw_amount'];
            $byFrequency[$freq]['normalized_monthly_cost'] += $item['normalized_monthly_cost'];
            $byFrequency[$freq]['annual_cost'] += $item['annual_cost'];
        }

        foreach ($byFrequency as &$freqGroup) {
            $freqGroup['total_amount'] = round($freqGroup['total_amount'], 2);
            $freqGroup['normalized_monthly_cost'] = round($freqGroup['normalized_monthly_cost'], 2);
            $freqGroup['annual_cost'] = round($freqGroup['annual_cost'], 2);
            $freqGroup['share_percentage'] = $totalMonthlyCost > 0
                ? round(($freqGroup['normalized_monthly_cost'] / $totalMonthlyCost) * 100, 1)
                : 0.0;
        }
        unset($freqGroup);

        // 4. Pengelompokan Berdasarkan Rekening Pembayaran (Metode Bayar)
        $byAccountMap = [];
        foreach ($analyzedList as $item) {
            $accKey = $item['account']['id'] ?? 'unspecified';
            if (! isset($byAccountMap[$accKey])) {
                $byAccountMap[$accKey] = [
                    'account_id' => $item['account']['id'] ?? null,
                    'account_name' => $item['account']['name'] ?? 'Rekening Belum Ditentukan',
                    'account_type' => $item['account']['type'] ?? 'other',
                    'items' => [],
                    'count' => 0,
                    'monthly_cost' => 0.0,
                    'annual_cost' => 0.0,
                    'share_percentage' => 0.0,
                ];
            }
            $byAccountMap[$accKey]['items'][] = $item;
            $byAccountMap[$accKey]['count']++;
            $byAccountMap[$accKey]['monthly_cost'] += $item['normalized_monthly_cost'];
            $byAccountMap[$accKey]['annual_cost'] += $item['annual_cost'];
        }

        foreach ($byAccountMap as &$accGroup) {
            $accGroup['monthly_cost'] = round($accGroup['monthly_cost'], 2);
            $accGroup['annual_cost'] = round($accGroup['annual_cost'], 2);
            $accGroup['share_percentage'] = $totalMonthlyCost > 0
                ? round(($accGroup['monthly_cost'] / $totalMonthlyCost) * 100, 1)
                : 0.0;
        }
        unset($accGroup);
        usort($byAccountMap, fn ($a, $b) => $b['monthly_cost'] <=> $a['monthly_cost']);

        // 5. Pengelompokan Berdasarkan Anggota (Shared Space)
        $byMemberMap = [];
        foreach ($analyzedList as $item) {
            $memKey = $item['member']['id'] ?? 'unassigned';
            if (! isset($byMemberMap[$memKey])) {
                $byMemberMap[$memKey] = [
                    'member_id' => $item['member']['id'] ?? null,
                    'member_name' => $item['member']['name'] ?? 'Seluruh Anggota / Bersama',
                    'member_color' => $item['member']['color'] ?? '#174C40',
                    'items' => [],
                    'count' => 0,
                    'monthly_cost' => 0.0,
                    'share_percentage' => 0.0,
                ];
            }
            $byMemberMap[$memKey]['items'][] = $item;
            $byMemberMap[$memKey]['count']++;
            $byMemberMap[$memKey]['monthly_cost'] += $item['normalized_monthly_cost'];
        }

        foreach ($byMemberMap as &$memGroup) {
            $memGroup['monthly_cost'] = round($memGroup['monthly_cost'], 2);
            $memGroup['share_percentage'] = $totalMonthlyCost > 0
                ? round(($memGroup['monthly_cost'] / $totalMonthlyCost) * 100, 1)
                : 0.0;
        }
        unset($memGroup);
        usort($byMemberMap, fn ($a, $b) => $b['monthly_cost'] <=> $a['monthly_cost']);

        // 6. Timeline Jadwal Pembayaran Berikutnya (Upcoming Horizons)
        $upcomingTimeline = [
            'overdue' => [
                'label' => 'Terlambat (Overdue)',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
            ],
            'due_today' => [
                'label' => 'Jatuh Tempo Hari Ini',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
            ],
            'this_week' => [
                'label' => '7 Hari Mendatang (Minggu Ini)',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
            ],
            'this_month' => [
                'label' => '8 - 30 Hari Mendatang (Bulan Ini)',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
            ],
            'later' => [
                'label' => 'Nanti (> 30 Hari)',
                'items' => [],
                'count' => 0,
                'total_amount' => 0.0,
            ],
        ];

        // Urutkan item aktif berdasarkan next_due_date asc untuk timeline
        $activeSorted = $analyzedList->where('status', 'active')->sortBy('days_until_due');
        foreach ($activeSorted as $item) {
            $days = $item['days_until_due'];
            $amt = (float) $item['raw_amount'];

            if ($days < 0) {
                $bucket = 'overdue';
            } elseif ($days === 0) {
                $bucket = 'due_today';
            } elseif ($days <= 7) {
                $bucket = 'this_week';
            } elseif ($days <= 30) {
                $bucket = 'this_month';
            } else {
                $bucket = 'later';
            }

            $upcomingTimeline[$bucket]['items'][] = $item;
            $upcomingTimeline[$bucket]['count']++;
            $upcomingTimeline[$bucket]['total_amount'] += $amt;
        }

        foreach ($upcomingTimeline as &$bucketData) {
            $bucketData['total_amount'] = round($bucketData['total_amount'], 2);
        }
        unset($bucketData);

        return [
            'summary' => $summary,
            'subscriptions' => $analyzedList,
            'price_hikes' => $priceHikes,
            'by_category' => $byCategoryMap,
            'by_frequency' => $byFrequency,
            'by_account' => $byAccountMap,
            'by_member' => $byMemberMap,
            'upcoming_timeline' => $upcomingTimeline,
        ];
    }

    /**
     * Analisis rinci untuk satu jadwal langganan / pembayaran berulang.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     status: string,
     *     frequency: string,
     *     frequency_label: string,
     *     raw_amount: int,
     *     is_variable_amount: bool,
     *     normalized_monthly_cost: float,
     *     annual_cost: float,
     *     category: array{id: ?int, name: string, color: string, icon: string},
     *     account: ?array{id: int, name: string, type: string},
     *     member: ?array{id: int, name: string, color: string},
     *     start_date: ?Carbon,
     *     next_due_date: ?Carbon,
     *     days_until_due: int,
     *     due_status: string,
     *     due_status_label: string,
     *     has_price_increase: bool,
     *     has_price_decrease: bool,
     *     initial_amount: int,
     *     current_amount: int,
     *     previous_amount: int,
     *     price_difference: int,
     *     percentage_increase: float,
     *     monthly_increase: float,
     *     annual_increase: float,
     *     latest_increase_date: ?string,
     *     price_history: array<int, array{date: string, amount: int, change: int}>,
     *     schedule: RecurringSchedule
     * }
     */
    public function analyzeSchedule(RecurringSchedule $schedule, ?Carbon $today = null): array
    {
        $today = $today ?: Carbon::now('Asia/Jakarta')->startOfDay();

        $rawAmount = (int) ($schedule->amount ?? 0);
        $frequency = $schedule->frequency ?: 'monthly';

        // Normalisasi biaya bulanan dan tahunan
        $normalizedMonthly = match ($frequency) {
            'weekly' => round(($rawAmount * 52) / 12, 2),
            'yearly' => round($rawAmount / 12, 2),
            default => (float) $rawAmount,
        };
        $annualCost = round($normalizedMonthly * 12, 2);

        // Jatuh tempo berikutnya
        $nextDueDate = $schedule->next_due_date
            ? Carbon::parse($schedule->next_due_date)->startOfDay()
            : null;

        $daysUntilDue = $nextDueDate ? (int) $today->diffInDays($nextDueDate, false) : 0;

        if ($daysUntilDue < 0) {
            $dueStatus = 'overdue';
            $dueStatusLabel = 'Terlambat '.abs($daysUntilDue).' hari';
        } elseif ($daysUntilDue === 0) {
            $dueStatus = 'due_today';
            $dueStatusLabel = 'Jatuh tempo hari ini';
        } else {
            $dueStatus = 'upcoming';
            $dueStatusLabel = $daysUntilDue.' hari lagi';
        }

        // Analisis Deteksi Kenaikan Biaya (Price Hike Detection)
        $occurrences = $schedule->relationLoaded('occurrences')
            ? $schedule->occurrences->where('status', 'confirmed')->sortBy('due_date')
            : $schedule->occurrences()->where('status', 'confirmed')->orderBy('due_date', 'asc')->get();

        $timelinePoints = [];

        // Masukkan nominal dari rekaman konfirmasi mutasi historis
        foreach ($occurrences as $occ) {
            $occAmt = (int) ($occ->amount ?? 0);
            $occDate = $occ->due_date ? Carbon::parse($occ->due_date)->toDateString() : null;

            if ($occAmt <= 0 || ! $occDate) {
                continue;
            }

            // Simpan perubahan nominal
            $lastPoint = end($timelinePoints);
            if (! $lastPoint || $lastPoint['amount'] !== $occAmt) {
                $timelinePoints[] = [
                    'date' => $occDate,
                    'amount' => $occAmt,
                ];
            }
        }

        // Jika tidak ada occurrence sama sekali, gunakan start_date dan schedule amount
        if (empty($timelinePoints)) {
            $timelinePoints[] = [
                'date' => $schedule->start_date ? Carbon::parse($schedule->start_date)->toDateString() : $today->toDateString(),
                'amount' => $rawAmount,
            ];
        } else {
            // Jika nominal pada jadwal sekarang berbeda dari occurrence terakhir
            $lastRecorded = end($timelinePoints);
            if ($rawAmount > 0 && $rawAmount !== $lastRecorded['amount']) {
                $timelinePoints[] = [
                    'date' => $schedule->updated_at ? $schedule->updated_at->toDateString() : $today->toDateString(),
                    'amount' => $rawAmount,
                ];
            }
        }

        // Susun riwayat kenaikan/penurunan harga
        $priceHistory = [];
        $initialAmount = $timelinePoints[0]['amount'];
        $currentAmount = end($timelinePoints)['amount'];
        $previousAmount = $initialAmount;
        $hasPriceIncrease = false;
        $hasPriceDecrease = false;
        $priceDifference = 0;
        $percentageIncrease = 0.0;
        $latestIncreaseDate = null;

        $runningAmount = null;
        foreach ($timelinePoints as $idx => $pt) {
            $change = $runningAmount !== null ? ($pt['amount'] - $runningAmount) : 0;
            $priceHistory[] = [
                'date' => $pt['date'],
                'amount' => $pt['amount'],
                'change' => $change,
            ];

            if ($change > 0) {
                $hasPriceIncrease = true;
                $previousAmount = $runningAmount;
                $priceDifference = $change;
                $latestIncreaseDate = $pt['date'];
            } elseif ($change < 0) {
                $hasPriceDecrease = true;
                $previousAmount = $runningAmount;
                $priceDifference = $change;
                $latestIncreaseDate = $pt['date'];
            }

            $runningAmount = $pt['amount'];
        }

        // Jika terjadi kenaikan kumulatif dari awal
        if (! $hasPriceIncrease && $currentAmount > $initialAmount) {
            $hasPriceIncrease = true;
            $previousAmount = $initialAmount;
            $priceDifference = $currentAmount - $initialAmount;
            $latestIncreaseDate = end($timelinePoints)['date'];
        }

        if ($previousAmount > 0) {
            $percentageIncrease = round(($priceDifference / $previousAmount) * 100, 1);
        }

        // Dampak kenaikan biaya per bulan dan per tahun
        $monthlyIncrease = 0.0;
        $annualIncrease = 0.0;
        if ($hasPriceIncrease && $priceDifference > 0) {
            $monthlyIncrease = match ($frequency) {
                'weekly' => round(($priceDifference * 52) / 12, 2),
                'yearly' => round($priceDifference / 12, 2),
                default => (float) $priceDifference,
            };
            $annualIncrease = round($monthlyIncrease * 12, 2);
        }

        // Data Kategori
        $categoryData = $schedule->category ? [
            'id' => $schedule->category->id,
            'name' => $schedule->category->name,
            'color' => $schedule->category->color ?: '#174C40',
            'icon' => $schedule->category->icon ?: '🏷️',
        ] : [
            'id' => null,
            'name' => 'Tanpa Kategori',
            'color' => '#6b7280',
            'icon' => '📦',
        ];

        // Data Rekening
        $accountData = $schedule->sourceAccount ? [
            'id' => $schedule->sourceAccount->id,
            'name' => $schedule->sourceAccount->name,
            'type' => $schedule->sourceAccount->type,
        ] : null;

        // Data Member
        $memberData = $schedule->member ? [
            'id' => $schedule->member->id,
            'name' => $schedule->member->name,
            'color' => $schedule->member->color ?: '#174C40',
        ] : null;

        return [
            'id' => $schedule->id,
            'name' => $schedule->name,
            'type' => $schedule->type,
            'status' => $schedule->status,
            'frequency' => $frequency,
            'frequency_label' => $schedule->frequencyLabel(),
            'raw_amount' => $rawAmount,
            'is_variable_amount' => (bool) $schedule->is_variable_amount,
            'normalized_monthly_cost' => $normalizedMonthly,
            'annual_cost' => $annualCost,
            'category' => $categoryData,
            'account' => $accountData,
            'member' => $memberData,
            'start_date' => $schedule->start_date ? Carbon::parse($schedule->start_date) : null,
            'next_due_date' => $nextDueDate,
            'days_until_due' => $daysUntilDue,
            'due_status' => $dueStatus,
            'due_status_label' => $dueStatusLabel,
            'has_price_increase' => $hasPriceIncrease,
            'has_price_decrease' => $hasPriceDecrease,
            'initial_amount' => $initialAmount,
            'current_amount' => $currentAmount,
            'previous_amount' => $previousAmount,
            'price_difference' => $priceDifference,
            'percentage_increase' => $percentageIncrease,
            'monthly_increase' => $monthlyIncrease,
            'annual_increase' => $annualIncrease,
            'latest_increase_date' => $latestIncreaseDate,
            'price_history' => $priceHistory,
            'schedule' => $schedule,
        ];
    }
}
