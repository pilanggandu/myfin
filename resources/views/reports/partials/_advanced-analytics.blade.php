@php
    $aa = $advancedAnalytics;
    $comp = $aa['periodComparison'];
    $curr = $comp['current'];
    $prev = $comp['previous'];
    $bs = $aa['balanceSheet'];
    $signals = $aa['healthSignals'];
    $trend = $aa['trendData'];
    $pattern = $aa['dailyPattern'];
    $catAnalysis = $aa['categoryAnalysis'];
    $budgetAnalysis = $aa['budgetAnalysis'];
    $structure = $aa['spendingStructure'];
    $forecast = $aa['commitmentsForecast'];
    $members = $aa['memberBreakdown'];
    $topExpenses = $aa['topExpenses'];
    $insights = $aa['insights'];

    $toneBadge = function (string $tone): string {
        return match ($tone) {
            'positive' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
            'warning' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30',
            'danger' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/30',
            'info' => 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/30',
            default => 'bg-cream dark:bg-line/40 text-muted border-line',
        };
    };

    $toneSymbol = function (string $tone): string {
        return match ($tone) {
            'positive' => '✓',
            'warning' => '!',
            'danger' => '▲',
            'info' => 'i',
            default => '•',
        };
    };

    $hasAnyCashflow = ($curr['income'] > 0 || $curr['expense'] > 0);
@endphp

<div data-privacy-scope="reports-summary" class="privacy-scope space-y-6">
    <!-- Toolbar Kontrol Analisis Lanjutan -->
    <div class="panel-card p-4 sm:p-5">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-3">
            <input type="hidden" name="tab" value="advanced">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap items-end gap-3 flex-1">
                <div class="min-w-0">
                    <label for="advPeriodType" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Mode Periode</label>
                    <select id="advPeriodType" name="period_type" class="field-select h-11 sm:h-9 py-1 text-xs w-full lg:w-36" onchange="toggleAdvancedPeriodInputs(this.value)">
                        <option value="month" {{ $periodType === 'month' ? 'selected' : '' }}>Bulanan</option>
                        <option value="custom" {{ $periodType === 'custom' ? 'selected' : '' }}>Rentang Kustom</option>
                    </select>
                </div>

                <div id="advMonthInputs" class="{{ $periodType === 'custom' ? 'hidden' : 'contents' }}">
                    <div class="min-w-0">
                        <label for="advMonth" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Bulan</label>
                        <select id="advMonth" name="month" class="field-select h-11 sm:h-9 py-1 text-xs w-full lg:w-36">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="advYear" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Tahun</label>
                        <select id="advYear" name="year" class="field-select h-11 sm:h-9 py-1 text-xs w-full lg:w-24">
                            @for ($y = now()->year - 3; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div id="advCustomInputs" class="{{ $periodType === 'custom' ? 'contents' : 'hidden' }}">
                    <div class="min-w-0">
                        <label for="advStartDate" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Dari Tanggal</label>
                        <input id="advStartDate" type="date" name="start_date" value="{{ $startDate }}" class="field-input h-11 sm:h-9 py-1 text-xs w-full lg:w-36">
                    </div>
                    <div class="min-w-0">
                        <label for="advEndDate" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Sampai Tanggal</label>
                        <input id="advEndDate" type="date" name="end_date" value="{{ $endDate }}" class="field-input h-11 sm:h-9 py-1 text-xs w-full lg:w-36">
                    </div>
                </div>

                <div class="min-w-0">
                    <label for="advAccount" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Cakupan Rekening</label>
                    <select id="advAccount" name="account_id" class="field-select h-11 sm:h-9 py-1 text-xs w-full lg:w-48">
                        <option value="">Semua Rekening Ruang</option>
                        @foreach ($allFilterAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ (string)$selectedAccountId === (string)$acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} ({{ strtoupper($acc->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-0">
                    <label for="advHorizon" class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Rentang Tren</label>
                    <select id="advHorizon" name="horizon" class="field-select h-11 sm:h-9 py-1 text-xs w-full lg:w-32">
                        <option value="6" {{ $horizonMonths === 6 ? 'selected' : '' }}>6 Bulan</option>
                        <option value="12" {{ $horizonMonths === 12 ? 'selected' : '' }}>12 Bulan</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[36px] px-4 text-xs font-bold cursor-pointer">
                    Terapkan Analisis
                </button>
            </div>
        </form>
    </div>

    <!-- Zona 1: 4 Rasio Kesehatan Keuangan Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Rasio Tabungan -->
        <div class="stat-folder-card">
            <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10.5px] font-bold tracking-[1.2px] uppercase text-muted">RASIO TABUNGAN</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[10px] font-bold {{ $toneBadge($signals['savings']['tone']) }}">
                            <span aria-hidden="true">{{ $toneSymbol($signals['savings']['tone']) }}</span>
                            <span>{{ $signals['savings']['label'] }}</span>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold font-mono tracking-tight mt-2.5 text-charcoal">
                        {{ $curr['savings_rate'] !== null ? $curr['savings_rate'].'%' : '-' }}
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-line text-xs text-muted flex items-center justify-between gap-2">
                    <span>Net periode ini</span>
                    <span class="font-mono font-bold {{ $curr['net'] >= 0 ? 'text-forest dark:text-blue-300' : 'text-red dark:text-rose-300' }}">
                        <span class="privacy-balance" data-raw-value="{{ $curr['net'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($curr['net']), 0, ',', '.') }}">
                            {{ $curr['net'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($curr['net']), 0, ',', '.') }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Laju Belanja Harian (Burn Rate) -->
        <div class="stat-folder-card">
            <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10.5px] font-bold tracking-[1.2px] uppercase text-muted">LAJU BELANJA HARIAN</span>
                        <span class="text-[10px] font-mono font-semibold text-muted">
                            {{ $aa['elapsedDays'] }}/{{ $aa['totalDaysInPeriod'] }} hari
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold font-mono tracking-tight mt-2.5 text-charcoal">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($aa['dailyBurnRate'], 0, ',', '.') }}">
                            Rp {{ number_format($aa['dailyBurnRate'], 0, ',', '.') }}
                        </span>
                        <span class="text-xs font-sans font-normal text-muted">/hari</span>
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-line text-xs text-muted flex items-center justify-between gap-2">
                    <span>{{ $aa['elapsedDays'] < $aa['totalDaysInPeriod'] && $aa['elapsedDays'] > 0 ? 'Proyeksi akhir periode' : 'Total pengeluaran' }}</span>
                    <span class="font-mono font-bold text-charcoal">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($aa['projectedPeriodExpense'], 0, ',', '.') }}">
                            Rp {{ number_format($aa['projectedPeriodExpense'], 0, ',', '.') }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Ketahanan Dana Likuid (Runway) -->
        <div class="stat-folder-card">
            <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10.5px] font-bold tracking-[1.2px] uppercase text-muted">KETAHANAN DANA LIKUID</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[10px] font-bold {{ $toneBadge($signals['runway']['tone']) }}">
                            <span aria-hidden="true">{{ $toneSymbol($signals['runway']['tone']) }}</span>
                            <span>{{ $signals['runway']['label'] }}</span>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold font-mono tracking-tight mt-2.5 text-charcoal">
                        {{ $aa['runwayMonths'] !== null ? number_format($aa['runwayMonths'], 1, ',', '.').' bln' : '-' }}
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-line text-xs text-muted flex items-center justify-between gap-2">
                    <span>Kas likuid / rata-rata belanja</span>
                    <span class="font-mono font-bold text-charcoal">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($bs['liquid_cash'], 0, ',', '.') }}">
                            Rp {{ number_format($bs['liquid_cash'], 0, ',', '.') }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Kekayaan Bersih (Net Worth) - Focal Dark Card -->
        <div class="stat-folder-card dark">
            <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10.5px] font-bold tracking-[1.2px] uppercase text-blue-100">KEKAYAAN BERSIH RUANG</span>
                        @if ($bs['credit_utilization'] !== null)
                            <span class="px-2 py-0.5 rounded bg-white/15 text-white text-[10px] font-mono font-bold">
                                Kredit {{ $bs['credit_utilization'] }}%
                            </span>
                        @endif
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold font-mono tracking-tight mt-2.5 text-white break-words">
                        <span class="privacy-balance" data-raw-value="{{ $bs['net_worth'] < 0 ? '-' : '' }}Rp {{ number_format(abs($bs['net_worth']), 0, ',', '.') }}">
                            {{ $bs['net_worth'] < 0 ? '-' : '' }}Rp {{ number_format(abs($bs['net_worth']), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-white/15 text-xs text-blue-100 flex items-center justify-between gap-2">
                    <span>Aset: <span class="font-mono font-semibold text-white privacy-balance" data-raw-value="Rp {{ number_format($bs['gross_assets'], 0, ',', '.') }}">Rp {{ number_format($bs['gross_assets'], 0, ',', '.') }}</span></span>
                    <span>Utang: <span class="font-mono font-semibold text-white privacy-balance" data-raw-value="Rp {{ number_format($bs['credit_liabilities'], 0, ',', '.') }}">Rp {{ number_format($bs['credit_liabilities'], 0, ',', '.') }}</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Zona 2: Asimetris (Perbandingan Periode + Struktur Pengeluaran & Temuan Data) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri (7 cols): Perbandingan Periode & Struktur Pengeluaran -->
        <div class="lg:col-span-7 panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-forest dark:text-blue-300">Perbandingan terhadap Periode Sebelumnya</h2>
                        <p class="text-[11px] text-muted mt-0.5">{{ $periodLabel }} dibandingkan {{ $comp['prev_label'] }}</p>
                    </div>
                    @if ($selectedAccountId)
                        <span class="px-2 py-0.5 rounded bg-cream dark:bg-line/50 border border-line text-[10.5px] font-semibold text-charcoal">
                            Terfilter 1 Rekening
                        </span>
                    @endif
                </div>

                <div class="p-5 space-y-4">
                    @php
                        $compRows = [
                            [
                                'label' => 'Pemasukan Bersih',
                                'curr' => $curr['income'],
                                'prev' => $prev['income'],
                                'delta' => $comp['income_delta'],
                                'pct' => $comp['income_delta_pct'],
                                'goodWhenUp' => true,
                            ],
                            [
                                'label' => 'Pengeluaran Bersih',
                                'curr' => $curr['expense'],
                                'prev' => $prev['expense'],
                                'delta' => $comp['expense_delta'],
                                'pct' => $comp['expense_delta_pct'],
                                'goodWhenUp' => false,
                            ],
                            [
                                'label' => 'Arus Kas Bersih (Net)',
                                'curr' => $curr['net'],
                                'prev' => $prev['net'],
                                'delta' => $comp['net_delta'],
                                'pct' => null,
                                'goodWhenUp' => true,
                            ],
                        ];
                    @endphp

                    <div class="divide-y divide-line border border-line rounded-xl overflow-hidden">
                        @foreach ($compRows as $row)
                            @php
                                $isPositiveDelta = $row['delta'] >= 0;
                                $isFavorable = $row['goodWhenUp'] ? $isPositiveDelta : ! $isPositiveDelta;
                                $deltaClass = $row['delta'] === 0
                                    ? 'text-muted bg-cream dark:bg-line/40 border-line'
                                    : ($isFavorable
                                        ? 'text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 border-emerald-500/30'
                                        : 'text-rose-700 dark:text-rose-300 bg-rose-500/10 border-rose-500/30');
                            @endphp
                            <div class="p-3.5 sm:px-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-panel">
                                <div>
                                    <div class="text-xs font-bold text-charcoal">{{ $row['label'] }}</div>
                                    <div class="text-[11px] text-muted mt-0.5">
                                        Periode lalu:
                                        <span class="font-mono privacy-balance" data-raw-value="{{ $row['prev'] < 0 ? '-' : '' }}Rp {{ number_format(abs($row['prev']), 0, ',', '.') }}">
                                            {{ $row['prev'] < 0 ? '-' : '' }}Rp {{ number_format(abs($row['prev']), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between sm:justify-end gap-3">
                                    <div class="text-left sm:text-right font-mono font-bold text-sm text-charcoal">
                                        <span class="privacy-balance" data-raw-value="{{ $row['curr'] < 0 ? '-' : '' }}Rp {{ number_format(abs($row['curr']), 0, ',', '.') }}">
                                            {{ $row['curr'] < 0 ? '-' : '' }}Rp {{ number_format(abs($row['curr']), 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-mono font-bold shrink-0 {{ $deltaClass }}">
                                        <span>{{ $row['delta'] > 0 ? '+' : ($row['delta'] < 0 ? '-' : '') }}</span>
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format(abs($row['delta']), 0, ',', '.') }}">Rp {{ number_format(abs($row['delta']), 0, ',', '.') }}</span>
                                        @if ($row['pct'] !== null)
                                            <span>({{ $row['pct'] > 0 ? '+' : '' }}{{ $row['pct'] }}%)</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Komposisi Pengeluaran: Komitmen Tetap vs Diskresioner -->
                    <div class="p-4 rounded-xl border border-line bg-cream/30 dark:bg-line/20">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-bold text-charcoal">Struktur Pengeluaran: Komitmen Terjadwal vs Fleksibel</span>
                            <span class="text-[11px] text-muted">
                                {{ $structure['committed_pct'] }}% rutin/cicilan • {{ $structure['discretionary_pct'] }}% fleksibel
                            </span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-cream dark:bg-line/60 overflow-hidden flex" aria-hidden="true">
                            <div class="h-full bg-forest transition-all" style="width: {{ min(100, $structure['committed_pct']) }}%"></div>
                            <div class="h-full bg-amber-500/80 transition-all" style="width: {{ min(100, $structure['discretionary_pct']) }}%"></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2.5 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 text-muted">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-forest shrink-0"></span>
                                    <span>Komitmen Rutin & Cicilan</span>
                                </span>
                                <span class="font-mono font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($structure['committed'], 0, ',', '.') }}">
                                    Rp {{ number_format($structure['committed'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 text-muted">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-amber-500/80 shrink-0"></span>
                                    <span>Belanja Fleksibel (Diskresioner)</span>
                                </span>
                                <span class="font-mono font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($structure['discretionary'], 0, ',', '.') }}">
                                    Rp {{ number_format($structure['discretionary'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3 bg-cream/20 border-t border-line text-[11px] text-muted flex flex-wrap items-center justify-between gap-2">
                <span>Rasio Pengeluaran terhadap Pemasukan:</span>
                <strong class="font-mono text-charcoal">{{ $curr['expense_ratio'] !== null ? $curr['expense_ratio'].'%' : 'Belum terukur' }}</strong>
            </div>
        </div>

        <!-- Kolom Kanan (5 cols): Temuan Utama Berbasis Data -->
        <div class="lg:col-span-5 panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                    <h2 class="text-sm font-bold text-forest dark:text-blue-300">Temuan Utama Periode Ini</h2>
                    <span class="text-[11px] font-mono text-muted">{{ count($insights) }} catatan</span>
                </div>

                @if (empty($insights))
                    <div class="p-8 text-center text-xs text-muted">
                        Belum ada cukup aktivitas transaksi untuk menyusun ringkasan temuan.
                    </div>
                @else
                    <div class="p-4 sm:p-5 space-y-2.5">
                        @foreach ($insights as $ins)
                            <div class="p-3 rounded-xl border {{ $toneBadge($ins['tone']) }} flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full border border-current flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5" aria-hidden="true">
                                    {{ $toneSymbol($ins['tone']) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-bold leading-snug">{{ $ins['title'] }}</div>
                                    <p class="text-[11px] opacity-90 mt-0.5 leading-relaxed">{{ $ins['detail'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="px-5 py-3 bg-cream/20 border-t border-line text-[11px] text-muted">
                Seluruh temuan dihitung langsung dari catatan transaksi posted, anggaran, dan kredit aktif Anda.
            </div>
        </div>
    </div>

    <!-- Zona 3: Tren Arus Kas & Rasio Tabungan Multi-Bulan -->
    <div class="panel-card p-0 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-forest dark:text-blue-300">Tren Arus Kas & Rasio Tabungan ({{ $horizonMonths }} Bulan)</h2>
                <p class="text-[11px] text-muted mt-0.5">Pemasukan, pengeluaran, net arus kas, dan persentase tabungan per bulan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[11px] text-muted">
                <span>Rata-rata Masuk: <strong class="font-mono text-emerald-600 dark:text-emerald-400 privacy-balance" data-raw-value="Rp {{ number_format($trend['avg_income'], 0, ',', '.') }}">Rp {{ number_format($trend['avg_income'], 0, ',', '.') }}</strong></span>
                <span>Rata-rata Keluar: <strong class="font-mono text-red dark:text-rose-300 privacy-balance" data-raw-value="Rp {{ number_format($trend['avg_expense'], 0, ',', '.') }}">Rp {{ number_format($trend['avg_expense'], 0, ',', '.') }}</strong></span>
            </div>
        </div>

        <div class="p-5 space-y-5">
            <div class="h-64 sm:h-72">
                <canvas id="advancedTrendChart"></canvas>
            </div>

            <!-- Mobile list -->
            <div class="md:hidden divide-y divide-line border-t border-line">
                @foreach ($trend['months'] as $tm)
                    <div class="py-2.5 space-y-1 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-charcoal">{{ $tm->label }}</span>
                            <span class="text-[11px] font-mono font-semibold text-muted">
                                Saving: {{ $tm->savings_rate !== null ? $tm->savings_rate.'%' : '-' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono">
                            <span class="text-emerald-600 dark:text-emerald-400">+<span class="privacy-balance" data-raw-value="Rp {{ number_format($tm->income, 0, ',', '.') }}">Rp {{ number_format($tm->income, 0, ',', '.') }}</span></span>
                            <span class="text-red dark:text-rose-300">-<span class="privacy-balance" data-raw-value="Rp {{ number_format($tm->expense, 0, ',', '.') }}">Rp {{ number_format($tm->expense, 0, ',', '.') }}</span></span>
                            <span class="font-bold {{ $tm->net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-300' }}">
                                <span class="privacy-balance" data-raw-value="{{ $tm->net >= 0 ? '+' : '-' }}Rp {{ number_format(abs($tm->net), 0, ',', '.') }}">{{ $tm->net >= 0 ? '+' : '-' }}Rp {{ number_format(abs($tm->net), 0, ',', '.') }}</span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[10.5px] tracking-wider">
                            <th class="py-2.5 px-3">Bulan</th>
                            <th class="py-2.5 px-3 text-right">Pemasukan</th>
                            <th class="py-2.5 px-3 text-right">Pengeluaran</th>
                            <th class="py-2.5 px-3 text-right">Arus Kas Bersih</th>
                            <th class="py-2.5 px-3 text-right">Rasio Tabungan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($trend['months'] as $tm)
                            <tr class="hover:bg-cream/20 transition-colors">
                                <td class="py-2 px-3 font-semibold text-charcoal">{{ $tm->label }}</td>
                                <td class="py-2 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($tm->income, 0, ',', '.') }}">Rp {{ number_format($tm->income, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono text-red dark:text-rose-300">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($tm->expense, 0, ',', '.') }}">Rp {{ number_format($tm->expense, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold {{ $tm->net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-300' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $tm->net >= 0 ? '+' : '-' }}Rp {{ number_format(abs($tm->net), 0, ',', '.') }}">{{ $tm->net >= 0 ? '+' : '-' }}Rp {{ number_format(abs($tm->net), 0, ',', '.') }}</span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold {{ $tm->savings_rate !== null && $tm->savings_rate >= 20 ? 'text-emerald-700 dark:text-emerald-300' : ($tm->savings_rate !== null && $tm->savings_rate < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-muted') }}">
                                    {{ $tm->savings_rate !== null ? $tm->savings_rate.'%' : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Zona 4: Kurva Akumulasi Pengeluaran & Pola Hari dalam Seminggu -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kurva Akumulasi Harian -->
        <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-forest dark:text-blue-300">Kurva Akumulasi Pengeluaran Harian</h2>
                        <p class="text-[11px] text-muted mt-0.5">Kecepatan belanja kumulatif dibanding {{ $comp['prev_label'] }}</p>
                    </div>
                </div>
                <div class="p-5">
                    @if (! $hasAnyCashflow && $prev['expense'] === 0)
                        <div class="p-8 text-center text-xs text-muted border border-dashed border-line rounded-xl">
                            Belum ada data pengeluaran harian untuk digambarkan pada periode ini atau periode pembanding.
                        </div>
                    @else
                        <div class="h-60">
                            <canvas id="cumulativeBurnChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
            <div class="px-5 py-3 bg-cream/20 border-t border-line text-[11px] text-muted">
                @if ($budgetAnalysis['total_limit'] > 0)
                    Garis putus-putus hijau menampilkan laju ideal batas total anggaran bulan ini (Rp {{ number_format($budgetAnalysis['total_limit'], 0, ',', '.') }}).
                @else
                    Kurva yang lebih landai menunjukkan pengendalian pengeluaran yang lebih baik dibanding periode sebelumnya.
                @endif
            </div>
        </div>

        <!-- Pola Pengeluaran per Hari (Senin - Minggu) -->
        <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-forest dark:text-blue-300">Pola Belanja per Hari (Senin - Minggu)</h2>
                        <p class="text-[11px] text-muted mt-0.5">Distribusi pengeluaran dan intensitas transaksi berdasarkan hari</p>
                    </div>
                    @if ($pattern['peak_day'])
                        <span class="px-2 py-0.5 rounded bg-cream dark:bg-line/50 border border-line text-[10.5px] font-bold text-charcoal">
                            Puncak: {{ $pattern['peak_day']->label }} ({{ $pattern['peak_day']->percentage }}%)
                        </span>
                    @endif
                </div>
                <div class="p-5 space-y-4">
                    @if ($curr['expense'] === 0)
                        <div class="p-8 text-center text-xs text-muted border border-dashed border-line rounded-xl">
                            Belum ada pengeluaran tercatat pada periode {{ $periodLabel }}.
                        </div>
                    @else
                        <div class="h-48">
                            <canvas id="weekdayPatternChart"></canvas>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-line text-xs">
                            <div class="p-2.5 rounded-lg bg-cream/30 dark:bg-line/20 border border-line">
                                <span class="text-[10.5px] font-bold uppercase text-muted block">Hari Kerja (Sen-Jum)</span>
                                <span class="font-mono font-bold text-charcoal mt-0.5 block privacy-balance" data-raw-value="Rp {{ number_format($pattern['weekday_total'], 0, ',', '.') }}">
                                    Rp {{ number_format($pattern['weekday_total'], 0, ',', '.') }}
                                </span>
                                <span class="text-[11px] text-muted">{{ round(100 - $pattern['weekend_pct'], 1) }}% porsi</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-cream/30 dark:bg-line/20 border border-line">
                                <span class="text-[10.5px] font-bold uppercase text-muted block">Akhir Pekan (Sab-Min)</span>
                                <span class="font-mono font-bold text-charcoal mt-0.5 block privacy-balance" data-raw-value="Rp {{ number_format($pattern['weekend_total'], 0, ',', '.') }}">
                                    Rp {{ number_format($pattern['weekend_total'], 0, ',', '.') }}
                                </span>
                                <span class="text-[11px] text-muted">{{ $pattern['weekend_pct'] }}% porsi</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="px-5 py-3 bg-cream/20 border-t border-line text-[11px] text-muted">
                Membantu mengenali kebiasaan pengeluaran akhir pekan dibanding hari kerja.
            </div>
        </div>
    </div>

    <!-- Zona 5: Pergerakan Kategori & Konsentrasi Pareto -->
    <div class="panel-card p-0 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-forest dark:text-blue-300">Analisis Pergerakan Kategori (MoM)</h2>
                <p class="text-[11px] text-muted mt-0.5">Perubahan pengeluaran tiap kategori dibanding {{ $comp['prev_label'] }} dan dominasi 3 kategori teratas.</p>
            </div>
            @if ($catAnalysis['active_category_count'] > 0)
                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                    <span class="px-2.5 py-1 rounded bg-cream dark:bg-line/40 border border-line font-semibold text-charcoal">
                        Konsentrasi Top 3: <span class="font-mono font-bold">{{ $catAnalysis['top3_concentration_pct'] }}%</span>
                    </span>
                </div>
            @endif
        </div>

        @if ($catAnalysis['items']->isEmpty())
            <div class="p-8 text-center text-xs text-muted">
                Belum ada pengeluaran per kategori pada periode ini maupun periode pembanding.
            </div>
        @else
            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-line">
                @foreach ($catAnalysis['items'] as $item)
                    <div class="p-4 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-charcoal">{{ $item->name }}</span>
                            <span class="font-mono font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($item->current_amount, 0, ',', '.') }}">
                                Rp {{ number_format($item->current_amount, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-2 text-[11px] text-muted">
                            <span>Lalu: <span class="font-mono privacy-balance" data-raw-value="Rp {{ number_format($item->prev_amount, 0, ',', '.') }}">Rp {{ number_format($item->prev_amount, 0, ',', '.') }}</span></span>
                            <span class="font-mono font-semibold {{ $item->diff_amount > 0 ? 'text-rose-700 dark:text-rose-300' : ($item->diff_amount < 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted') }}">
                                {{ $item->diff_amount > 0 ? '+' : ($item->diff_amount < 0 ? '-' : '') }}Rp {{ number_format(abs($item->diff_amount), 0, ',', '.') }}
                                @if ($item->diff_pct !== null)
                                    ({{ $item->diff_pct > 0 ? '+' : '' }}{{ $item->diff_pct }}%)
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[10.5px] tracking-wider">
                            <th class="py-2.5 px-5">Kategori</th>
                            <th class="py-2.5 px-3 text-right">Trx</th>
                            <th class="py-2.5 px-3 text-right">Periode Ini</th>
                            <th class="py-2.5 px-3 text-right">Periode Lalu</th>
                            <th class="py-2.5 px-3 text-right">Perubahan</th>
                            <th class="py-2.5 px-5 text-right">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($catAnalysis['items'] as $item)
                            <tr class="hover:bg-cream/20 transition-colors">
                                <td class="py-2.5 px-5 font-semibold text-charcoal">{{ $item->name }}</td>
                                <td class="py-2.5 px-3 text-right font-mono text-muted">{{ $item->tx_count }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-charcoal">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($item->current_amount, 0, ',', '.') }}">Rp {{ number_format($item->current_amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-muted">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($item->prev_amount, 0, ',', '.') }}">Rp {{ number_format($item->prev_amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-semibold {{ $item->diff_amount > 0 ? 'text-rose-700 dark:text-rose-300' : ($item->diff_amount < 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted') }}">
                                    <span class="privacy-balance" data-raw-value="{{ $item->diff_amount > 0 ? '+' : ($item->diff_amount < 0 ? '-' : '') }}Rp {{ number_format(abs($item->diff_amount), 0, ',', '.') }}">
                                        {{ $item->diff_amount > 0 ? '+' : ($item->diff_amount < 0 ? '-' : '') }}Rp {{ number_format(abs($item->diff_amount), 0, ',', '.') }}
                                    </span>
                                    @if ($item->diff_pct !== null)
                                        <span class="text-[10.5px]">({{ $item->diff_pct > 0 ? '+' : '' }}{{ $item->diff_pct }}%)</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-5 text-right font-mono font-bold text-forest dark:text-blue-300">
                                    {{ $item->share_pct }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Zona 6: Efisiensi Anggaran & Proyeksi Kewajiban 6 Bulan -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Efisiensi & Proyeksi Anggaran -->
        <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-forest dark:text-blue-300">Efisiensi & Proyeksi Anggaran</h2>
                        <p class="text-[11px] text-muted mt-0.5">Realisasi terhadap batas kategori dan potensi pelampauan</p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[10px] font-bold {{ $toneBadge($signals['budget']['tone']) }}">
                        {{ $signals['budget']['label'] }}
                    </span>
                </div>

                <div class="p-5 space-y-4">
                    @if ($periodType !== 'month')
                        <div class="p-6 text-center text-xs text-muted border border-dashed border-line rounded-xl">
                            Evaluasi anggaran tersedia pada mode periode <strong>Bulanan</strong> karena batas anggaran diatur per bulan.
                        </div>
                    @elseif (! $budgetAnalysis['has_budgets'])
                        <div class="p-6 text-center space-y-2 border border-dashed border-line rounded-xl">
                            <p class="text-xs text-muted">Belum ada anggaran bulanan yang ditetapkan untuk {{ $periodLabel }}.</p>
                            <a href="{{ route('budgets.index') }}" class="inline-flex items-center justify-center min-h-[36px] px-3 py-1.5 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-bold text-forest transition">
                                Atur Anggaran Bulan Ini
                            </a>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($budgetAnalysis['items'] as $bItem)
                                @php
                                    $barColor = match ($bItem->status) {
                                        'over' => 'bg-rose-500',
                                        'waspada' => 'bg-amber-500',
                                        default => 'bg-emerald-600',
                                    };
                                    $statusText = match ($bItem->status) {
                                        'over' => 'Melebihi Batas',
                                        'waspada' => 'Waspada Pace',
                                        default => 'Aman',
                                    };
                                    $statusBadge = match ($bItem->status) {
                                        'over' => $toneBadge('danger'),
                                        'waspada' => $toneBadge('warning'),
                                        default => $toneBadge('positive'),
                                    };
                                @endphp
                                <div class="p-3 rounded-xl border border-line bg-cream/20 dark:bg-line/15 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-charcoal">{{ $bItem->category_name }}</span>
                                        <span class="px-2 py-0.5 rounded border text-[10px] font-bold {{ $statusBadge }}">{{ $statusText }}</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-cream dark:bg-line/60 overflow-hidden" aria-hidden="true">
                                        <div class="h-full {{ $barColor }}" style="width: {{ min(100, $bItem->pct_used) }}%"></div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono text-muted">
                                        <span>
                                            Terpakai: <strong class="text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($bItem->spent, 0, ',', '.') }}">Rp {{ number_format($bItem->spent, 0, ',', '.') }}</strong>
                                            / <span class="privacy-balance" data-raw-value="Rp {{ number_format($bItem->limit, 0, ',', '.') }}">Rp {{ number_format($bItem->limit, 0, ',', '.') }}</span>
                                        </span>
                                        <span>{{ $bItem->pct_used }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($budgetAnalysis['unbudgeted']->isNotEmpty())
                        <div class="pt-3 border-t border-line">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Pengeluaran Kategori Tanpa Anggaran</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($budgetAnalysis['unbudgeted'] as $ub)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-line bg-panel text-[11px]">
                                        <span class="font-semibold text-charcoal">{{ $ub->name }}:</span>
                                        <span class="font-mono font-bold text-rose-700 dark:text-rose-300 privacy-balance" data-raw-value="Rp {{ number_format($ub->amount, 0, ',', '.') }}">Rp {{ number_format($ub->amount, 0, ',', '.') }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Proyeksi Kewajiban 6 Bulan Ke Depan -->
        <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-forest dark:text-blue-300">Proyeksi Kewajiban 6 Bulan Ke Depan</h2>
                        <p class="text-[11px] text-muted mt-0.5">Estimasi cicilan aktif dan jadwal pengeluaran rutin bulanan</p>
                    </div>
                    <span class="font-mono text-xs font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($forecast['total_6_months'], 0, ',', '.') }}">
                        Total: Rp {{ number_format($forecast['total_6_months'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="p-5 space-y-4">
                    @if ($forecast['total_6_months'] === 0)
                        <div class="p-6 text-center text-xs text-muted border border-dashed border-line rounded-xl">
                            Tidak ada cicilan kredit atau jadwal pengeluaran rutin aktif dalam 6 bulan ke depan.
                        </div>
                    @else
                        <div class="h-48">
                            <canvas id="commitmentsForecastChart"></canvas>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 border-t border-line text-xs">
                        <div class="p-2.5 rounded-lg bg-cream/30 dark:bg-line/20 border border-line">
                            <span class="text-[10px] font-bold uppercase text-muted block">Sisa Pokok Cicilan</span>
                            <span class="font-mono font-bold text-charcoal mt-0.5 block privacy-balance" data-raw-value="Rp {{ number_format($forecast['remaining_principal'], 0, ',', '.') }}">
                                Rp {{ number_format($forecast['remaining_principal'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-cream/30 dark:bg-line/20 border border-line">
                            <span class="text-[10px] font-bold uppercase text-muted block">Bunga & Admin Tersisa</span>
                            <span class="font-mono font-bold text-amber-700 dark:text-amber-300 mt-0.5 block privacy-balance" data-raw-value="Rp {{ number_format($forecast['remaining_interest_and_fee'], 0, ',', '.') }}">
                                Rp {{ number_format($forecast['remaining_interest_and_fee'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-cream/30 dark:bg-line/20 border border-line">
                            <span class="text-[10px] font-bold uppercase text-muted block">Short Plan Aktif</span>
                            <span class="font-mono font-bold text-forest dark:text-blue-300 mt-0.5 block privacy-balance" data-raw-value="Rp {{ number_format($forecast['planned_short_plans_total'], 0, ',', '.') }}">
                                Rp {{ number_format($forecast['planned_short_plans_total'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Zona 7: 5 Pengeluaran Terbesar & Sebaran Anggota -->
    <div class="grid grid-cols-1 {{ $members->isNotEmpty() ? 'lg:grid-cols-12' : '' }} gap-6">
        <div class="{{ $members->isNotEmpty() ? 'lg:col-span-7' : '' }} panel-card p-0 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                <h2 class="text-sm font-bold text-forest dark:text-blue-300">5 Pengeluaran Terbesar Periode Ini</h2>
                <span class="text-xs text-muted">{{ $periodLabel }}</span>
            </div>

            @if ($topExpenses->isEmpty())
                <div class="p-8 text-center text-xs text-muted">
                    Belum ada transaksi pengeluaran pada periode ini.
                </div>
            @else
                <div class="divide-y divide-line">
                    @foreach ($topExpenses as $idx => $txItem)
                        <div class="p-4 sm:px-6 flex items-center justify-between gap-3 hover:bg-cream/20 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 rounded-md bg-cream dark:bg-line/50 border border-line text-[11px] font-mono font-bold text-charcoal flex items-center justify-center shrink-0">
                                    {{ $idx + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ $txItem->url }}" class="text-xs font-bold text-charcoal hover:text-forest hover:underline block truncate">
                                        {{ $txItem->description }}
                                    </a>
                                    <div class="text-[11px] text-muted flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5">
                                        <span>{{ $txItem->date }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $txItem->category_name }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $txItem->account_name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="font-mono font-bold text-xs sm:text-sm text-red dark:text-rose-300 shrink-0">
                                <span class="privacy-balance" data-raw-value="-Rp {{ number_format($txItem->amount, 0, ',', '.') }}">
                                    -Rp {{ number_format($txItem->amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($members->isNotEmpty())
            <div class="lg:col-span-5 panel-card p-0 overflow-hidden">
                <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                    <h2 class="text-sm font-bold text-forest dark:text-blue-300">Pengeluaran per Anggota</h2>
                    <span class="text-xs text-muted">{{ $members->count() }} kelompok</span>
                </div>
                <div class="p-5 space-y-3">
                    @foreach ($members as $mem)
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $mem->color }}"></span>
                                    <span class="font-bold text-charcoal truncate">{{ $mem->name }}</span>
                                    <span class="text-[11px] text-muted">({{ $mem->count }} trx)</span>
                                </div>
                                <div class="font-mono font-bold text-charcoal shrink-0">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($mem->amount, 0, ',', '.') }}">Rp {{ number_format($mem->amount, 0, ',', '.') }}</span>
                                    <span class="text-muted font-normal ml-1">{{ $mem->percentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full h-2 rounded-full bg-cream dark:bg-line/50 overflow-hidden" aria-hidden="true">
                                <div class="h-full rounded-full" style="width: {{ min(100, $mem->percentage) }}%; background-color: {{ $mem->color }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleAdvancedPeriodInputs(mode) {
        const monthBox = document.getElementById('advMonthInputs');
        const customBox = document.getElementById('advCustomInputs');
        if (!monthBox || !customBox) return;

        if (mode === 'custom') {
            monthBox.className = 'hidden';
            customBox.className = 'contents';
        } else {
            monthBox.className = 'contents';
            customBox.className = 'hidden';
        }
    }

    const advCharts = {
        trend: null,
        cumulative: null,
        weekday: null,
        forecast: null,
    };

    function initAdvancedCharts() {
        if (typeof Chart === 'undefined') {
            setTimeout(initAdvancedCharts, 80);
            return;
        }

        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#CBD5E1' : '#526070';
        const gridColor = isDark ? 'rgba(52, 72, 61, 0.45)' : 'rgba(200, 215, 229, 0.55)';
        const primaryBlue = isDark ? '#60A5FA' : '#0B4A8B';
        const incomeGreen = isDark ? '#34D399' : '#059669';
        const dangerRed = isDark ? '#F87171' : '#D14343';
        const savingsRateLine = isDark ? '#38BDF8' : '#0284C7';
        const emeraldLine = isDark ? '#34D399' : '#059669';
        const amberBar = isDark ? '#FBBF24' : '#D97706';

        function compactRp(value) {
            const abs = Math.abs(value);
            if (abs >= 1000000) return (value / 1000000).toFixed(1).replace(/\.0$/, '') + ' jt';
            if (abs >= 1000) return (value / 1000).toFixed(0) + ' rb';
            return value;
        }

        // 1. Tren Arus Kas & Savings Rate
        const trendCtx = document.getElementById('advancedTrendChart');
        if (trendCtx) {
            if (advCharts.trend) {
                advCharts.trend.destroy();
                advCharts.trend = null;
            }
            advCharts.trend = new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: @json(collect($trend['months'])->pluck('short_label')),
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: @json(collect($trend['months'])->pluck('income')),
                            backgroundColor: incomeGreen,
                            borderRadius: 4,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Pengeluaran',
                            data: @json(collect($trend['months'])->pluck('expense')),
                            backgroundColor: dangerRed,
                            borderRadius: 4,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Rasio Tabungan (%)',
                            data: @json(collect($trend['months'])->pluck('savings_rate')),
                            type: 'line',
                            borderColor: savingsRateLine,
                            backgroundColor: savingsRateLine,
                            borderWidth: 2,
                            pointRadius: 3,
                            tension: 0.25,
                            spanGaps: true,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                        y: {
                            position: 'left',
                            ticks: { color: textColor, font: { size: 10 }, callback: compactRp },
                            grid: { color: gridColor }
                        },
                        y1: {
                            position: 'right',
                            ticks: { color: savingsRateLine, font: { size: 10 }, callback: (v) => v + '%' },
                            grid: { drawOnChartArea: false }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: textColor, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    if (ctx.dataset.yAxisID === 'y1') {
                                        return ctx.dataset.label + ': ' + (ctx.raw ?? '-') + '%';
                                    }
                                    return ctx.dataset.label + ': Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Kurva Akumulasi Pengeluaran Harian
        const cumCtx = document.getElementById('cumulativeBurnChart');
        if (cumCtx) {
            if (advCharts.cumulative) {
                advCharts.cumulative.destroy();
                advCharts.cumulative = null;
            }
            const cumDatasets = [
                {
                    label: 'Periode Ini',
                    data: @json($pattern['cumulative']['current']),
                    borderColor: dangerRed,
                    backgroundColor: 'transparent',
                    borderWidth: 2.5,
                    pointRadius: 1.5,
                    tension: 0.2,
                },
                {
                    label: 'Periode Lalu',
                    data: @json($pattern['cumulative']['previous']),
                    borderColor: textColor,
                    borderDash: [5, 4],
                    backgroundColor: 'transparent',
                    borderWidth: 1.75,
                    pointRadius: 0,
                    tension: 0.2,
                }
            ];

            const budgetLine = @json($pattern['cumulative']['budget_line']);
            if (budgetLine && budgetLine.length > 0) {
                cumDatasets.push({
                    label: 'Laju Target Anggaran',
                    data: budgetLine,
                    borderColor: emeraldLine,
                    borderDash: [2, 3],
                    backgroundColor: 'transparent',
                    borderWidth: 1.75,
                    pointRadius: 0,
                });
            }

            advCharts.cumulative = new Chart(cumCtx, {
                type: 'line',
                data: {
                    labels: @json($pattern['cumulative']['labels']),
                    datasets: cumDatasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { ticks: { color: textColor, font: { size: 10 }, maxTicksLimit: 10 }, grid: { display: false } },
                        y: { ticks: { color: textColor, font: { size: 10 }, callback: compactRp }, grid: { color: gridColor } }
                    },
                    plugins: {
                        legend: { labels: { color: textColor, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.dataset.label + ': Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID')
                            }
                        }
                    }
                }
            });
        }

        // 3. Pola Pengeluaran per Hari (Senin - Minggu)
        const dowCtx = document.getElementById('weekdayPatternChart');
        if (dowCtx) {
            if (advCharts.weekday) {
                advCharts.weekday.destroy();
                advCharts.weekday = null;
            }
            advCharts.weekday = new Chart(dowCtx, {
                type: 'bar',
                data: {
                    labels: @json(collect($pattern['weekdays'])->pluck('short')),
                    datasets: [{
                        label: 'Total Pengeluaran',
                        data: @json(collect($pattern['weekdays'])->pluck('amount')),
                        backgroundColor: @json(collect($pattern['weekdays'])->map(fn ($w) => (data_get($w, 'iso') ?? 0) >= 6)->values()).map(isWeekend => isWeekend ? amberBar : primaryBlue),
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                        y: { ticks: { color: textColor, font: { size: 10 }, callback: compactRp }, grid: { color: gridColor } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => 'Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID')
                            }
                        }
                    }
                }
            });
        }

        // 4. Proyeksi Kewajiban 6 Bulan
        const fcCtx = document.getElementById('commitmentsForecastChart');
        if (fcCtx) {
            if (advCharts.forecast) {
                advCharts.forecast.destroy();
                advCharts.forecast = null;
            }
            advCharts.forecast = new Chart(fcCtx, {
                type: 'bar',
                data: {
                    labels: @json(collect($forecast['months'])->pluck('label')),
                    datasets: [
                        {
                            label: 'Cicilan Kredit',
                            data: @json(collect($forecast['months'])->pluck('installments')),
                            backgroundColor: dangerRed,
                            borderRadius: 4,
                        },
                        {
                            label: 'Jadwal Rutin',
                            data: @json(collect($forecast['months'])->pluck('recurring')),
                            backgroundColor: amberBar,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                        y: { stacked: true, ticks: { color: textColor, font: { size: 10 }, callback: compactRp }, grid: { color: gridColor } }
                    },
                    plugins: {
                        legend: { labels: { color: textColor, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.dataset.label + ': Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID')
                            }
                        }
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdvancedCharts);
    } else {
        initAdvancedCharts();
    }

    window.addEventListener('themeChanged', initAdvancedCharts);
</script>
