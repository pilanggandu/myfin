<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionAnomalyService
{
    /**
     * Ambang batas default deteksi anomali.
     */
    public const OUTLIER_MULTIPLIER_CATEGORY = 3.0;

    public const OUTLIER_STDDEV_MULTIPLIER = 2.5;

    public const OUTLIER_MIN_DIFF_CATEGORY = 100000; // Rp 100.000 selisih minimal dari rata-rata

    public const OUTLIER_MULTIPLIER_GLOBAL = 3.5;

    public const OUTLIER_MIN_DIFF_GLOBAL = 250000; // Rp 250.000 selisih minimal pengeluaran global

    public const SPIKE_MIN_PERCENTAGE = 50.0; // Kenaikan minimal 50%

    public const SPIKE_MIN_DIFF_AMOUNT = 200000; // Kenaikan nominal minimal Rp 200.000

    public const SPIKE_NEW_CATEGORY_MIN_AMOUNT = 300000; // Kategori baru tanpa histori minimal Rp 300.000

    public const DUPLICATE_MAX_DAYS_DIFF = 1; // Maksimal selisih 1 hari

    /**
     * Dapatkan ringkasan seluruh anomali untuk suatu ruang pada periode tertentu.
     *
     * @return array<string, mixed>
     */
    public function detectAnomaliesForSpace(
        Space $space,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $accountId = null
    ): array {
        $timezone = $space->timezone ?: 'Asia/Jakarta';
        $now = $space->now();

        if (! $startDate || ! $endDate) {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
        }

        $categoryStats = $this->buildCategoryBaselines($space);
        $globalStats = $this->buildGlobalBaselines($space);
        $spikes = $this->detectCategorySpikes($space, $startDate, $endDate, $accountId);
        $spikedCategoryIds = collect($spikes)->pluck('category_id')->all();

        // Ambil transaksi dalam periode untuk pemeriksaan anomali
        $periodTransactions = $this->getPeriodTransactionsQuery($space->id, $startDate, $endDate, $accountId)
            ->with(['category', 'entries.account', 'member'])
            ->get();

        $duplicates = $this->detectDuplicatesInSpace($space, $startDate, $endDate, $accountId);
        $outliers = $this->detectOutliersInCollection($periodTransactions, $categoryStats, $globalStats);

        $flaggedMap = [];

        // Petakan transaksi duplikat
        foreach ($duplicates as $dupGroup) {
            foreach ($dupGroup['transactions'] as $tx) {
                $otherIds = collect($dupGroup['transactions'])
                    ->pluck('id')
                    ->reject(fn ($id) => $id === $tx->id)
                    ->values()
                    ->all();

                if (! isset($flaggedMap[$tx->id])) {
                    $flaggedMap[$tx->id] = $this->emptyFlagPayload($tx);
                }

                $flaggedMap[$tx->id]['is_duplicate'] = true;
                $flaggedMap[$tx->id]['duplicate_with'] = array_values(array_unique(array_merge($flaggedMap[$tx->id]['duplicate_with'], $otherIds)));
                $flaggedMap[$tx->id]['duplicate_reason'] = $dupGroup['reason'];
                $flaggedMap[$tx->id]['badges'][] = [
                    'type' => 'duplicate',
                    'label' => 'Potensi Ganda',
                    'title' => 'Nominal sama persis dengan Transaksi #'.implode(', #', $otherIds),
                    'tone' => 'danger',
                ];
            }
        }

        // Petakan transaksi outlier
        foreach ($outliers as $outlierItem) {
            $tx = $outlierItem['transaction'];
            if (! isset($flaggedMap[$tx->id])) {
                $flaggedMap[$tx->id] = $this->emptyFlagPayload($tx);
            }

            $flaggedMap[$tx->id]['is_outlier'] = true;
            $flaggedMap[$tx->id]['outlier_data'] = $outlierItem;
            $flaggedMap[$tx->id]['badges'][] = [
                'type' => 'outlier',
                'label' => 'Nominal Ekstrem',
                'title' => $outlierItem['reason'],
                'tone' => 'warning',
            ];
        }

        // Petakan transaksi yang masuk ke dalam kategori dengan lonjakan tajam
        foreach ($periodTransactions as $tx) {
            if ($tx->isExpense() && $tx->category_id && in_array($tx->category_id, $spikedCategoryIds, true)) {
                $spikeInfo = collect($spikes)->firstWhere('category_id', $tx->category_id);

                if (! isset($flaggedMap[$tx->id])) {
                    $flaggedMap[$tx->id] = $this->emptyFlagPayload($tx);
                }

                $flaggedMap[$tx->id]['is_category_spike'] = true;
                $flaggedMap[$tx->id]['spike_data'] = $spikeInfo;
                $flaggedMap[$tx->id]['badges'][] = [
                    'type' => 'spike',
                    'label' => 'Lonjakan Kategori',
                    'title' => $spikeInfo['reason'] ?? 'Pengeluaran kategori melonjak tajam periode ini',
                    'tone' => 'info',
                ];
            }
        }

        $allFlaggedIds = array_keys($flaggedMap);

        return [
            'summary' => [
                'total_anomalies' => count($allFlaggedIds),
                'duplicate_count' => count($duplicates),
                'outlier_count' => count($outliers),
                'spike_count' => count($spikes),
            ],
            'duplicates' => $duplicates,
            'outliers' => $outliers,
            'spikes' => $spikes,
            'flagged_transaction_ids' => $flaggedMap,
            'all_flagged_ids' => $allFlaggedIds,
            'duplicate_ids' => collect($duplicates)->flatMap(fn ($d) => collect($d['transactions'])->pluck('id'))->unique()->values()->all(),
            'outlier_ids' => collect($outliers)->pluck('transaction.id')->unique()->values()->all(),
            'spiked_category_ids' => $spikedCategoryIds,
        ];
    }

    /**
     * Deteksi anomali untuk daftar transaksi spesifik (misal pada halaman pagination transactions.index).
     *
     * @param  Collection<int, FinancialTransaction>|iterable  $transactions
     * @return array<int, array<string, mixed>>
     */
    public function detectForTransactions(Space $space, iterable $transactions, ?string $referenceDate = null): array
    {
        $items = $transactions instanceof Paginator
            || $transactions instanceof AbstractPaginator
            ? $transactions->items()
            : $transactions;

        $txCollection = $items instanceof Collection ? $items : collect($items);
        if ($txCollection->isEmpty()) {
            return [];
        }

        $categoryStats = $this->buildCategoryBaselines($space);
        $globalStats = $this->buildGlobalBaselines($space);

        // Tentukan rentang tanggal dari transaksi yang ada untuk lonjakan kategori
        $minDate = $txCollection->min('transaction_date');
        $maxDate = $txCollection->max('transaction_date');
        $startDate = $minDate ? Carbon::parse($minDate)->startOfMonth()->toDateString() : null;
        $endDate = $maxDate ? Carbon::parse($maxDate)->endOfMonth()->toDateString() : null;

        $spikes = $startDate && $endDate
            ? $this->detectCategorySpikes($space, $startDate, $endDate)
            : [];
        $spikedCategoryMap = collect($spikes)->keyBy('category_id');

        $result = [];

        foreach ($txCollection as $tx) {
            if (! $tx instanceof FinancialTransaction) {
                continue;
            }

            $flags = $this->emptyFlagPayload($tx);

            if ($tx->isVoid()) {
                $result[$tx->id] = $flags;

                continue;
            }

            // 1. Cek Potensi Ganda
            $potentialDuplicates = $this->findDuplicatesForSingleTransaction($space, $tx);
            if ($potentialDuplicates->isNotEmpty()) {
                $otherIds = $potentialDuplicates->pluck('id')->all();
                $flags['is_duplicate'] = true;
                $flags['duplicate_with'] = $otherIds;
                $flags['duplicate_reason'] = 'Memiliki kesamaan nominal Rp '.number_format($tx->totalAmount(), 0, ',', '.').' dengan Transaksi #'.implode(', #', $otherIds);
                $flags['badges'][] = [
                    'type' => 'duplicate',
                    'label' => 'Potensi Ganda',
                    'title' => 'Berpotensi ganda dengan Transaksi #'.implode(', #', $otherIds),
                    'tone' => 'danger',
                ];
            }

            // 2. Cek Nominal Ekstrem (Outlier)
            $outlierData = $this->evaluateTransactionOutlier($tx, $categoryStats, $globalStats);
            if ($outlierData !== null) {
                $flags['is_outlier'] = true;
                $flags['outlier_data'] = $outlierData;
                $flags['badges'][] = [
                    'type' => 'outlier',
                    'label' => 'Nominal Ekstrem',
                    'title' => $outlierData['reason'],
                    'tone' => 'warning',
                ];
            }

            // 3. Cek Lonjakan Kategori
            if ($tx->isExpense() && $tx->category_id && $spikedCategoryMap->has($tx->category_id)) {
                $spikeInfo = $spikedCategoryMap->get($tx->category_id);
                $flags['is_category_spike'] = true;
                $flags['spike_data'] = $spikeInfo;
                $flags['badges'][] = [
                    'type' => 'spike',
                    'label' => 'Lonjakan Kategori',
                    'title' => $spikeInfo['reason'] ?? 'Pengeluaran kategori melonjak tajam periode ini',
                    'tone' => 'info',
                ];
            }

            $flags['has_anomaly'] = $flags['is_duplicate'] || $flags['is_outlier'] || $flags['is_category_spike'];
            $result[$tx->id] = $flags;
        }

        return $result;
    }

    /**
     * Periksa rincian anomali lengkap untuk 1 transaksi spesifik (untuk transactions.show).
     *
     * @return array<string, mixed>
     */
    public function detectAnomaliesForSingleTransaction(FinancialTransaction $transaction): array
    {
        $space = $transaction->space;
        if (! $space) {
            return $this->emptyFlagPayload($transaction);
        }

        $categoryStats = $this->buildCategoryBaselines($space);
        $globalStats = $this->buildGlobalBaselines($space);

        $payload = $this->emptyFlagPayload($transaction);

        if ($transaction->isVoid()) {
            return $payload;
        }

        // 1. Cek Transaksi Berpotensi Ganda
        $matchingDuplicates = $this->findDuplicatesForSingleTransaction($space, $transaction);
        if ($matchingDuplicates->isNotEmpty()) {
            $payload['is_duplicate'] = true;
            $payload['duplicate_transactions'] = $matchingDuplicates;
            $payload['duplicate_with'] = $matchingDuplicates->pluck('id')->all();
            $payload['duplicate_reason'] = 'Ditemukan '.$matchingDuplicates->count().' transaksi lain dengan nominal identik (Rp '.number_format($transaction->totalAmount(), 0, ',', '.').') dan rincian serupa di tanggal berdekatan.';
            $payload['badges'][] = [
                'type' => 'duplicate',
                'label' => 'Potensi Ganda',
                'title' => $payload['duplicate_reason'],
                'tone' => 'danger',
            ];
        }

        // 2. Cek Nominal Ekstrem (Outlier)
        $outlierData = $this->evaluateTransactionOutlier($transaction, $categoryStats, $globalStats);
        if ($outlierData !== null) {
            $payload['is_outlier'] = true;
            $payload['outlier_data'] = $outlierData;
            $payload['badges'][] = [
                'type' => 'outlier',
                'label' => 'Nominal Ekstrem',
                'title' => $outlierData['reason'],
                'tone' => 'warning',
            ];
        }

        // 3. Cek Lonjakan Pengeluaran Kategori
        if ($transaction->isExpense() && $transaction->category_id) {
            $txDate = $transaction->transaction_date ? Carbon::parse($transaction->transaction_date) : $space->now();
            $startOfMonth = $txDate->copy()->startOfMonth()->toDateString();
            $endOfMonth = $txDate->copy()->endOfMonth()->toDateString();

            $spikes = $this->detectCategorySpikes($space, $startOfMonth, $endOfMonth);
            $matchingSpike = collect($spikes)->firstWhere('category_id', $transaction->category_id);

            if ($matchingSpike !== null) {
                $payload['is_category_spike'] = true;
                $payload['spike_data'] = $matchingSpike;
                $payload['badges'][] = [
                    'type' => 'spike',
                    'label' => 'Lonjakan Kategori',
                    'title' => $matchingSpike['reason'],
                    'tone' => 'info',
                ];
            }
        }

        $payload['has_anomaly'] = $payload['is_duplicate'] || $payload['is_outlier'] || $payload['is_category_spike'];

        return $payload;
    }

    /**
     * Dapatkan daftar kategori dengan lonjakan pengeluaran tajam pada periode tertentu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function detectCategorySpikes(
        Space $space,
        string $startDate,
        string $endDate,
        ?int $accountId = null
    ): array {
        $timezone = $space->timezone ?: 'Asia/Jakarta';
        $startCarbon = Carbon::parse($startDate, $timezone)->startOfDay();
        $endCarbon = Carbon::parse($endDate, $timezone)->endOfDay();

        // Hitung periode pembanding (bulan sebelumnya atau periode berdurasi sama)
        $spanDays = (int) $startCarbon->diffInDays($endCarbon) + 1;
        $prevEnd = $startCarbon->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($spanDays - 1)->startOfDay();
        $prevLabel = $prevStart->translatedFormat('d M Y').' - '.$prevEnd->translatedFormat('d M Y');

        $currentCatTotals = $this->getCategoryExpenseTotals($space->id, $startCarbon->toDateString(), $endCarbon->toDateString(), $accountId);
        $prevCatTotals = $this->getCategoryExpenseTotals($space->id, $prevStart->toDateString(), $prevEnd->toDateString(), $accountId);

        $prevMap = $prevCatTotals->keyBy('id');
        $spikes = [];

        foreach ($currentCatTotals as $curr) {
            $catId = (int) $curr->id;
            $currAmount = (int) $curr->amount;
            $prev = $prevMap->get($catId);
            $prevAmount = $prev ? (int) $prev->amount : 0;
            $diffAmount = $currAmount - $prevAmount;

            if ($diffAmount <= 0) {
                continue;
            }

            $diffPct = $prevAmount > 0 ? round(($diffAmount / $prevAmount) * 100, 1) : null;

            $isSpike = false;
            $reason = '';

            if ($prevAmount > 0) {
                if ($diffPct >= self::SPIKE_MIN_PERCENTAGE && $diffAmount >= self::SPIKE_MIN_DIFF_AMOUNT) {
                    $isSpike = true;
                    $reason = "Pengeluaran kategori {$curr->name} melonjak +{$diffPct}% (naik Rp ".number_format($diffAmount, 0, ',', '.').") dibanding periode sebelumnya ({$prevLabel}).";
                }
            } else {
                if ($currAmount >= self::SPIKE_NEW_CATEGORY_MIN_AMOUNT) {
                    $isSpike = true;
                    $reason = "Pengeluaran kategori baru {$curr->name} mencapai Rp ".number_format($currAmount, 0, ',', '.').' tanpa histori belanja pada periode sebelumnya.';
                }
            }

            if ($isSpike) {
                $spikes[] = [
                    'category_id' => $catId,
                    'category_name' => $curr->name,
                    'current_amount' => $currAmount,
                    'prev_amount' => $prevAmount,
                    'diff_amount' => $diffAmount,
                    'diff_pct' => $diffPct,
                    'tx_count' => (int) $curr->count,
                    'reason' => $reason,
                ];
            }
        }

        // Urutkan lonjakan dari selisih kenaikan terbesar
        usort($spikes, fn ($a, $b) => $b['diff_amount'] <=> $a['diff_amount']);

        return $spikes;
    }

    /**
     * Bangun data baseline statistik pengeluaran per kategori di ruang aktif.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildCategoryBaselines(Space $space): array
    {
        // Ambil transaksi posted 180 hari terakhir untuk baseline yang relevan
        $sinceDate = $space->now()->subDays(180)->toDateString();

        $rows = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->where('financial_transactions.space_id', $space->id)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereNotNull('financial_transactions.category_id')
            ->whereDate('financial_transactions.transaction_date', '>=', $sinceDate)
            ->where('transaction_entries.amount_signed', '<', 0)
            ->select(
                'financial_transactions.category_id',
                DB::raw('ABS(transaction_entries.amount_signed) as amount')
            )
            ->get();

        $byCategory = $rows->groupBy('category_id');
        $baselines = [];

        foreach ($byCategory as $catId => $catRows) {
            $amounts = $catRows->pluck('amount')->map(fn ($val) => (float) $val);
            $count = $amounts->count();

            if ($count === 0) {
                continue;
            }

            $mean = $amounts->avg();
            $variance = $count > 1
                ? $amounts->map(fn ($x) => pow($x - $mean, 2))->sum() / ($count - 1)
                : 0.0;
            $stddev = sqrt($variance);

            $baselines[(int) $catId] = [
                'count' => $count,
                'mean' => (float) $mean,
                'stddev' => (float) $stddev,
                'max' => (float) $amounts->max(),
                'min' => (float) $amounts->min(),
            ];
        }

        return $baselines;
    }

    /**
     * Bangun data baseline statistik global per jenis transaksi di ruang aktif.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function buildGlobalBaselines(Space $space): array
    {
        $sinceDate = $space->now()->subDays(180)->toDateString();

        $rows = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->where('financial_transactions.space_id', $space->id)
            ->where('financial_transactions.status', 'posted')
            ->whereDate('financial_transactions.transaction_date', '>=', $sinceDate)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereIn('financial_transactions.type', ['expense', 'credit_payment'])
                        ->where('transaction_entries.amount_signed', '<', 0);
                })->orWhere(function ($sub) {
                    $sub->whereIn('financial_transactions.type', ['income', 'transfer'])
                        ->where('transaction_entries.amount_signed', '>', 0);
                });
            })
            ->select(
                'financial_transactions.type',
                DB::raw('ABS(transaction_entries.amount_signed) as amount')
            )
            ->get();

        $byType = $rows->groupBy('type');
        $baselines = [];

        foreach ($byType as $type => $typeRows) {
            $amounts = $typeRows->pluck('amount')->map(fn ($val) => (float) $val);
            $count = $amounts->count();

            if ($count === 0) {
                continue;
            }

            $mean = $amounts->avg();
            $variance = $count > 1
                ? $amounts->map(fn ($x) => pow($x - $mean, 2))->sum() / ($count - 1)
                : 0.0;
            $stddev = sqrt($variance);

            $baselines[$type] = [
                'count' => $count,
                'mean' => (float) $mean,
                'stddev' => (float) $stddev,
            ];
        }

        return $baselines;
    }

    /**
     * Evaluasi apakah satu transaksi merupakan outlier nominal.
     *
     * @return array<string, mixed>|null
     */
    protected function evaluateTransactionOutlier(
        FinancialTransaction $tx,
        array $categoryStats,
        array $globalStats
    ): ?array {
        $amount = (float) $tx->totalAmount();
        if ($amount <= 0) {
            return null;
        }

        // Cek baseline kategori jika bertipe expense dan memiliki kategori
        if ($tx->isExpense() && $tx->category_id && isset($categoryStats[$tx->category_id])) {
            $catStat = $categoryStats[$tx->category_id];

            // Kategori memiliki minimal 3 transaksi historis
            if ($catStat['count'] >= 3) {
                $mean = $catStat['mean'];
                $stddev = $catStat['stddev'];
                $diff = $amount - $mean;

                $thresholdStd = $stddev > 0 ? ($mean + (self::OUTLIER_STDDEV_MULTIPLIER * $stddev)) : ($mean * self::OUTLIER_MULTIPLIER_CATEGORY);
                $thresholdMultiplier = $mean * self::OUTLIER_MULTIPLIER_CATEGORY;
                $isThresholdMet = ($amount >= $thresholdStd || $amount >= $thresholdMultiplier);

                if ($isThresholdMet && $diff >= self::OUTLIER_MIN_DIFF_CATEGORY) {
                    $multiplier = round($amount / max(1.0, $mean), 1);
                    $categoryName = $tx->category?->name ?? 'Kategori';

                    return [
                        'type' => 'category',
                        'scope' => 'category',
                        'transaction' => $tx,
                        'amount' => (int) $amount,
                        'baseline_avg' => (int) round($mean),
                        'multiplier' => $multiplier,
                        'diff_amount' => (int) round($diff),
                        'category_name' => $categoryName,
                        'reason' => 'Nominal Rp '.number_format($amount, 0, ',', '.')." adalah {$multiplier}x di atas rata-rata kebiasaan kategori {$categoryName} (rata-rata: Rp ".number_format($mean, 0, ',', '.').').',
                    ];
                }

                // Jika kategori sudah dicek dan tidak anomali terhadap kategorinya, jangan fallback global
                return null;
            }
        }

        // Fallback ke baseline global ruang
        $type = $tx->type;
        if (isset($globalStats[$type])) {
            $global = $globalStats[$type];
            if ($global['count'] >= 5) {
                $mean = $global['mean'];
                $stddev = $global['stddev'];
                $diff = $amount - $mean;

                $thresholdStd = $stddev > 0 ? ($mean + (self::OUTLIER_STDDEV_MULTIPLIER * $stddev)) : ($mean * self::OUTLIER_MULTIPLIER_GLOBAL);
                $thresholdMultiplier = $mean * self::OUTLIER_MULTIPLIER_GLOBAL;
                $isThresholdMet = ($amount >= $thresholdStd || $amount >= $thresholdMultiplier);

                if ($isThresholdMet && $diff >= self::OUTLIER_MIN_DIFF_GLOBAL) {
                    $multiplier = round($amount / max(1.0, $mean), 1);
                    $typeName = match ($type) {
                        'expense' => 'pengeluaran',
                        'income' => 'pemasukan',
                        'transfer' => 'transfer',
                        default => 'transaksi',
                    };

                    return [
                        'type' => 'global',
                        'scope' => 'global',
                        'transaction' => $tx,
                        'amount' => (int) $amount,
                        'baseline_avg' => (int) round($mean),
                        'multiplier' => $multiplier,
                        'diff_amount' => (int) round($diff),
                        'category_name' => $tx->category?->name,
                        'reason' => 'Nominal Rp '.number_format($amount, 0, ',', '.')." adalah {$multiplier}x di atas rata-rata {$typeName} ruang (rata-rata: Rp ".number_format($mean, 0, ',', '.').').',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Cari transaksi duplikat potensial untuk 1 transaksi spesifik.
     *
     * @return Collection<int, FinancialTransaction>
     */
    protected function findDuplicatesForSingleTransaction(Space $space, FinancialTransaction $tx): Collection
    {
        $amount = $tx->totalAmount();
        if ($amount <= 0) {
            return collect();
        }

        $txDate = Carbon::parse($tx->transaction_date);
        $minDate = $txDate->copy()->subDays(self::DUPLICATE_MAX_DAYS_DIFF)->toDateString();
        $maxDate = $txDate->copy()->addDays(self::DUPLICATE_MAX_DAYS_DIFF)->toDateString();

        $candidates = FinancialTransaction::query()
            ->where('space_id', $space->id)
            ->where('status', 'posted')
            ->where('type', $tx->type)
            ->where('id', '!=', $tx->id)
            ->whereBetween('transaction_date', [$minDate, $maxDate])
            ->with(['entries.account', 'category'])
            ->get();

        $sourceAccountId = $tx->sourceAccount()?->id;
        $destAccountId = $tx->destinationAccount()?->id;
        $normalizedDesc = strtolower(trim((string) $tx->description));

        return $candidates->filter(function (FinancialTransaction $c) use (
            $amount,
            $sourceAccountId,
            $destAccountId,
            $normalizedDesc,
            $tx
        ): bool {
            if ($c->totalAmount() !== $amount) {
                return false;
            }

            // Minimal 1 sinyal kesamaan pendukung:
            // Sinyal A: Akun sumber sama
            if ($sourceAccountId && $c->sourceAccount()?->id === $sourceAccountId) {
                return true;
            }

            // Sinyal B: Akun tujuan sama (misal pada income/transfer)
            if ($destAccountId && $c->destinationAccount()?->id === $destAccountId) {
                return true;
            }

            // Sinyal C: Kategori sama
            if ($tx->category_id && $c->category_id === $tx->category_id) {
                return true;
            }

            // Sinyal D: Keterangan identik atau mirip
            $candDesc = strtolower(trim((string) $c->description));
            if ($normalizedDesc !== '' && $normalizedDesc === $candDesc) {
                return true;
            }

            // Sinyal E: Tanggal sama persis
            if ($c->transaction_date?->toDateString() === $tx->transaction_date?->toDateString()) {
                return true;
            }

            return false;
        })->values();
    }

    /**
     * Deteksi kelompok transaksi duplikat di seluruh ruang.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function detectDuplicatesInSpace(
        Space $space,
        string $startDate,
        string $endDate,
        ?int $accountId = null
    ): array {
        // Ambil transaksi posted dengan rentang melebar 1 hari ke sebelum dan sesudah untuk mendeteksi batas tanggal
        $windowStart = Carbon::parse($startDate)->subDays(self::DUPLICATE_MAX_DAYS_DIFF)->toDateString();
        $windowEnd = Carbon::parse($endDate)->addDays(self::DUPLICATE_MAX_DAYS_DIFF)->toDateString();

        $transactions = $this->getPeriodTransactionsQuery($space->id, $windowStart, $windowEnd, $accountId)
            ->with(['category', 'entries.account', 'member'])
            ->get();

        // Kelompokkan berdasarkan jenis transaksi dan nominal
        $byTypeAndAmount = $transactions->groupBy(function (FinancialTransaction $tx) {
            return $tx->type.'_'.$tx->totalAmount();
        });

        $duplicateGroups = [];
        $handledPairs = [];

        foreach ($byTypeAndAmount as $groupKey => $group) {
            if ($group->count() < 2) {
                continue;
            }

            $count = $group->count();
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $t1 = $group[$i];
                    $t2 = $group[$j];

                    $pairKey = min($t1->id, $t2->id).'_'.max($t1->id, $t2->id);
                    if (isset($handledPairs[$pairKey])) {
                        continue;
                    }

                    $daysDiff = abs(Carbon::parse($t1->transaction_date)->diffInDays(Carbon::parse($t2->transaction_date)));
                    if ($daysDiff > self::DUPLICATE_MAX_DAYS_DIFF) {
                        continue;
                    }

                    // Periksa sinyal pendukung kesamaan
                    $isDuplicate = false;
                    $reasons = [];

                    $sameDate = $t1->transaction_date?->toDateString() === $t2->transaction_date?->toDateString();
                    if ($sameDate) {
                        $reasons[] = 'tanggal yang sama ('.$t1->transaction_date->format('d M Y').')';
                    } else {
                        $reasons[] = 'tanggal berdekatan ('.$t1->transaction_date->format('d M').' & '.$t2->transaction_date->format('d M').')';
                    }

                    $s1 = $t1->sourceAccount()?->id;
                    $s2 = $t2->sourceAccount()?->id;
                    if ($s1 && $s2 && $s1 === $s2) {
                        $isDuplicate = true;
                        $reasons[] = 'rekening yang sama ('.$t1->sourceAccount()->name.')';
                    }

                    if ($t1->category_id && $t1->category_id === $t2->category_id) {
                        $isDuplicate = true;
                        $reasons[] = 'kategori yang sama ('.$t1->category?->name.')';
                    }

                    $d1 = strtolower(trim((string) $t1->description));
                    $d2 = strtolower(trim((string) $t2->description));
                    if ($d1 !== '' && $d1 === $d2) {
                        $isDuplicate = true;
                        $reasons[] = 'keterangan serupa ("'.$t1->description.'")';
                    }

                    // Jika tanggal sama persis dan nominal sama, otomatis terindikasi
                    if ($sameDate) {
                        $isDuplicate = true;
                    }

                    if ($isDuplicate) {
                        $handledPairs[$pairKey] = true;
                        $amount = $t1->totalAmount();

                        $duplicateGroups[] = [
                            'amount' => $amount,
                            'transactions' => [$t1, $t2],
                            'reason' => 'Nominal sama persis Rp '.number_format($amount, 0, ',', '.').' dengan '.implode(', ', $reasons).'.',
                        ];
                    }
                }
            }
        }

        return $duplicateGroups;
    }

    /**
     * Deteksi outlier pada koleksi transaksi.
     *
     * @param  Collection<int, FinancialTransaction>  $transactions
     * @return array<int, array<string, mixed>>
     */
    protected function detectOutliersInCollection(Collection $transactions, array $categoryStats, array $globalStats): array
    {
        $outliers = [];

        foreach ($transactions as $tx) {
            if ($tx->isVoid()) {
                continue;
            }

            $outlierData = $this->evaluateTransactionOutlier($tx, $categoryStats, $globalStats);
            if ($outlierData !== null) {
                $outliers[] = $outlierData;
            }
        }

        // Urutkan outlier dari rasio multiplier terbesar
        usort($outliers, fn ($a, $b) => $b['multiplier'] <=> $a['multiplier']);

        return $outliers;
    }

    /**
     * Kueri dasar transaksi dalam periode.
     */
    protected function getPeriodTransactionsQuery(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): Builder
    {
        $query = FinancialTransaction::query()
            ->where('space_id', $spaceId)
            ->where('status', 'posted')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($accountId !== null) {
            $query->whereHas('entries', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            });
        }

        return $query;
    }

    /**
     * Hitung total pengeluaran per kategori dalam periode.
     *
     * @return Collection<int, object>
     */
    protected function getCategoryExpenseTotals(int $spaceId, string $startDate, string $endDate, ?int $accountId = null): Collection
    {
        $query = DB::table('financial_transactions')
            ->join('categories', 'financial_transactions.category_id', '=', 'categories.id')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->where('transaction_entries.amount_signed', '<', 0);

        if ($accountId !== null) {
            $query->where('transaction_entries.account_id', $accountId);
        }

        return $query->select(
            'categories.id',
            'categories.name',
            DB::raw('ABS(SUM(transaction_entries.amount_signed)) as amount'),
            DB::raw('COUNT(DISTINCT financial_transactions.id) as count')
        )
            ->groupBy('categories.id', 'categories.name')
            ->get();
    }

    /**
     * Struktur payload anomali kosong standar untuk 1 transaksi.
     *
     * @return array<string, mixed>
     */
    protected function emptyFlagPayload(FinancialTransaction|int $tx): array
    {
        $id = $tx instanceof FinancialTransaction ? $tx->id : (int) $tx;

        return [
            'transaction_id' => $id,
            'has_anomaly' => false,
            'is_outlier' => false,
            'is_duplicate' => false,
            'is_category_spike' => false,
            'duplicate_with' => [],
            'duplicate_reason' => null,
            'outlier_data' => null,
            'spike_data' => null,
            'badges' => [],
        ];
    }
}
