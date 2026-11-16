<x-layouts.app title="Rincian Transaksi #{{ $transaction->id }}">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <x-back-link :fallback="route('transactions.index')" label="Kembali ke daftar transaksi" />
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / TRANSAKSI</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Rincian Transaksi #{{ $transaction->id }}
            </h1>
            <p class="text-xs text-muted">
                {{ $transaction->description }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button 
                type="button" 
                onclick="toggleBalanceVisibility()" 
                class="privacy-toggle-btn inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Nominal</span>
            </button>
            @if (! $transaction->isVoid())
                @if (in_array($transaction->type, ['expense', 'income'], true))
                    <button type="button" onclick="openSplitModal()" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-forest dark:text-blue-300">
                        <span>✂️ {{ $transaction->isSplit() ? 'Kelola Pemisahan' : 'Pisahkan Transaksi' }}</span>
                    </button>
                    <button type="button" onclick="openSplitPaymentModal()" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-purple-700 dark:text-purple-300">
                        <span>💳 {{ $transaction->isSplitPayment() ? 'Kelola Split Rekening' : 'Split Rekening Pembayaran' }}</span>
                    </button>
                @endif
                <a href="{{ route('transactions.edit', $transaction) }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px]">
                    <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                    <span>Koreksi Transaksi</span>
                </a>
                <button type="button" onclick="document.getElementById('void-modal').classList.remove('hidden')" class="btn-arus-danger min-h-[44px] sm:min-h-[38px]">
                    <svg class="w-4 h-4 text-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <span>Batalkan (Void)</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Alert status jika void -->
    @if ($transaction->isVoid())
        <div class="p-4 mb-6 rounded-xl border border-red/30 bg-red/5 text-xs text-red flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold uppercase tracking-wider text-[11px] px-2 py-0.5 rounded bg-red/10 border border-red/20 shrink-0">Status: Void</span>
                <span>Transaksi ini telah dibatalkan dan tidak lagi mempengaruhi saldo rekening mana pun.</span>
            </div>
            <span class="text-muted text-[11px] shrink-0">Versi terakhir: v{{ $transaction->version }}</span>
        </div>
    @endif

    <!-- Metric Cards Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @php
            $isIncome = $transaction->isIncome();
            $isExpense = $transaction->isExpense();
            $isTransfer = $transaction->isTransfer();
            $amount = $transaction->totalAmount();
        @endphp

        <x-metric-card 
            label="NOMINAL TRANSAKSI" 
            :value="'Rp ' . number_format($amount, 0, ',', '.')" 
            :dark="$isIncome ? 'green' : ($isExpense ? 'red' : 'blue')" 
            :sub="'Tipe: ' . strtoupper($transaction->type)" 
        />

        <x-metric-card 
            label="TANGGAL & WAKTU BUKU" 
            :value="$transaction->transaction_date->format('d M Y') . ($transaction->formattedTime() ? ' • ' . $transaction->formattedTime() : '')" 
            :sub="'Periode: ' . $transaction->transaction_date->translatedFormat('F Y')" 
        />

        <x-metric-card 
            label="STATUS & VERSI" 
            :value="strtoupper($transaction->status)" 
            :sub="'Versi Optimistic Lock: v' . $transaction->version" 
        />
    </div>

    <!-- Deteksi Anomali Transaksi -->
    @if (!empty($anomalyData['has_anomaly']))
        <div class="mb-6 rounded-2xl border border-amber-500/30 bg-amber-500/5 dark:bg-amber-950/20 p-5 space-y-4">
            <div class="flex items-center justify-between gap-3 border-b border-amber-500/20 pb-3 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-700 dark:text-amber-300 flex items-center justify-center text-sm font-bold shrink-0">
                        ⚠️
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-charcoal dark:text-amber-100">Peringatan: Transaksi Tidak Biasa</h2>
                        <p class="text-xs text-muted dark:text-amber-200/80">Sistem mendeteksi indikasi anomali pada pencatatan transaksi ini.</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach ($anomalyData['badges'] as $b)
                        @php
                            $badgeClass = match($b['type']) {
                                'duplicate' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-900',
                                'outlier' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-900',
                                'spike' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-900',
                                default => 'bg-cream text-muted border-line',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                            {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3.5">
                @if (!empty($anomalyData['is_duplicate']))
                    <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900 bg-panel space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-rose-600 dark:text-rose-400 font-bold text-sm shrink-0">⚠️</span>
                            <div class="flex-1">
                                <h3 class="text-xs font-bold text-rose-700 dark:text-rose-300">Potensi Transaksi Ganda (Duplikat)</h3>
                                <p class="text-xs text-muted mt-0.5">{{ $anomalyData['duplicate_reason'] }}</p>
                            </div>
                        </div>

                        @if (!empty($anomalyData['duplicate_transactions']))
                            <div class="divide-y divide-line rounded-lg border border-line bg-surface/50 overflow-hidden text-xs">
                                @foreach ($anomalyData['duplicate_transactions'] as $dupTx)
                                    <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <div class="font-bold text-charcoal">
                                                <a href="{{ route('transactions.show', $dupTx) }}" class="text-forest hover:underline">
                                                    Transaksi #{{ $dupTx->id }}: {{ $dupTx->description }}
                                                </a>
                                            </div>
                                            <div class="text-[11px] text-muted mt-0.5">
                                                {{ $dupTx->transaction_date->format('d M Y') }} &bull;
                                                {{ $dupTx->sourceAccount()?->name ?? $dupTx->destinationAccount()?->name }} &bull;
                                                {{ $dupTx->category?->name ?? 'Tanpa Kategori' }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                            <span class="font-bold font-mono text-charcoal">
                                                Rp {{ number_format($dupTx->totalAmount(), 0, ',', '.') }}
                                            </span>
                                            <a href="{{ route('transactions.show', $dupTx) }}" class="btn-arus-secondary px-2.5 py-1 text-xs font-bold">
                                                Lihat #{{ $dupTx->id }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if (!empty($anomalyData['is_outlier']) && !empty($anomalyData['outlier_data']))
                    @php $od = $anomalyData['outlier_data']; @endphp
                    <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900 bg-panel space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-amber-600 dark:text-amber-400 font-bold text-sm shrink-0">⚠️</span>
                            <div class="flex-1">
                                <h3 class="text-xs font-bold text-amber-700 dark:text-amber-300">Nominal Jauh di Atas Kebiasaan (Outlier)</h3>
                                <p class="text-xs text-muted mt-0.5">{{ $od['reason'] }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Nominal Transaksi</span>
                                <span class="font-bold font-mono text-charcoal mt-0.5 block">Rp {{ number_format($od['amount'], 0, ',', '.') }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Rata-rata Historis</span>
                                <span class="font-bold font-mono text-charcoal mt-0.5 block">Rp {{ number_format($od['baseline_avg'], 0, ',', '.') }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Rasio Lonjakan</span>
                                <span class="font-bold font-mono text-amber-600 dark:text-amber-400 mt-0.5 block">{{ $od['multiplier'] }}x lipat</span>
                            </div>
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Selisih Deviasi</span>
                                <span class="font-bold font-mono text-rose-600 dark:text-rose-400 mt-0.5 block">+Rp {{ number_format($od['diff_amount'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if (!empty($anomalyData['is_category_spike']) && !empty($anomalyData['spike_data']))
                    @php $sd = $anomalyData['spike_data']; @endphp
                    <div class="p-4 rounded-xl border border-purple-200 dark:border-purple-900 bg-panel space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-purple-600 dark:text-purple-400 font-bold text-sm shrink-0">🔥</span>
                            <div class="flex-1">
                                <h3 class="text-xs font-bold text-purple-700 dark:text-purple-300">Pengeluaran Kategori Melonjak Tajam</h3>
                                <p class="text-xs text-muted mt-0.5">{{ $sd['reason'] }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Total Kategori Periode Ini</span>
                                <span class="font-bold font-mono text-charcoal mt-0.5 block">Rp {{ number_format($sd['current_amount'], 0, ',', '.') }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Periode Pembanding</span>
                                <span class="font-bold font-mono text-charcoal mt-0.5 block">Rp {{ number_format($sd['prev_amount'], 0, ',', '.') }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg border border-line bg-surface/40">
                                <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Kenaikan Total</span>
                                <span class="font-bold font-mono text-purple-600 dark:text-purple-400 mt-0.5 block">
                                    {{ $sd['diff_pct'] !== null ? '+'.$sd['diff_pct'].'%' : 'Kategori Baru' }} (+Rp {{ number_format($sd['diff_amount'], 0, ',', '.') }})
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Informasi Transaksi -->
        <div class="lg:col-span-2 space-y-6">
            <x-panel eyebrow="RINCIAN">
                <div class="divide-y divide-line text-xs">
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Jenis Transaksi</span>
                        @if ($transaction->isIncome())
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900">
                                PEMASUKAN
                            </span>
                        @elseif ($transaction->isExpense())
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900">
                                PENGELUARAN
                            </span>
                        @else
                            <x-badge variant="default">
                                {{ strtoupper($transaction->type) }}
                            </x-badge>
                        @endif
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Kategori</span>
                        <span class="font-bold text-charcoal">
                            @if ($transaction->isSplit())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-forest/10 text-forest dark:bg-forest/20 dark:text-forest-light border border-forest/20">
                                    <span>✂️ Terbagi ({{ $transaction->splits->count() }} Kategori)</span>
                                </span>
                            @else
                                {{ $transaction->category?->name ?? '(Transfer antar-rekening)' }}
                            @endif
                        </span>
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">{{ $transaction->isIncome() ? 'Rekening Tujuan' : 'Rekening Pembayaran' }}</span>
                        <span class="font-bold text-charcoal">
                            @if ($transaction->isSplitPayment())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/20">
                                    <span>💳 Split Payment ({{ $transaction->sourceEntries()->count() }} Rekening)</span>
                                </span>
                            @elseif ($transaction->isTransfer())
                                <span>{{ $transaction->sourceAccount()?->name }} &rarr; {{ $transaction->destinationAccount()?->name }}</span>
                            @else
                                @php
                                    $primaryAcc = $transaction->isIncome() ? $transaction->destinationAccount() : $transaction->sourceAccount();
                                @endphp
                                @if ($primaryAcc)
                                    <a href="{{ route('accounts.show', $primaryAcc) }}" class="hover:text-forest hover:underline">
                                        {{ $primaryAcc->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            @endif
                        </span>
                    </div>

                    @if ($transaction->isTransfer() && (float)($transaction->fee_amount ?? 0) > 0)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Biaya Admin Transfer</span>
                            <span class="font-bold text-red privacy-balance" data-raw-value="Rp {{ number_format($transaction->fee_amount, 0, ',', '.') }}">
                                Rp {{ number_format($transaction->fee_amount, 0, ',', '.') }}
                            </span>
                        </div>
                        @if ($transaction->feeCategory)
                            <div class="py-3 flex justify-between items-center">
                                <span class="text-muted">Kategori Biaya Admin</span>
                                <span class="font-bold text-charcoal">
                                    {{ $transaction->feeCategory->name }}
                                </span>
                            </div>
                        @endif
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Total Pengurangan Rekening Asal</span>
                            <span class="font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($transaction->totalDeductedFromSource(), 0, ',', '.') }}">
                                Rp {{ number_format($transaction->totalDeductedFromSource(), 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    @if ($transaction->member)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Pihak / Member</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-2xs" style="background-color: {{ $transaction->member->color ?: '#0f4c3a' }};">
                                {{ $transaction->member->name }}
                            </span>
                        </div>
                    @endif

                    @if ($transaction->recurringOccurrence)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Jadwal Berulang</span>
                            <a href="{{ route('recurring.show', $transaction->recurringOccurrence->schedule) }}" class="font-bold text-forest hover:underline inline-flex items-center gap-1">
                                <span>{{ $transaction->recurringOccurrence->schedule?->name }}</span>
                                <span class="text-muted text-[11px] font-normal">(Periode {{ $transaction->recurringOccurrence->due_date->format('d/m/Y') }})</span>
                            </a>
                        </div>
                    @endif

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Keterangan</span>
                        <span class="font-semibold text-charcoal text-right max-w-xs sm:max-w-md">
                            {{ $transaction->description }}
                        </span>
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Status Mutasi</span>
                        @if ($transaction->isVoid())
                            <x-badge variant="inactive">VOID (DIBATALKAN)</x-badge>
                        @else
                            <x-badge variant="active">POSTED (TERCATAT)</x-badge>
                        @endif
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Dicatat Oleh</span>
                        <span class="text-charcoal font-medium">
                            {{ $transaction->creator?->name ?? 'Sistem' }} ({{ $transaction->created_at->format('d M Y H:i') }})
                        </span>
                    </div>

                    @if ($transaction->updater)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Terakhir Diperbarui</span>
                            <span class="text-charcoal font-medium">
                                {{ $transaction->updater->name }} ({{ $transaction->updated_at->format('d M Y H:i') }})
                            </span>
                        </div>
                    @endif

                    @if ($transaction->request_key)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Kunci Idempoten (Request Key)</span>
                            <span class="font-mono text-[11px] text-muted">
                                {{ $transaction->request_key }}
                            </span>
                        </div>
                    @endif
                </div>
            </x-panel>

            <!-- Pemisahan Transaksi (Split Allocations) -->
            @if ($transaction->isSplit())
                <x-panel eyebrow="RINCIAN PEMISAHAN TRANSAKSI (SPLIT)">
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-line">
                            <div>
                                <h3 class="text-sm font-bold text-charcoal">Alokasi {{ $transaction->splits->count() }} Kategori / Member</h3>
                                <p class="text-xs text-muted">Satu mutasi rekening dialokasikan ke beberapa pos anggaran secara terpisah.</p>
                            </div>
                            @if (! $transaction->isVoid())
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openSplitModal()" class="px-2.5 py-1.5 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-semibold text-charcoal transition cursor-pointer">
                                        ✏️ Kelola Alokasi
                                    </button>
                                    <button type="button" onclick="document.getElementById('unsplit-modal').classList.remove('hidden')" class="px-2.5 py-1.5 rounded-lg border border-red/20 bg-red/5 hover:bg-red/10 text-xs font-semibold text-red transition cursor-pointer">
                                        Batalkan Split
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Progress Bar Proporsi Split -->
                        @php
                            $totalTxAmount = (float) $transaction->totalAmount();
                            $colors = ['#0f4c3a', '#d97706', '#2563eb', '#7c3aed', '#db2777', '#059669', '#475569'];
                        @endphp
                        <div class="space-y-1.5">
                            <div class="h-2 w-full bg-cream rounded-full overflow-hidden flex">
                                @foreach ($transaction->splits as $idx => $s)
                                    @php
                                        $pct = $totalTxAmount > 0 ? round(($s->amount / $totalTxAmount) * 100, 1) : 0;
                                        $barColor = $colors[$idx % count($colors)];
                                    @endphp
                                    <div style="width: {{ $pct }}%; background-color: {{ $barColor }};" title="{{ $s->category?->name }}: {{ $pct }}%"></div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Tabel Alokasi Mobile -->
                        <div class="sm:hidden divide-y divide-line text-xs">
                            @foreach ($transaction->splits as $idx => $s)
                                @php
                                    $pct = $totalTxAmount > 0 ? round(($s->amount / $totalTxAmount) * 100, 1) : 0;
                                    $dotColor = $colors[$idx % count($colors)];
                                @endphp
                                <div class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $dotColor }};"></span>
                                            <span class="font-bold text-charcoal truncate">{{ $s->category?->name ?? 'Tanpa Kategori' }}</span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-1.5 text-[11px] text-muted flex-wrap">
                                            @if ($s->member)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-bold text-white shadow-2xs" style="background-color: {{ $s->member->color ?: '#0f4c3a' }};">
                                                    {{ $s->member->name }}
                                                </span>
                                            @endif
                                            @if ($s->notes)
                                                <span>{{ $s->notes }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-mono font-bold text-charcoal">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($s->amount, 0, ',', '.') }}">
                                                Rp {{ number_format($s->amount, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-muted font-mono font-semibold">{{ $pct }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Tabel Alokasi Desktop -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                        <th class="py-2.5 px-3">Kategori</th>
                                        <th class="py-2.5 px-3">Pihak / Member</th>
                                        <th class="py-2.5 px-3">Catatan</th>
                                        <th class="py-2.5 px-3 text-right">Porsi</th>
                                        <th class="py-2.5 px-3 text-right">Nominal Alokasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($transaction->splits as $idx => $s)
                                        @php
                                            $pct = $totalTxAmount > 0 ? round(($s->amount / $totalTxAmount) * 100, 1) : 0;
                                            $dotColor = $colors[$idx % count($colors)];
                                        @endphp
                                        <tr class="hover:bg-cream/20 transition-colors">
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $dotColor }};"></span>
                                                    <span class="font-bold text-charcoal">{{ $s->category?->name ?? 'Tanpa Kategori' }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3">
                                                @if ($s->member)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background-color: {{ $s->member->color ?: '#0f4c3a' }};">
                                                        {{ $s->member->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3 text-muted">
                                                {{ $s->notes ?: '-' }}
                                            </td>
                                            <td class="py-3 px-3 text-right font-mono font-bold text-muted">
                                                {{ $pct }}%
                                            </td>
                                            <td class="py-3 px-3 text-right font-mono font-bold text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($s->amount, 0, ',', '.') }}">
                                                    Rp {{ number_format($s->amount, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-line bg-cream/30 font-bold text-charcoal">
                                        <td colspan="3" class="py-2.5 px-3">Total Alokasi Pemisahan</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400">100%</td>
                                        <td class="py-2.5 px-3 text-right font-mono">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalTxAmount, 0, ',', '.') }}">
                                                Rp {{ number_format($totalTxAmount, 0, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </x-panel>
            @elseif (! $transaction->isVoid() && in_array($transaction->type, ['expense', 'income'], true))
                <div class="p-4 sm:p-5 rounded-2xl border border-dashed border-forest/30 bg-forest/5 dark:bg-forest/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 rounded-xl bg-forest/10 text-forest dark:text-forest-light flex items-center justify-center text-sm font-bold shrink-0">
                            ✂️
                        </span>
                        <div>
                            <h3 class="text-xs font-bold text-charcoal dark:text-forest-light">Pisahkan Transaksi ke Beberapa Kategori / Member</h3>
                            <p class="text-xs text-muted mt-0.5 max-w-xl">
                                Satu struk belanja atau transaksi dapat dibagi ke beberapa pos anggaran (misal: belanja kebutuhan rumah dan belanja pribadi).
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="openSplitModal()" class="btn-arus-primary text-xs shrink-0 whitespace-nowrap cursor-pointer">
                        ✂️ Pisahkan Transaksi
                    </button>
                </div>
            @endif

            <!-- Rincian Rekening Pembayaran (Split Payment) -->
            @if ($transaction->isSplitPayment())
                <x-panel eyebrow="RINCIAN REKENING PEMBAYARAN (SPLIT PAYMENT)">
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-line">
                            <div>
                                <h3 class="text-sm font-bold text-charcoal">Alokasi {{ $transaction->sourceEntries()->count() }} Rekening Pembayaran</h3>
                                <p class="text-xs text-muted">Total transaksi dibayar menggunakan gabungan beberapa rekening / metode pembayaran.</p>
                            </div>
                            @if (! $transaction->isVoid())
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openSplitPaymentModal()" class="px-2.5 py-1.5 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-semibold text-charcoal transition cursor-pointer">
                                        ✏️ Kelola Rekening
                                    </button>
                                    <button type="button" onclick="openUnsplitPaymentModal()" class="px-2.5 py-1.5 rounded-lg border border-red/20 bg-red/5 hover:bg-red/10 text-xs font-semibold text-red transition cursor-pointer">
                                        Batalkan Split
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Progress Bar Proporsi Split Rekening -->
                        @php
                            $totalTxAmount = (float) $transaction->totalAmount();
                            $paymentColors = ['#7c3aed', '#2563eb', '#059669', '#d97706', '#db2777', '#0f4c3a'];
                            $entriesList = $transaction->isExpense() ? $transaction->sourceEntries() : $transaction->destinationEntries();
                        @endphp
                        <div class="space-y-1.5">
                            <div class="h-2 w-full bg-cream rounded-full overflow-hidden flex">
                                @foreach ($entriesList as $idx => $entry)
                                    @php
                                        $entryAmt = abs((float) $entry->amount_signed);
                                        $pct = $totalTxAmount > 0 ? round(($entryAmt / $totalTxAmount) * 100, 1) : 0;
                                        $barColor = $paymentColors[$idx % count($paymentColors)];
                                    @endphp
                                    <div style="width: {{ $pct }}%; background-color: {{ $barColor }};" title="{{ $entry->account?->name }}: {{ $pct }}%"></div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Breakdown Mobile -->
                        <div class="sm:hidden divide-y divide-line text-xs">
                            @foreach ($entriesList as $idx => $entry)
                                @php
                                    $entryAmt = abs((float) $entry->amount_signed);
                                    $pct = $totalTxAmount > 0 ? round(($entryAmt / $totalTxAmount) * 100, 1) : 0;
                                    $dotColor = $paymentColors[$idx % count($paymentColors)];
                                @endphp
                                <div class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $dotColor }};"></span>
                                            <a href="{{ route('accounts.show', $entry->account) }}" class="font-bold text-charcoal hover:text-forest hover:underline truncate">
                                                {{ $entry->account?->name ?? 'Rekening' }}
                                            </a>
                                        </div>
                                        <div class="mt-0.5 text-[11px] text-muted uppercase">
                                            {{ $entry->account?->type }} &bull; Porsi {{ $pct }}%
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-mono font-bold text-charcoal">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($entryAmt, 0, ',', '.') }}">
                                                Rp {{ number_format($entryAmt, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Breakdown Desktop Table -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                        <th class="py-2.5 px-3">Rekening Pembayaran</th>
                                        <th class="py-2.5 px-3">Tipe</th>
                                        <th class="py-2.5 px-3 text-right">Porsi</th>
                                        <th class="py-2.5 px-3 text-right">Nominal Terpotong</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($entriesList as $idx => $entry)
                                        @php
                                            $entryAmt = abs((float) $entry->amount_signed);
                                            $pct = $totalTxAmount > 0 ? round(($entryAmt / $totalTxAmount) * 100, 1) : 0;
                                            $dotColor = $paymentColors[$idx % count($paymentColors)];
                                        @endphp
                                        <tr class="hover:bg-cream/20 transition-colors">
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $dotColor }};"></span>
                                                    <a href="{{ route('accounts.show', $entry->account) }}" class="font-bold text-charcoal hover:text-forest hover:underline">
                                                        {{ $entry->account?->name ?? 'Rekening' }}
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 uppercase text-muted text-[11px]">
                                                {{ $entry->account?->type }}
                                            </td>
                                            <td class="py-3 px-3 text-right font-mono font-bold text-muted">
                                                {{ $pct }}%
                                            </td>
                                            <td class="py-3 px-3 text-right font-mono font-bold text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($entryAmt, 0, ',', '.') }}">
                                                    Rp {{ number_format($entryAmt, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-line bg-cream/30 font-bold text-charcoal">
                                        <td colspan="2" class="py-2.5 px-3">Total Pembayaran</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-purple-700 dark:text-purple-300">100%</td>
                                        <td class="py-2.5 px-3 text-right font-mono">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalTxAmount, 0, ',', '.') }}">
                                                Rp {{ number_format($totalTxAmount, 0, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </x-panel>
            @elseif (! $transaction->isVoid() && in_array($transaction->type, ['expense', 'income'], true))
                <div class="p-4 sm:p-5 rounded-2xl border border-dashed border-purple-500/30 bg-purple-500/5 dark:bg-purple-950/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-700 dark:text-purple-300 flex items-center justify-center text-sm font-bold shrink-0">
                            💳
                        </span>
                        <div>
                            <h3 class="text-xs font-bold text-charcoal dark:text-purple-200">Bayar dengan Beberapa Rekening (Split Payment)</h3>
                            <p class="text-xs text-muted mt-0.5 max-w-xl">
                                Bagi beban pembayaran transaksi ini ke gabungan beberapa rekening (misal: Bank BCA + Kas Tunai atau E-Wallet).
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="openSplitPaymentModal()" class="btn-arus-secondary text-xs shrink-0 whitespace-nowrap cursor-pointer text-purple-700 dark:text-purple-300 border-purple-500/30 hover:bg-purple-500/10">
                        💳 Split Rekening
                    </button>
                </div>
            @endif

            <!-- Entri Pembukuan -->
            <x-panel eyebrow="ENTRI PEMBUKUAN (JURNAL MUTASI)">
                <div class="md:hidden divide-y divide-line">
                    @foreach ($transaction->entries as $entry)
                        @php
                            $isPositive = $entry->amount_signed > 0;
                        @endphp
                        <div class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('accounts.show', $entry->account) }}" class="font-bold text-charcoal hover:text-forest hover:underline block truncate">
                                    {{ $entry->account->name }}
                                </a>
                                <div class="mt-1 flex items-center flex-wrap gap-1.5 text-[11px]">
                                    <span class="uppercase text-muted">{{ $entry->account->type }}</span>
                                    <span class="text-line">&bull;</span>
                                    @if ($isPositive)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Kas Masuk (Debet)</span>
                                    @else
                                        <span class="text-red dark:text-rose-400 font-semibold">Kas Keluar (Kredit)</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right font-mono font-bold shrink-0 {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-400' }}">
                                <span class="privacy-balance" data-raw-value="{{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}">
                                    {{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                <th class="py-2.5 px-4">Rekening</th>
                                <th class="py-2.5 px-4">Tipe Rekening</th>
                                <th class="py-2.5 px-4">Arah Aliran</th>
                                <th class="py-2.5 px-4 text-right">Mutasi Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transaction->entries as $entry)
                                @php
                                    $isPositive = $entry->amount_signed > 0;
                                @endphp
                                <tr class="hover:bg-cream/20 transition-colors">
                                    <td class="py-3 px-4 font-bold text-charcoal">
                                        <a href="{{ route('accounts.show', $entry->account) }}" class="hover:text-forest hover:underline">
                                            {{ $entry->account->name }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 uppercase text-muted text-[11px]">
                                        {{ $entry->account->type }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($isPositive)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Kas Masuk (Debet)</span>
                                        @else
                                            <span class="text-red dark:text-rose-400 font-semibold">Kas Keluar (Kredit)</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-400' }}">
                                        <span class="privacy-balance" data-raw-value="{{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}">
                                            {{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        </div>

        <!-- Audit Trail / Histori Revisi -->
        <div class="space-y-6">
            <x-panel eyebrow="JEJAK AUDIT & REVISI">
                @if ($transaction->revisions->isEmpty())
                    <p class="text-xs text-muted leading-relaxed m-0">
                        Belum ada riwayat koreksi. Transaksi ini masih sesuai data aslinya sejak pertama kali dicatat.
                    </p>
                @else
                    <div class="space-y-4">
                        @foreach ($transaction->revisions->sortByDesc('created_at') as $revision)
                            <div class="p-3.5 rounded-lg border border-line bg-sidebar/40 text-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-forest">{{ $revision->actor?->name ?? 'Pengguna' }}</span>
                                    <span class="text-[11px] text-muted">{{ $revision->created_at->format('d M Y H:i') }}</span>
                                </div>
                                @if ($revision->reason)
                                    <div class="text-charcoal mb-2 font-medium">
                                        Alasan: "{{ $revision->reason }}"
                                    </div>
                                @endif
                                
                                <div class="mt-2 pt-2 border-t border-line text-[11px] text-muted space-y-1">
                                    @if (isset($revision->before_data['amount']) && isset($revision->after_data['amount']))
                                        @if ($revision->before_data['amount'] != $revision->after_data['amount'])
                                            <div>
                                                Nominal: <span class="privacy-balance" data-raw-value="Rp {{ number_format($revision->before_data['amount'], 0, ',', '.') }}">Rp {{ number_format($revision->before_data['amount'], 0, ',', '.') }}</span> &rarr; <span class="privacy-balance" data-raw-value="Rp {{ number_format($revision->after_data['amount'], 0, ',', '.') }}">Rp {{ number_format($revision->after_data['amount'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    @endif
                                    @if (isset($revision->before_data['description']) && isset($revision->after_data['description']))
                                        @if ($revision->before_data['description'] != $revision->after_data['description'])
                                            <div class="truncate">
                                                Keterangan: "{{ $revision->before_data['description'] }}" &rarr; "{{ $revision->after_data['description'] }}"
                                            </div>
                                        @endif
                                    @endif
                                    @if (isset($revision->after_data['status']) && $revision->after_data['status'] === 'void')
                                        <div class="text-red font-bold">
                                            Status diubah menjadi VOID (dibatalkan)
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-panel>
        </div>
    </div>

    <!-- Modal Konfirmasi Void Transaksi -->
    @if (! $transaction->isVoid())
        <div id="void-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-xl my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
                <h3 class="font-serif text-xl text-forest font-normal mb-2">Batalkan Transaksi?</h3>
                <p class="text-xs text-muted leading-relaxed mb-4">
                    Tindakan pembatalan (void) akan membatalkan pengaruh transaksi ini dari saldo rekening terkait. Catatan tidak dihapus dari basis data untuk mematuhi integritas audit pembukuan.
                </p>

                <form method="POST" action="{{ route('transactions.void', $transaction) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="void_reason" class="field-label text-xs">Alasan Pembatalan (Opsional)</label>
                        <input 
                            type="text" 
                            name="reason" 
                            id="void_reason" 
                            placeholder="Contoh: Salah input tanggal / transaksi duplikat" 
                            class="field-input text-xs" 
                            maxlength="255"
                        />
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('void-modal').classList.add('hidden')" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px]">
                            Batal
                        </button>
                        <button type="submit" class="btn-arus-danger-solid w-full sm:w-auto min-h-[44px] sm:min-h-[38px]">
                            Ya, Batalkan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Pemisahan Transaksi (Split Modal) -->
    @if (! $transaction->isVoid() && in_array($transaction->type, ['expense', 'income'], true))
        <div id="split-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-2xl w-full p-5 sm:p-6 shadow-xl my-4 sm:my-0 max-h-[calc(100dvh-2rem)] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <div>
                        <div class="text-[11px] font-bold text-muted uppercase tracking-wider">ARUS / PEMISAHAN TRANSAKSI</div>
                        <h3 class="font-serif text-xl sm:text-2xl text-forest font-normal">
                            {{ $transaction->isSplit() ? 'Kelola Pemisahan Transaksi' : 'Pisahkan Transaksi' }}
                        </h3>
                    </div>
                    <button type="button" onclick="closeSplitModal()" class="w-8 h-8 rounded-lg border border-line hover:bg-cream flex items-center justify-center text-muted hover:text-charcoal cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="p-3.5 rounded-xl border border-line bg-surface/40 flex items-center justify-between gap-3 text-xs">
                    <div>
                        <span class="text-muted block text-[11px]">Total Transaksi Struk</span>
                        <span class="font-bold text-base text-charcoal font-mono">Rp {{ number_format($transaction->totalAmount(), 0, ',', '.') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-muted block text-[11px]">Jenis Transaksi</span>
                        <span class="font-bold uppercase tracking-wider text-[11px] {{ $transaction->isIncome() ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $transaction->isIncome() ? 'Pemasukan' : 'Pengeluaran' }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('transactions.split', $transaction) }}" id="splitTransactionForm" class="space-y-4" onsubmit="return validateSplitSubmit(event)">
                    @csrf

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-charcoal">Baris Alokasi Pemisahan</span>
                            <span class="text-[11px] text-muted">Minimal 2 alokasi</span>
                        </div>

                        <!-- Container Baris Dinamis -->
                        <div id="split-rows-container" class="space-y-3">
                            <!-- Diisi via JavaScript -->
                        </div>

                        <div class="pt-1 flex items-center justify-between gap-2">
                            <button type="button" onclick="addSplitRow()" class="btn-arus-secondary text-xs min-h-[38px] px-3 font-semibold cursor-pointer">
                                + Tambah Baris Kategori
                            </button>
                            <button type="button" onclick="fillSplitRemainder()" id="btn-fill-remainder" class="text-xs text-forest hover:underline font-semibold cursor-pointer hidden">
                                Isi Sisa ke Baris Terakhir
                            </button>
                        </div>
                    </div>

                    <!-- Status Kalkulasi & Selisih -->
                    <div class="p-4 rounded-xl border border-line bg-cream/40 space-y-2 text-xs">
                        <div class="flex justify-between items-center text-muted">
                            <span>Total Nominal Transaksi:</span>
                            <span class="font-mono font-bold text-charcoal">Rp {{ number_format($transaction->totalAmount(), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-muted">
                            <span>Total Teralokasi Saat Ini:</span>
                            <span id="split-allocated-sum" class="font-mono font-bold text-charcoal">Rp 0</span>
                        </div>
                        <div class="pt-2 border-t border-line flex justify-between items-center font-bold">
                            <span>Status Sisa:</span>
                            <span id="split-balance-status" class="font-mono px-2 py-0.5 rounded text-[11px]">
                                Menghitung...
                            </span>
                        </div>
                    </div>

                    <div id="split-error-message" class="hidden p-3 rounded-xl border border-red/30 bg-red/10 text-xs text-red font-medium"></div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="closeSplitModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" id="split-submit-btn" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Simpan Pemisahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Batalkan Pemisahan (Unsplit Modal) -->
        <div id="unsplit-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-xl my-4 sm:my-0 space-y-4">
                <h3 class="font-serif text-xl text-forest font-normal">Batalkan Pemisahan Transaksi?</h3>
                <p class="text-xs text-muted leading-relaxed">
                    Seluruh baris pemisahan kategori dan alokasi member akan dihapus. Transaksi akan dikembalikan menjadi pencatatan kategori tunggal.
                </p>

                <form method="POST" action="{{ route('transactions.unsplit', $transaction) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="fallback_category_id" class="block text-xs font-bold text-charcoal mb-1.5">
                            Pilih Kategori Utama Transaksi
                        </label>
                        <select name="fallback_category_id" id="fallback_category_id" class="field-select w-full text-xs">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $transaction->category_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('unsplit-modal').classList.add('hidden')" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="btn-arus-danger-solid w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Ya, Batalkan Pemisahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Split Payment (Multi-Rekening) -->
        <div id="split-payment-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-2xl w-full p-5 sm:p-6 shadow-xl my-4 sm:my-0 max-h-[calc(100dvh-2rem)] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <div>
                        <div class="text-[11px] font-bold text-muted uppercase tracking-wider">ARUS / SUMBER REKENING PEMBAYARAN</div>
                        <h3 class="font-serif text-xl sm:text-2xl text-forest font-normal">
                            {{ $transaction->isSplitPayment() ? 'Kelola Split Rekening Pembayaran' : 'Split Rekening Pembayaran' }}
                        </h3>
                    </div>
                    <button type="button" onclick="closeSplitPaymentModal()" class="w-8 h-8 rounded-lg border border-line hover:bg-cream flex items-center justify-center text-muted hover:text-charcoal cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="p-3.5 rounded-xl border border-purple-500/20 bg-purple-500/5 dark:bg-purple-950/20 flex items-center justify-between gap-3 text-xs">
                    <div>
                        <span class="text-muted block text-[11px]">Total Transaksi</span>
                        <span class="font-bold text-base text-charcoal font-mono">Rp {{ number_format($transaction->totalAmount(), 0, ',', '.') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-muted block text-[11px]">Status Sumber</span>
                        <span class="font-bold text-purple-700 dark:text-purple-300">
                            {{ $transaction->isSplitPayment() ? $transaction->sourceEntries()->count() . ' Rekening' : '1 Rekening Tunggal' }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('transactions.split-payment', $transaction) }}" id="splitPaymentForm" class="space-y-4" onsubmit="return validateSplitPaymentSubmit(event)">
                    @csrf

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-charcoal">Daftar Rekening Pembayar</span>
                            <span class="text-[11px] text-muted">Minimal 2 rekening</span>
                        </div>

                        <!-- Container Baris Dinamis Split Payment -->
                        <div id="split-payment-rows-container" class="space-y-3">
                            <!-- Diisi via JavaScript -->
                        </div>

                        <div class="pt-1 flex items-center justify-between gap-2">
                            <button type="button" onclick="addSplitPaymentRow()" class="btn-arus-secondary text-xs min-h-[38px] px-3 font-semibold cursor-pointer">
                                + Tambah Rekening Pembayaran
                            </button>
                            <button type="button" onclick="fillSplitPaymentRemainder()" id="btn-fill-payment-remainder" class="text-xs text-purple-700 dark:text-purple-300 hover:underline font-semibold cursor-pointer hidden">
                                Isi Sisa ke Baris Terakhir
                            </button>
                        </div>
                    </div>

                    <!-- Status Kalkulasi & Selisih -->
                    <div class="p-4 rounded-xl border border-line bg-cream/40 space-y-2 text-xs">
                        <div class="flex justify-between items-center text-muted">
                            <span>Total Nominal Transaksi:</span>
                            <span class="font-mono font-bold text-charcoal">Rp {{ number_format($transaction->totalAmount(), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-muted">
                            <span>Total Pembayaran Dialokasikan:</span>
                            <span id="split-payment-allocated-sum" class="font-mono font-bold text-charcoal">Rp 0</span>
                        </div>
                        <div class="pt-2 border-t border-line flex justify-between items-center font-bold">
                            <span>Status Sisa:</span>
                            <span id="split-payment-balance-status" class="font-mono px-2 py-0.5 rounded text-[11px]">
                                Menghitung...
                            </span>
                        </div>
                    </div>

                    <div id="split-payment-error-message" class="hidden p-3 rounded-xl border border-red/30 bg-red/10 text-xs text-red font-medium"></div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="closeSplitPaymentModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" id="split-payment-submit-btn" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Simpan Split Rekening
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Batalkan Split Payment (Kembali ke 1 Rekening) -->
        <div id="unsplit-payment-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-xl my-4 sm:my-0 space-y-4">
                <h3 class="font-serif text-xl text-forest font-normal">Batalkan Split Rekening Pembayaran?</h3>
                <p class="text-xs text-muted leading-relaxed">
                    Seluruh beban nominal transaksi (Rp {{ number_format($transaction->totalAmount(), 0, ',', '.') }}) akan dikembalikan menjadi tanggungan satu rekening tunggal yang Anda pilih.
                </p>

                <form method="POST" action="{{ route('transactions.unsplit-payment', $transaction) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="target_account_id" class="block text-xs font-bold text-charcoal mb-1.5">
                            Pilih Rekening Pembayar Tunggal
                        </label>
                        <select name="target_account_id" id="target_account_id" class="field-select w-full text-xs" required>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($transaction->sourceAccount()?->id ?? null) == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }} (Saldo: Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="closeUnsplitPaymentModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="btn-arus-danger-solid w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                            Ya, Kembalikan ke 1 Rekening
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            @php
                $existingSplitsData = $transaction->splits->map(fn($s) => [
                    'category_id' => $s->category_id,
                    'member_id' => $s->member_id,
                    'amount' => (float) $s->amount,
                    'notes' => $s->notes ?? '',
                ])->values();
                $categoriesData = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
                $membersData = $members->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'color' => $m->color])->values();
                $existingPaymentEntries = $transaction->isExpense()
                    ? $transaction->sourceEntries()->map(fn($e) => ['account_id' => $e->account_id, 'amount' => abs((float)$e->amount_signed)])->values()
                    : $transaction->destinationEntries()->map(fn($e) => ['account_id' => $e->account_id, 'amount' => (float)$e->amount_signed])->values();
                $accountsData = $accounts->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'type' => $a->type, 'balance' => $a->currentBalance()])->values();
            @endphp
            const splitCategories = @json($categoriesData);
            const splitMembers = @json($membersData);
            const targetTotalAmount = {{ (float) $transaction->totalAmount() }};
            const existingSplits = @json($existingSplitsData);
            const availableAccounts = @json($accountsData);
            const existingPaymentSplits = @json($existingPaymentEntries);

            let splitRowIndex = 0;
            let splitPaymentRowIndex = 0;

            function formatRupiah(val) {
                return 'Rp ' + Math.round(val).toLocaleString('id-ID');
            }

            function openSplitModal() {
                const container = document.getElementById('split-rows-container');
                container.innerHTML = '';
                splitRowIndex = 0;

                if (existingSplits.length > 0) {
                    existingSplits.forEach(s => {
                        addSplitRow(s.category_id, s.member_id, s.amount, s.notes);
                    });
                } else {
                    const defaultCatId = {{ $transaction->category_id ?: 'null' }};
                    const half = Math.floor(targetTotalAmount / 2);
                    const remainder = targetTotalAmount - half;

                    addSplitRow(defaultCatId, {{ $transaction->member_id ?: 'null' }}, half, '');
                    addSplitRow(null, null, remainder, '');
                }

                recalculateSplit();
                document.getElementById('split-modal').classList.remove('hidden');
            }

            function closeSplitModal() {
                document.getElementById('split-modal').classList.add('hidden');
            }

            function addSplitRow(categoryId = null, memberId = null, amount = '', notes = '') {
                const container = document.getElementById('split-rows-container');
                const idx = splitRowIndex++;

                let catOptions = '<option value="">-- Pilih Kategori --</option>';
                splitCategories.forEach(c => {
                    const sel = (categoryId && Number(categoryId) === Number(c.id)) ? 'selected' : '';
                    catOptions += `<option value="${c.id}" ${sel}>${c.name}</option>`;
                });

                let memberOptions = '<option value="">Tanpa Member (Umum)</option>';
                splitMembers.forEach(m => {
                    const sel = (memberId && Number(memberId) === Number(m.id)) ? 'selected' : '';
                    memberOptions += `<option value="${m.id}" ${sel}>${m.name}</option>`;
                });

                const div = document.createElement('div');
                div.className = 'p-3 rounded-xl border border-line bg-surface/30 space-y-2.5 split-row';
                div.innerHTML = `
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-bold text-muted uppercase">Baris Alokasi</span>
                        <button type="button" onclick="removeSplitRow(this)" class="text-muted hover:text-red text-xs px-1.5 py-0.5 rounded cursor-pointer btn-remove-row" title="Hapus Baris">
                            ✕ Hapus
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Kategori *</label>
                            <select name="splits[${idx}][category_id]" class="field-select w-full text-xs split-category-select" required>
                                ${catOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Nominal (Rp) *</label>
                            <input 
                                type="number" 
                                step="any"
                                min="0.01"
                                name="splits[${idx}][amount]" 
                                value="${amount}" 
                                placeholder="0" 
                                class="field-input w-full text-xs font-mono font-bold split-amount-input" 
                                required
                                oninput="recalculateSplit()"
                            >
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Pihak / Member (Opsional)</label>
                            <select name="splits[${idx}][member_id]" class="field-select w-full text-xs">
                                ${memberOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Catatan Baris (Opsional)</label>
                            <input 
                                type="text" 
                                name="splits[${idx}][notes]" 
                                value="${notes ? escapeHtml(notes) : ''}" 
                                placeholder="Contoh: Belanja sabun, snack, dll." 
                                class="field-input w-full text-xs" 
                                maxlength="255"
                            >
                        </div>
                    </div>
                `;

                container.appendChild(div);
                recalculateSplit();
            }

            function escapeHtml(str) {
                return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function removeSplitRow(btn) {
                const rows = document.querySelectorAll('.split-row');
                if (rows.length <= 2) {
                    alert('Pemisahan transaksi membutuhkan minimal 2 baris alokasi.');
                    return;
                }
                btn.closest('.split-row').remove();
                recalculateSplit();
            }

            function recalculateSplit() {
                const amountInputs = document.querySelectorAll('.split-amount-input');
                let sum = 0;
                amountInputs.forEach(input => {
                    const val = parseFloat(input.value) || 0;
                    sum += val;
                });

                sum = Math.round(sum * 100) / 100;
                const diff = Math.round((targetTotalAmount - sum) * 100) / 100;

                const allocatedEl = document.getElementById('split-allocated-sum');
                const statusEl = document.getElementById('split-balance-status');
                const submitBtn = document.getElementById('split-submit-btn');
                const fillRemainderBtn = document.getElementById('btn-fill-remainder');

                allocatedEl.textContent = formatRupiah(sum);

                if (Math.abs(diff) < 0.01) {
                    statusEl.textContent = '✓ Pas (100% Cocok)';
                    statusEl.className = 'font-mono px-2 py-0.5 rounded text-[11px] bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    fillRemainderBtn.classList.add('hidden');
                } else if (diff > 0) {
                    statusEl.textContent = `Kurang Rp ${Math.round(diff).toLocaleString('id-ID')}`;
                    statusEl.className = 'font-mono px-2 py-0.5 rounded text-[11px] bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    fillRemainderBtn.classList.remove('hidden');
                    fillRemainderBtn.textContent = `Isi sisa Rp ${Math.round(diff).toLocaleString('id-ID')} ke baris terakhir`;
                } else {
                    statusEl.textContent = `Kelebihan Rp ${Math.round(Math.abs(diff)).toLocaleString('id-ID')}`;
                    statusEl.className = 'font-mono px-2 py-0.5 rounded text-[11px] bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    fillRemainderBtn.classList.add('hidden');
                }
            }

            function fillSplitRemainder() {
                const amountInputs = Array.from(document.querySelectorAll('.split-amount-input'));
                if (amountInputs.length === 0) return;

                let otherSum = 0;
                for (let i = 0; i < amountInputs.length - 1; i++) {
                    otherSum += parseFloat(amountInputs[i].value) || 0;
                }

                const remainder = Math.max(0, targetTotalAmount - otherSum);
                amountInputs[amountInputs.length - 1].value = remainder;
                recalculateSplit();
            }

            function validateSplitSubmit(e) {
                const rows = document.querySelectorAll('.split-row');
                const errorBox = document.getElementById('split-error-message');
                errorBox.classList.add('hidden');
                errorBox.textContent = '';

                if (rows.length < 2) {
                    errorBox.textContent = 'Pemisahan transaksi membutuhkan minimal 2 baris alokasi.';
                    errorBox.classList.remove('hidden');
                    return false;
                }

                let sum = 0;
                let hasEmptyCat = false;

                rows.forEach(r => {
                    const cat = r.querySelector('.split-category-select').value;
                    const amt = parseFloat(r.querySelector('.split-amount-input').value) || 0;
                    if (!cat) hasEmptyCat = true;
                    sum += amt;
                });

                if (hasEmptyCat) {
                    errorBox.textContent = 'Seluruh baris alokasi wajib memilih kategori.';
                    errorBox.classList.remove('hidden');
                    return false;
                }

                const diff = Math.round((targetTotalAmount - sum) * 100) / 100;
                if (Math.abs(diff) >= 0.01) {
                    errorBox.textContent = `Total alokasi (${formatRupiah(sum)}) harus tepat sama dengan total transaksi (${formatRupiah(targetTotalAmount)}). Selisih: Rp ${Math.round(Math.abs(diff)).toLocaleString('id-ID')}`;
                    errorBox.classList.remove('hidden');
                    return false;
                }

                return true;
            }

            // Split Payment Modal JS Handlers
            function openSplitPaymentModal() {
                const container = document.getElementById('split-payment-rows-container');
                container.innerHTML = '';
                splitPaymentRowIndex = 0;

                if (existingPaymentSplits.length >= 2) {
                    existingPaymentSplits.forEach(p => {
                        addSplitPaymentRow(p.account_id, p.amount);
                    });
                } else {
                    const currentAccId = existingPaymentSplits.length > 0 ? existingPaymentSplits[0].account_id : (availableAccounts[0] ? availableAccounts[0].id : null);
                    const secondAcc = availableAccounts.find(a => Number(a.id) !== Number(currentAccId))?.id || (availableAccounts[1] ? availableAccounts[1].id : null);
                    const half = Math.floor(targetTotalAmount / 2);
                    const remainder = targetTotalAmount - half;

                    addSplitPaymentRow(currentAccId, half);
                    addSplitPaymentRow(secondAcc, remainder);
                }

                recalculateSplitPaymentModal();
                document.getElementById('split-payment-modal').classList.remove('hidden');
            }

            function closeSplitPaymentModal() {
                document.getElementById('split-payment-modal').classList.add('hidden');
            }

            function openUnsplitPaymentModal() {
                document.getElementById('unsplit-payment-modal').classList.remove('hidden');
            }

            function closeUnsplitPaymentModal() {
                document.getElementById('unsplit-payment-modal').classList.add('hidden');
            }

            function addSplitPaymentRow(accountId = null, amount = '') {
                const container = document.getElementById('split-payment-rows-container');
                const idx = splitPaymentRowIndex++;

                let accountOptions = '<option value="">-- Pilih Rekening --</option>';
                availableAccounts.forEach(a => {
                    const sel = (accountId && Number(accountId) === Number(a.id)) ? 'selected' : '';
                    const bal = 'Rp ' + Math.round(a.balance).toLocaleString('id-ID');
                    accountOptions += `<option value="${a.id}" ${sel}>${a.name} (${bal})</option>`;
                });

                const div = document.createElement('div');
                div.className = 'p-3 rounded-xl border border-line bg-surface/30 space-y-2.5 split-payment-row';
                div.innerHTML = `
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-bold text-muted uppercase">Rekening Pembayaran #${idx + 1}</span>
                        <button type="button" onclick="removeSplitPaymentRow(this)" class="text-muted hover:text-red text-xs px-1.5 py-0.5 rounded cursor-pointer btn-remove-row" title="Hapus Baris">
                            ✕ Hapus
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Rekening Sumber *</label>
                            <select name="payments[${idx}][account_id]" class="field-select w-full text-xs split-payment-account-select" required onchange="recalculateSplitPaymentModal()">
                                ${accountOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Nominal Terpotong (Rp) *</label>
                            <div class="flex items-center gap-1.5">
                                <input 
                                    type="number" 
                                    step="any"
                                    min="0.01"
                                    name="payments[${idx}][amount]" 
                                    value="${amount}" 
                                    placeholder="0" 
                                    class="field-input flex-1 text-xs font-mono font-bold split-payment-amount-input" 
                                    required
                                    oninput="recalculateSplitPaymentModal()"
                                >
                                <button type="button" onclick="fillSplitPaymentSingleRemainder(this)" class="btn-arus-secondary text-[11px] px-2.5 py-2 whitespace-nowrap cursor-pointer shrink-0" title="Isi sisa alokasi ke rekening ini">
                                    Isi Sisa
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(div);
                recalculateSplitPaymentModal();
            }

            function removeSplitPaymentRow(btn) {
                const rows = document.querySelectorAll('.split-payment-row');
                if (rows.length <= 2) {
                    alert('Split payment membutuhkan minimal 2 rekening pembayaran.');
                    return;
                }
                btn.closest('.split-payment-row').remove();
                recalculateSplitPaymentModal();
            }

            function fillSplitPaymentSingleRemainder(btn) {
                const row = btn.closest('.split-payment-row');
                const targetInput = row.querySelector('.split-payment-amount-input');
                let otherSum = 0;
                document.querySelectorAll('.split-payment-amount-input').forEach(inp => {
                    if (inp !== targetInput) {
                        otherSum += parseFloat(inp.value) || 0;
                    }
                });
                const remainder = Math.max(0, Math.round((targetTotalAmount - otherSum) * 100) / 100);
                targetInput.value = remainder > 0 ? remainder : '';
                recalculateSplitPaymentModal();
            }

            function fillSplitPaymentRemainder() {
                const amountInputs = Array.from(document.querySelectorAll('.split-payment-amount-input'));
                if (amountInputs.length === 0) return;

                let otherSum = 0;
                for (let i = 0; i < amountInputs.length - 1; i++) {
                    otherSum += parseFloat(amountInputs[i].value) || 0;
                }

                const remainder = Math.max(0, targetTotalAmount - otherSum);
                amountInputs[amountInputs.length - 1].value = remainder;
                recalculateSplitPaymentModal();
            }

            function recalculateSplitPaymentModal() {
                const amountInputs = document.querySelectorAll('.split-payment-amount-input');
                let sum = 0;
                amountInputs.forEach(input => {
                    const val = parseFloat(input.value) || 0;
                    sum += val;
                });

                sum = Math.round(sum * 100) / 100;
                const diff = Math.round((targetTotalAmount - sum) * 100) / 100;

                const allocatedEl = document.getElementById('split-payment-allocated-sum');
                const statusEl = document.getElementById('split-payment-balance-status');
                const submitBtn = document.getElementById('split-payment-submit-btn');
                const fillRemainderBtn = document.getElementById('btn-fill-payment-remainder');

                allocatedEl.textContent = formatRupiah(sum);

                if (Math.abs(diff) < 0.01) {
                    statusEl.textContent = '✓ Pas (100% Cocok)';
                    statusEl.className = 'font-mono px-2 py-0.5 rounded text-[11px] bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    fillRemainderBtn.classList.add('hidden');
                } else if (diff > 0) {
                    statusEl.textContent = `Kurang Rp ${Math.round(diff).toLocaleString('id-ID')}`;
                    statusEl.className = 'font-mono px-2 py-0.5 rounded text-[11px] bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    fillRemainderBtn.classList.remove('hidden');
                    fillRemainderBtn.textContent = `Isi sisa Rp ${Math.round(diff).toLocaleString('id-ID')} ke baris terakhir`;
                } else {
                    statusEl.textContent = `Kelebihan Rp ${Math.round(Math.abs(diff)).toLocaleString('id-ID')}`;
                    statusEl.className = 'font-mono px-2 py-0.5 rounded text-[11px] bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    fillRemainderBtn.classList.add('hidden');
                }
            }

            function validateSplitPaymentSubmit(e) {
                const rows = document.querySelectorAll('.split-payment-row');
                const errorBox = document.getElementById('split-payment-error-message');
                errorBox.classList.add('hidden');
                errorBox.textContent = '';

                if (rows.length < 2) {
                    errorBox.textContent = 'Split payment membutuhkan minimal 2 rekening pembayaran.';
                    errorBox.classList.remove('hidden');
                    return false;
                }

                let sum = 0;
                let hasEmptyAcc = false;
                const seenAccounts = new Set();
                let hasDuplicateAcc = false;

                rows.forEach(r => {
                    const acc = r.querySelector('.split-payment-account-select').value;
                    const amt = parseFloat(r.querySelector('.split-payment-amount-input').value) || 0;
                    if (!acc) hasEmptyAcc = true;
                    if (seenAccounts.has(acc)) hasDuplicateAcc = true;
                    seenAccounts.add(acc);
                    sum += amt;
                });

                if (hasEmptyAcc) {
                    errorBox.textContent = 'Seluruh baris wajib memilih rekening pembayaran.';
                    errorBox.classList.remove('hidden');
                    return false;
                }

                if (hasDuplicateAcc) {
                    errorBox.textContent = 'Rekening pembayaran tidak boleh dipilih lebih dari sekali. Gabungkan ke satu baris.';
                    errorBox.classList.remove('hidden');
                    return false;
                }

                const diff = Math.round((targetTotalAmount - sum) * 100) / 100;
                if (Math.abs(diff) >= 0.01) {
                    errorBox.textContent = `Total alokasi rekening (${formatRupiah(sum)}) harus tepat sama dengan total nominal transaksi (${formatRupiah(targetTotalAmount)}). Selisih: Rp ${Math.round(Math.abs(diff)).toLocaleString('id-ID')}`;
                    errorBox.classList.remove('hidden');
                    return false;
                }

                return true;
            }
        </script>
    @endif
</x-layouts.app>
