<x-layouts.app title="Proyeksi Saldo dan Arus Kas">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / PERENCANAAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Proyeksi Saldo dan Arus Kas
            </h1>
            <p class="text-xs text-muted">
                Perkiraan saldo dan arus kas mendatang dari transaksi berulang, cicilan aktif, dan rencana pemasukan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a 
                href="{{ route('projections.export-csv', request()->query()) }}" 
                class="btn-arus-secondary text-xs h-10 px-3.5 inline-flex items-center gap-1.5 shadow-sm"
                title="Unduh proyeksi arus kas dalam format CSV"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Ekspor CSV</span>
            </a>

            <button 
                type="button" 
                onclick="openCreatePlannedIncomeModal()" 
                class="btn-arus-primary text-xs h-10 px-4 inline-flex items-center gap-1.5 shadow-sm cursor-pointer"
            >
                <span class="text-base leading-none font-bold">+</span>
                <span>Rencana Pemasukan</span>
            </button>
        </div>
    </div>

    <!-- Tab Navigasi Halaman -->
    <div class="flex items-center gap-2 mb-6 border-b border-line overflow-x-auto whitespace-nowrap">
        <a 
            href="{{ route('projections.index', array_merge(request()->query(), ['tab' => 'projection'])) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 {{ $activeTab === 'projection' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
            </svg>
            <span>Simulasi dan Grafik Proyeksi</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $activeTab === 'projection' ? 'bg-forest/15 text-forest' : 'bg-cream text-muted dark:bg-line/50' }}">
                {{ $projection['horizon_days'] }} Hari
            </span>
        </a>

        <a 
            href="{{ route('projections.index', array_merge(request()->query(), ['tab' => 'planned_incomes'])) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 {{ $activeTab === 'planned_incomes' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>Kelola Rencana Pemasukan</span>
            @if ($plannedIncomeSummary['count_planned'] > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    {{ $plannedIncomeSummary['count_planned'] }} aktif
                </span>
            @endif
        </a>
    </div>

    @if ($activeTab === 'projection')
        <!-- ========================================== -->
        <!-- TAB 1: SIMULASI & PROYEKSI SALDO           -->
        <!-- ========================================== -->

        <!-- Filter & Parameter Simulasi Panel -->
        <div class="panel-card p-4 sm:p-5 mb-6 space-y-4">
            <form method="GET" action="{{ route('projections.index') }}" id="projectionFilterForm">
                <input type="hidden" name="tab" value="projection">
                
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Pilihan Horizon Hari -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-muted block">
                            Rentang Horizon Proyeksi
                        </label>
                        <div class="inline-flex flex-wrap items-center gap-1.5 bg-cream/50 dark:bg-line/30 p-1 rounded-xl border border-line">
                            @foreach ([30 => '30 Hari', 60 => '60 Hari', 90 => '3 Bulan', 180 => '6 Bulan', 365 => '1 Tahun'] as $days => $label)
                                <button 
                                    type="submit" 
                                    name="horizon" 
                                    value="{{ $days }}"
                                    class="min-h-[36px] px-3 py-1.5 text-xs font-semibold rounded-lg transition cursor-pointer {{ (int)$horizonDays === (int)$days ? 'bg-forest text-white shadow-xs font-bold' : 'text-charcoal hover:bg-cream dark:hover:bg-line/60' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Filter Rekening & Reset -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="w-full sm:w-auto min-w-[200px]">
                            <label for="filterAccountId" class="text-[11px] font-bold uppercase tracking-wider text-muted block mb-1">
                                Sumber Saldo Rekening
                            </label>
                            <select 
                                name="account_id" 
                                id="filterAccountId" 
                                class="field-select text-xs h-10 w-full"
                                onchange="document.getElementById('projectionFilterForm').submit()"
                            >
                                <option value="" {{ empty($selectedAccountId) ? 'selected' : '' }}>
                                    Semua Rekening Kas & Bank (Gabungan)
                                </option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (int)$selectedAccountId === (int)$acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} ({{ $acc->typeLabel() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Komponen yang Disertakan dalam Simulasi (Checkboxes) -->
                <div class="pt-3 mt-3 border-t border-line flex flex-wrap items-center gap-x-6 gap-y-2">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Komponen Simulasi:</span>

                    <label class="inline-flex items-center gap-2 text-xs text-charcoal font-medium cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="include_recurring" 
                            value="1" 
                            {{ $includeRecurring ? 'checked' : '' }} 
                            class="field-checkbox"
                            onchange="document.getElementById('projectionFilterForm').submit()"
                        >
                        <span>Transaksi Rutin ({{ $projection['counts']['recurring_count'] }})</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-xs text-charcoal font-medium cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="include_installments" 
                            value="1" 
                            {{ $includeInstallments ? 'checked' : '' }} 
                            class="field-checkbox"
                            onchange="document.getElementById('projectionFilterForm').submit()"
                        >
                        <span>Cicilan Tempo ({{ $projection['counts']['installment_count'] }})</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-xs text-charcoal font-medium cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="include_planned_incomes" 
                            value="1" 
                            {{ $includePlannedIncomes ? 'checked' : '' }} 
                            class="field-checkbox"
                            onchange="document.getElementById('projectionFilterForm').submit()"
                        >
                        <span>Rencana Pemasukan ({{ $projection['counts']['planned_income_count'] }})</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-xs text-charcoal font-medium cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="include_short_plans" 
                            value="1" 
                            {{ $includeShortPlans ? 'checked' : '' }} 
                            class="field-checkbox"
                            onchange="document.getElementById('projectionFilterForm').submit()"
                        >
                        <span>Short Plan Fleksibel ({{ $projection['counts']['short_plan_count'] }})</span>
                    </label>
                </div>
            </form>
        </div>

        <!-- Indikator Risiko / Peringatan Defisit Kas -->
        @if ($projection['is_deficit_risk'])
            <div class="mb-6 p-4 rounded-xl border border-red-300 bg-red-50 dark:bg-red-950/30 dark:border-red-900/50 flex items-start gap-3.5 shadow-sm" role="alert">
                <div class="w-8 h-8 rounded-lg bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 flex items-center justify-center shrink-0 font-bold text-base">
                    !
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-red-900 dark:text-red-200">
                        Peringatan Risiko Defisit Kas!
                    </h3>
                    <p class="text-xs text-red-800 dark:text-red-300/90 mt-1 leading-relaxed">
                        Berdasarkan jadwal kewajiban aktif, saldo kas diperkirakan akan mengalami <strong>defisit (minus)</strong> mulai tanggal 
                        <strong>{{ \Illuminate\Support\Carbon::parse($projection['deficit_date'])->translatedFormat('l, d F Y') }}</strong> 
                        dengan titik terendah mencapai <strong class="font-mono text-red-700 dark:text-red-400">Rp {{ number_format($projection['lowest_balance'], 0, ',', '.') }}</strong> 
                        pada tanggal <strong>{{ \Illuminate\Support\Carbon::parse($projection['lowest_balance_date'])->translatedFormat('d M Y') }}</strong>.
                        Disarankan untuk melakukan penyesuaian pos pengeluaran atau mempercepat pencairan rencana pemasukan sebelum tanggal tersebut.
                    </p>
                </div>
            </div>
        @else
            <div class="mb-6 p-4 rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-900/40 flex items-start gap-3.5 shadow-sm">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 flex items-center justify-center shrink-0 font-bold text-base">
                    ✓
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">
                        Likuiditas Kas Diproyeksikan Aman
                    </h3>
                    <p class="text-xs text-emerald-800 dark:text-emerald-300/90 mt-0.5 leading-relaxed">
                        Arus kas dan saldo diproyeksikan tetap positif sepanjang rentang <strong>{{ $projection['horizon_days'] }} hari</strong> ke depan. 
                        Titik saldo terendah kas Anda diperkirakan berada di nominal 
                        <strong class="font-mono">Rp {{ number_format($projection['lowest_balance'], 0, ',', '.') }}</strong> 
                        pada tanggal <strong>{{ \Illuminate\Support\Carbon::parse($projection['lowest_balance_date'])->translatedFormat('d M Y') }}</strong>.
                    </p>
                </div>
            </div>
        @endif

        <!-- 6 Ringkasan KPI Proyeksi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-6">
            <!-- 1. Saldo Kas Awal -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Saldo Kas Awal</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-charcoal">
                        Rp {{ number_format($projection['starting_balance'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-muted block truncate mt-0.5" title="{{ $projection['selected_account']?->name ?? 'Semua Kas & Bank' }}">
                        {{ $projection['selected_account']?->name ?? 'Kas & Bank Riil' }}
                    </span>
                </div>
            </div>

            <!-- 2. Estimasi Pemasukan -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total Est. Masuk</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-emerald-700 dark:text-emerald-400">
                        +Rp {{ number_format($projection['total_inflows'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-muted block mt-0.5">
                        {{ $projection['counts']['planned_income_count'] }} rencana & rutin
                    </span>
                </div>
            </div>

            <!-- 3. Estimasi Pengeluaran -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400">Total Est. Keluar</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-red-700 dark:text-red-400">
                        -Rp {{ number_format($projection['total_outflows'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-muted block mt-0.5">
                        {{ $projection['counts']['recurring_count'] + $projection['counts']['installment_count'] }} komitmen
                    </span>
                </div>
            </div>

            <!-- 4. Arus Kas Bersih -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Arus Kas Bersih</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold {{ $projection['net_change'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                        {{ $projection['net_change'] >= 0 ? '+' : '' }}Rp {{ number_format($projection['net_change'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-muted block mt-0.5">
                        {{ $projection['net_change'] >= 0 ? 'Surplus Kas' : 'Defisit Arus' }}
                    </span>
                </div>
            </div>

            <!-- 5. Proyeksi Saldo Akhir -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-forest dark:text-blue-300">Proyeksi Saldo Akhir</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold {{ $projection['ending_balance'] >= 0 ? 'text-forest dark:text-blue-300' : 'text-red-700 dark:text-red-400' }}">
                        Rp {{ number_format($projection['ending_balance'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-muted block mt-0.5">
                        Per {{ \Illuminate\Support\Carbon::parse($projection['end_date'])->translatedFormat('d M Y') }}
                    </span>
                </div>
            </div>

            <!-- 6. Titik Saldo Terendah -->
            <div class="panel-card p-4 flex flex-col justify-between {{ $projection['lowest_balance'] < 0 ? 'border-red-300 bg-red-50/40 dark:bg-red-950/20' : '' }}">
                <span class="text-[10px] font-bold uppercase tracking-wider {{ $projection['lowest_balance'] < 0 ? 'text-red-700 dark:text-red-400' : 'text-muted' }}">
                    Titik Terendah
                </span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold {{ $projection['lowest_balance'] >= 0 ? 'text-charcoal' : 'text-red-700 dark:text-red-400' }}">
                        Rp {{ number_format($projection['lowest_balance'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-muted block mt-0.5">
                        {{ \Illuminate\Support\Carbon::parse($projection['lowest_balance_date'])->translatedFormat('d M Y') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Visual Grafik Proyeksi Saldo & Arus Kas Harian -->
        <div class="panel-card p-4 sm:p-6 mb-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-line pb-3">
                <div>
                    <h2 class="text-sm font-bold text-forest dark:text-blue-300">Kurva Proyeksi Saldo Harian</h2>
                    <p class="text-[11px] text-muted mt-0.5">
                        Pergerakan saldo kumulatif dan mutasi kas harian selama periode {{ \Illuminate\Support\Carbon::parse($projection['start_date'])->translatedFormat('d M Y') }} hingga {{ \Illuminate\Support\Carbon::parse($projection['end_date'])->translatedFormat('d M Y') }}.
                    </p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 text-muted">
                        <span class="w-2.5 h-2.5 rounded-full bg-forest shrink-0"></span>
                        <span>Saldo Kumulatif</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-muted">
                        <span class="w-2.5 h-2.5 rounded-xs bg-emerald-600 shrink-0"></span>
                        <span>Kas Masuk</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-muted">
                        <span class="w-2.5 h-2.5 rounded-xs bg-red-600 shrink-0"></span>
                        <span>Kas Keluar</span>
                    </span>
                </div>
            </div>

            <div class="h-64 sm:h-72 w-full relative">
                <canvas id="cashflowProjectionChart"></canvas>
            </div>
        </div>

        <!-- Rincian Komposisi Sumber Arus Kas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Komposisi Pemasukan -->
            <div class="panel-card p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-line pb-2">
                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">
                        Komposisi Rencana Pemasukan
                    </span>
                    <span class="font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">
                        Total: Rp {{ number_format($projection['total_inflows'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-cream/40 dark:bg-line/20">
                        <span class="text-charcoal font-medium">Rencana Pemasukan Spesifik (Gaji/Bonus/Piutang)</span>
                        <span class="font-mono font-bold text-charcoal">
                            Rp {{ number_format($projection['breakdown']['planned_incomes'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-cream/40 dark:bg-line/20">
                        <span class="text-charcoal font-medium">Pemasukan Rutin Terjadwal</span>
                        <span class="font-mono font-bold text-charcoal">
                            Rp {{ number_format($projection['breakdown']['recurring_income'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Komposisi Pengeluaran & Kewajiban -->
            <div class="panel-card p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-line pb-2">
                    <span class="text-xs font-bold text-red-800 dark:text-red-300 uppercase tracking-wider">
                        Komposisi Kewajiban & Komitmen
                    </span>
                    <span class="font-mono text-xs font-bold text-red-700 dark:text-red-400">
                        Total: Rp {{ number_format($projection['total_outflows'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-cream/40 dark:bg-line/20">
                        <span class="text-charcoal font-medium">Tagihan Rutin & Pengeluaran Berulang</span>
                        <span class="font-mono font-bold text-charcoal">
                            Rp {{ number_format($projection['breakdown']['recurring_expense'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-cream/40 dark:bg-line/20">
                        <span class="text-charcoal font-medium">Cicilan Aktif Jatuh Tempo</span>
                        <span class="font-mono font-bold text-charcoal">
                            Rp {{ number_format($projection['breakdown']['installments'], 0, ',', '.') }}
                        </span>
                    </div>
                    @if ($includeShortPlans)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-cream/40 dark:bg-line/20">
                            <span class="text-charcoal font-medium">Short Plan (Belanja Terencana)</span>
                            <span class="font-mono font-bold text-charcoal">
                                Rp {{ number_format($projection['breakdown']['short_plans'], 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabel Kronologi Arus Kas Proyeksi -->
        <div class="panel-card p-0 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-forest dark:text-blue-300">
                        Kronologi Kejadian Arus Kas & Saldo Berjalan
                    </h2>
                    <p class="text-[11px] text-muted mt-0.5">
                        Urutan waktu setiap arus kas masuk dan kewajiban tempo beserta proyeksi saldo setelah mutasi.
                    </p>
                </div>
                <span class="text-xs text-muted font-medium">
                    Total {{ count($projection['timeline_events']) }} mutasi terproyeksi
                </span>
            </div>

            @if (empty($projection['timeline_events']))
                <div class="p-8 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-cream dark:bg-line/40 flex items-center justify-center text-xl text-muted">
                        📅
                    </div>
                    <h3 class="text-sm font-bold text-charcoal">Tidak Ada Kejadian Kas Terjadwal</h3>
                    <p class="text-xs text-muted max-w-md mx-auto">
                        Tidak ada transaksi berulang, cicilan jatuh tempo, atau rencana pemasukan pada rentang {{ $projection['horizon_days'] }} hari ini.
                    </p>
                    <button 
                        type="button" 
                        onclick="openCreatePlannedIncomeModal()" 
                        class="btn-arus-primary text-xs h-9 px-4 inline-flex items-center gap-1.5"
                    >
                        + Tambah Rencana Pemasukan
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-cream/40 dark:bg-line/20 text-[11px] font-bold text-muted uppercase tracking-wider">
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Sumber</th>
                                <th class="py-3 px-4">Deskripsi / Judul</th>
                                <th class="py-3 px-4">Kategori & Rekening</th>
                                <th class="py-3 px-4 text-right">Mutasi Kas</th>
                                <th class="py-3 px-4 text-right">Proyeksi Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($projection['timeline_events'] as $evt)
                                <tr class="hover:bg-cream/30 dark:hover:bg-line/10 transition-colors {{ $evt['is_negative'] ? 'bg-red-50/50 dark:bg-red-950/20' : '' }}">
                                    <!-- Tanggal -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-bold text-charcoal">{{ $evt['date_formatted'] }}</div>
                                        <div class="text-[10.5px] text-muted">{{ $evt['day_name'] }}</div>
                                    </td>

                                    <!-- Sumber Badge -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if ($evt['source_type'] === 'planned_income')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                <span>💰</span> {{ $evt['source_label'] }}
                                            </span>
                                        @elseif ($evt['source_type'] === 'installment')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                                <span>🛡️</span> {{ $evt['source_label'] }}
                                            </span>
                                        @elseif ($evt['source_type'] === 'short_plan')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                                                <span>📝</span> {{ $evt['source_label'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                                <span>📅</span> {{ $evt['source_label'] }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Deskripsi -->
                                    <td class="py-3 px-4">
                                        @if ($evt['detail_url'])
                                            <a href="{{ $evt['detail_url'] }}" class="font-bold text-forest dark:text-blue-300 hover:underline">
                                                {{ $evt['title'] }}
                                            </a>
                                        @else
                                            <span class="font-bold text-charcoal">{{ $evt['title'] }}</span>
                                        @endif
                                    </td>

                                    <!-- Kategori & Rekening -->
                                    <td class="py-3 px-4">
                                        <div class="text-charcoal font-medium">{{ $evt['category_name'] }}</div>
                                        <div class="text-[10.5px] text-muted">{{ $evt['account_name'] }}</div>
                                    </td>

                                    <!-- Mutasi Kas -->
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if ($evt['flow'] === 'in')
                                            <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                                +Rp {{ number_format($evt['amount'], 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="font-mono font-bold text-red-700 dark:text-red-400">
                                                -Rp {{ number_format($evt['amount'], 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Proyeksi Saldo -->
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="font-mono font-bold {{ $evt['is_negative'] ? 'text-red-700 dark:text-red-400' : 'text-charcoal' }}">
                                            Rp {{ number_format($evt['running_balance'], 0, ',', '.') }}
                                        </div>
                                        @if ($evt['is_negative'])
                                            <span class="inline-block text-[9.5px] font-bold text-red-700 dark:text-red-400 uppercase tracking-wider">
                                                Defisit Kas
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    @else
        <!-- ========================================== -->
        <!-- TAB 2: KELOLA RENCANA PEMASUKAN            -->
        <!-- ========================================== -->

        <!-- KPI Ringkasan Rencana Pemasukan -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="panel-card p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Total Rencana Aktif</span>
                <div class="text-xl font-mono font-bold text-forest dark:text-blue-300 mt-1">
                    Rp {{ number_format($plannedIncomeSummary['total_planned'], 0, ',', '.') }}
                </div>
                <span class="text-xs text-muted mt-0.5 block">
                    {{ $plannedIncomeSummary['count_planned'] }} rencana belum diterima
                </span>
            </div>

            <div class="panel-card p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total Telah Diterima</span>
                <div class="text-xl font-mono font-bold text-emerald-700 dark:text-emerald-400 mt-1">
                    Rp {{ number_format($plannedIncomeSummary['total_received'], 0, ',', '.') }}
                </div>
                <span class="text-xs text-muted mt-0.5 block">
                    {{ $plannedIncomeSummary['count_received'] }} rencana telah terealisasi
                </span>
            </div>

            <div class="panel-card p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Total Semua Rencana</span>
                <div class="text-xl font-mono font-bold text-charcoal mt-1">
                    {{ $plannedIncomes->count() }}
                </div>
                <span class="text-xs text-muted mt-0.5 block">
                    Termasuk {{ $plannedIncomeSummary['count_cancelled'] }} dibatalkan
                </span>
            </div>
        </div>

        <!-- Tabel Daftar Rencana Pemasukan -->
        <div class="panel-card p-0 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-forest dark:text-blue-300">
                        Daftar Rencana Pemasukan Mendatang
                    </h2>
                    <p class="text-[11px] text-muted mt-0.5">
                        Kelola sumber pemasukan yang diharapkan seperti gaji, bonus, dividen, penjualan, atau piutang.
                    </p>
                </div>
                <button 
                    type="button" 
                    onclick="openCreatePlannedIncomeModal()" 
                    class="btn-arus-primary text-xs h-9 px-3.5 inline-flex items-center gap-1.5"
                >
                    <span class="text-sm font-bold">+</span>
                    <span>Buat Rencana Pemasukan</span>
                </button>
            </div>

            @if ($plannedIncomes->isEmpty())
                <div class="p-8 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-cream dark:bg-line/40 flex items-center justify-center text-xl text-muted">
                        💰
                    </div>
                    <h3 class="text-sm font-bold text-charcoal">Belum Ada Rencana Pemasukan</h3>
                    <p class="text-xs text-muted max-w-md mx-auto">
                        Tambahkan perkiraan pemasukan mendatang agar proyeksi saldo kas Anda memperhitungkan penambahan dana.
                    </p>
                    <button 
                        type="button" 
                        onclick="openCreatePlannedIncomeModal()" 
                        class="btn-arus-primary text-xs h-9 px-4 inline-flex items-center gap-1.5"
                    >
                        + Tambah Rencana Pemasukan Sekarang
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-cream/40 dark:bg-line/20 text-[11px] font-bold text-muted uppercase tracking-wider">
                                <th class="py-3 px-4">Judul & Catatan</th>
                                <th class="py-3 px-4">Tgl Estimasi</th>
                                <th class="py-3 px-4">Rekening & Kategori</th>
                                <th class="py-3 px-4 text-right">Nominal Rencana</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($plannedIncomes as $plan)
                                <tr class="hover:bg-cream/30 dark:hover:bg-line/10 transition-colors">
                                    <!-- Judul & Catatan -->
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-charcoal text-xs">{{ $plan->title }}</div>
                                        @if ($plan->notes)
                                            <div class="text-[11px] text-muted line-clamp-1 mt-0.5">{{ $plan->notes }}</div>
                                        @endif
                                    </td>

                                    <!-- Tanggal Estimasi -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-medium text-charcoal">{{ $plan->expected_date?->translatedFormat('d M Y') }}</div>
                                        <div class="text-[10.5px] text-muted">{{ $plan->dueStatusLabel() }}</div>
                                    </td>

                                    <!-- Rekening & Kategori -->
                                    <td class="py-3 px-4">
                                        <div class="text-charcoal font-medium">{{ $plan->account?->name ?? 'Kas / Rekening Utama' }}</div>
                                        <div class="text-[10.5px] text-muted">{{ $plan->category?->name ?? 'Pemasukan' }}</div>
                                    </td>

                                    <!-- Nominal -->
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="font-mono font-bold text-emerald-700 dark:text-emerald-400 text-xs">
                                            Rp {{ number_format($plan->amount, 0, ',', '.') }}
                                        </div>
                                        @if ($plan->actual_amount !== null && (float)$plan->actual_amount !== (float)$plan->amount)
                                            <div class="text-[10px] text-muted font-mono">
                                                Riil: Rp {{ number_format($plan->actual_amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        @if ($plan->isReceived())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                ✓ Diterima
                                            </span>
                                        @elseif ($plan->isCancelled())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-line text-muted">
                                                Dibatalkan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                                Direncanakan
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if ($plan->isPlanned())
                                                <button 
                                                    type="button" 
                                                    class="btn-arus-primary text-[11px] h-8 px-2.5 py-1"
                                                    onclick="openRealizePlannedIncomeModal({{ json_encode([
                                                        'id' => $plan->id,
                                                        'title' => $plan->title,
                                                        'amount' => $plan->amount,
                                                        'account_id' => $plan->account_id,
                                                        'category_id' => $plan->category_id,
                                                        'expected_date' => $plan->expected_date?->toDateString(),
                                                    ]) }})"
                                                    title="Tandai telah diterima dan catat transaksi kas"
                                                >
                                                    Tandai Diterima
                                                </button>

                                                <button 
                                                    type="button" 
                                                    class="btn-arus-secondary text-[11px] h-8 px-2 py-1"
                                                    onclick="openEditPlannedIncomeModal({{ json_encode([
                                                        'id' => $plan->id,
                                                        'title' => $plan->title,
                                                        'amount' => $plan->amount,
                                                        'account_id' => $plan->account_id,
                                                        'category_id' => $plan->category_id,
                                                        'member_id' => $plan->member_id,
                                                        'expected_date' => $plan->expected_date?->toDateString(),
                                                        'notes' => $plan->notes,
                                                    ]) }})"
                                                    title="Ubah Rencana"
                                                >
                                                    Ubah
                                                </button>

                                                <form method="POST" action="{{ route('planned-incomes.cancel', $plan) }}" class="inline" onsubmit="return confirm('Batalkan rencana pemasukan ini?')">
                                                    @csrf
                                                    <button type="submit" class="btn-arus-secondary text-[11px] h-8 px-2 py-1 text-muted" title="Batalkan Rencana">
                                                        Batal
                                                    </button>
                                                </form>
                                            @elseif ($plan->isCancelled())
                                                <form method="POST" action="{{ route('planned-incomes.reopen', $plan) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn-arus-secondary text-[11px] h-8 px-2 py-1 text-forest" title="Aktifkan Kembali">
                                                        Aktifkan
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('planned-incomes.destroy', $plan) }}" class="inline" onsubmit="return confirm('Hapus permanen rencana pemasukan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-arus-secondary text-[11px] h-8 px-2 py-1 text-red-600 hover:text-red-700" title="Hapus Permanen">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- MODAL 1: TAMBAH RENCANA PEMASUKAN                             -->
    <!-- ============================================================= -->
    <div 
        id="modalCreatePlannedIncome" 
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalCreateTitle"
        onclick="if (event.target === this) closeCreatePlannedIncomeModal()"
    >
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
                <div>
                    <span class="text-[10.5px] font-bold tracking-[1.2px] text-muted uppercase block">Perencanaan Kas</span>
                    <h3 id="modalCreateTitle" class="font-serif text-xl text-forest font-normal mt-0.5">
                        Tambah Rencana Pemasukan
                    </h3>
                </div>
                <button 
                    type="button" 
                    onclick="closeCreatePlannedIncomeModal()" 
                    class="min-w-[40px] min-h-[40px] rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-base shrink-0"
                    aria-label="Tutup modal"
                >
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('planned-incomes.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="createIncomeTitle" class="field-label">Judul Rencana <span class="text-red-600">*</span></label>
                    <input 
                        type="text" 
                        name="title" 
                        id="createIncomeTitle" 
                        required 
                        placeholder="Contoh: Gaji Bulanan, Bonus Proyek, Pelunasan Piutang" 
                        class="field-input text-xs w-full"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="createIncomeAmount" class="field-label">Estimasi Nominal (Rp) <span class="text-red-600">*</span></label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="createIncomeAmount" 
                            required 
                            min="1" 
                            step="any"
                            placeholder="0" 
                            class="field-input text-xs w-full font-mono"
                        >
                    </div>
                    <div>
                        <label for="createIncomeDate" class="field-label">Perkiraan Tanggal Masuk <span class="text-red-600">*</span></label>
                        <input 
                            type="date" 
                            name="expected_date" 
                            id="createIncomeDate" 
                            required 
                            value="{{ now()->addDays(7)->toDateString() }}" 
                            class="field-input text-xs w-full"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="createIncomeAccount" class="field-label">Rekening Penerima (Opsional)</label>
                        <select name="account_id" id="createIncomeAccount" class="field-select text-xs w-full">
                            <option value="">Rekening Fleksibel / Belum Ditentukan</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->typeLabel() }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="createIncomeCategory" class="field-label">Kategori Pemasukan</label>
                        <select name="category_id" id="createIncomeCategory" class="field-select text-xs w-full">
                            <option value="">Tanpa Kategori Khusus</option>
                            @foreach ($incomeCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($members->isNotEmpty())
                    <div>
                        <label for="createIncomeMember" class="field-label">Anggota Terkait (Opsional)</label>
                        <select name="member_id" id="createIncomeMember" class="field-select text-xs w-full">
                            <option value="">Semua / Ruang</option>
                            @foreach ($members as $mem)
                                <option value="{{ $mem->id }}">{{ $mem->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="createIncomeNotes" class="field-label">Catatan Tambahan (Opsional)</label>
                    <textarea 
                        name="notes" 
                        id="createIncomeNotes" 
                        rows="2" 
                        placeholder="Keterangan singkat mengenai rencana pemasukan ini..." 
                        class="field-input text-xs w-full"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closeCreatePlannedIncomeModal()" 
                        class="btn-arus-secondary text-xs h-10 px-4"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="btn-arus-primary text-xs h-10 px-5"
                    >
                        Simpan Rencana
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL 2: UBAH RENCANA PEMASUKAN                               -->
    <!-- ============================================================= -->
    <div 
        id="modalEditPlannedIncome" 
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalEditTitle"
        onclick="if (event.target === this) closeEditPlannedIncomeModal()"
    >
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
                <div>
                    <span class="text-[10.5px] font-bold tracking-[1.2px] text-muted uppercase block">Ubah Perencanaan</span>
                    <h3 id="modalEditTitle" class="font-serif text-xl text-forest font-normal mt-0.5">
                        Ubah Rencana Pemasukan
                    </h3>
                </div>
                <button 
                    type="button" 
                    onclick="closeEditPlannedIncomeModal()" 
                    class="min-w-[40px] min-h-[40px] rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-base shrink-0"
                    aria-label="Tutup modal"
                >
                    &times;
                </button>
            </div>

            <form method="POST" action="" id="formEditPlannedIncome" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="editIncomeTitle" class="field-label">Judul Rencana <span class="text-red-600">*</span></label>
                    <input 
                        type="text" 
                        name="title" 
                        id="editIncomeTitle" 
                        required 
                        class="field-input text-xs w-full"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editIncomeAmount" class="field-label">Estimasi Nominal (Rp) <span class="text-red-600">*</span></label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="editIncomeAmount" 
                            required 
                            min="1" 
                            step="any"
                            class="field-input text-xs w-full font-mono"
                        >
                    </div>
                    <div>
                        <label for="editIncomeDate" class="field-label">Perkiraan Tanggal Masuk <span class="text-red-600">*</span></label>
                        <input 
                            type="date" 
                            name="expected_date" 
                            id="editIncomeDate" 
                            required 
                            class="field-input text-xs w-full"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editIncomeAccount" class="field-label">Rekening Penerima</label>
                        <select name="account_id" id="editIncomeAccount" class="field-select text-xs w-full">
                            <option value="">Rekening Fleksibel / Belum Ditentukan</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->typeLabel() }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="editIncomeCategory" class="field-label">Kategori Pemasukan</label>
                        <select name="category_id" id="editIncomeCategory" class="field-select text-xs w-full">
                            <option value="">Tanpa Kategori Khusus</option>
                            @foreach ($incomeCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($members->isNotEmpty())
                    <div>
                        <label for="editIncomeMember" class="field-label">Anggota Terkait</label>
                        <select name="member_id" id="editIncomeMember" class="field-select text-xs w-full">
                            <option value="">Semua / Ruang</option>
                            @foreach ($members as $mem)
                                <option value="{{ $mem->id }}">{{ $mem->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="editIncomeNotes" class="field-label">Catatan Tambahan</label>
                    <textarea 
                        name="notes" 
                        id="editIncomeNotes" 
                        rows="2" 
                        class="field-input text-xs w-full"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closeEditPlannedIncomeModal()" 
                        class="btn-arus-secondary text-xs h-10 px-4"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="btn-arus-primary text-xs h-10 px-5"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL 3: REALISASIKAN RENCANA PEMASUKAN                       -->
    <!-- ============================================================= -->
    <div 
        id="modalRealizePlannedIncome" 
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalRealizeTitle"
        onclick="if (event.target === this) closeRealizePlannedIncomeModal()"
    >
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
                <div>
                    <span class="text-[10.5px] font-bold tracking-[1.2px] text-emerald-700 dark:text-emerald-400 uppercase block">Realisasi Penerimaan</span>
                    <h3 id="modalRealizeTitle" class="font-serif text-xl text-forest font-normal mt-0.5">
                        Tandai Pemasukan Diterima
                    </h3>
                    <p id="realizePlanTitleDisplay" class="text-xs text-muted mt-0.5 truncate"></p>
                </div>
                <button 
                    type="button" 
                    onclick="closeRealizePlannedIncomeModal()" 
                    class="min-w-[40px] min-h-[40px] rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-base shrink-0"
                    aria-label="Tutup modal"
                >
                    &times;
                </button>
            </div>

            <form method="POST" action="" id="formRealizePlannedIncome" class="space-y-4">
                @csrf
                
                <label class="flex items-start gap-3 p-3 rounded-xl bg-cream/60 dark:bg-line/30 border border-line cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="record_transaction" 
                        id="realizeRecordTx" 
                        value="1" 
                        checked 
                        class="field-checkbox mt-0.5"
                        onchange="toggleRealizeAccountFields(this.checked)"
                    >
                    <div class="min-w-0">
                        <span class="block text-xs font-bold text-charcoal">Catat sebagai transaksi pemasukan di buku kas</span>
                        <span class="block text-[11px] text-muted mt-0.5">
                            Saldo rekening penerima akan bertambah dan tercatat resmi pada daftar Transaksi.
                        </span>
                    </div>
                </label>

                <div>
                    <label for="realizeActualAmount" class="field-label">Nominal Riil Diterima (Rp) <span class="text-red-600">*</span></label>
                    <input 
                        type="number" 
                        name="actual_amount" 
                        id="realizeActualAmount" 
                        required 
                        min="1" 
                        step="any"
                        class="field-input text-xs w-full font-mono text-base font-bold text-emerald-700 dark:text-emerald-400"
                    >
                </div>

                <div id="realizeAccountSection" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="realizeAccount" class="field-label">Rekening Masuk <span class="text-red-600">*</span></label>
                            <select name="account_id" id="realizeAccount" class="field-select text-xs w-full">
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->typeLabel() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="realizeCategory" class="field-label">Kategori Pemasukan <span class="text-red-600">*</span></label>
                            <select name="category_id" id="realizeCategory" class="field-select text-xs w-full">
                                @foreach ($incomeCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="realizeDate" class="field-label">Tanggal Diterima</label>
                    <input 
                        type="date" 
                        name="received_date" 
                        id="realizeDate" 
                        value="{{ now()->toDateString() }}" 
                        class="field-input text-xs w-full"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closeRealizePlannedIncomeModal()" 
                        class="btn-arus-secondary text-xs h-10 px-4"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="btn-arus-primary text-xs h-10 px-5"
                    >
                        Konfirmasi Penerimaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Chart.js & Interaksi Modal -->
    <script>
        // Inisialisasi Grafik Chart.js
        document.addEventListener('DOMContentLoaded', function () {
            const chartCanvas = document.getElementById('cashflowProjectionChart');
            if (!chartCanvas) return;

            const dailyData = @json($projection['daily_points']);
            const labels = dailyData.map(d => d.label);
            const fullLabels = dailyData.map(d => d.full_label);
            const balances = dailyData.map(d => d.balance);
            const inflows = dailyData.map(d => d.inflow);
            const outflows = dailyData.map(d => d.outflow);

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
            const textColor = isDark ? '#94A3B8' : '#526070';
            const lineColor = isDark ? '#60A5FA' : '#0B4A8B';

            new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Proyeksi Saldo Kumulatif',
                            data: balances,
                            borderColor: lineColor,
                            backgroundColor: isDark ? 'rgba(59, 130, 246, 0.12)' : 'rgba(11, 74, 139, 0.08)',
                            borderWidth: 2.5,
                            pointRadius: dailyData.length > 60 ? 0 : 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: lineColor,
                            tension: 0.25,
                            fill: true,
                            yAxisID: 'yBalance',
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Arus Kas Masuk (+)',
                            data: inflows,
                            backgroundColor: 'rgba(22, 163, 74, 0.7)',
                            borderColor: '#16a34a',
                            borderWidth: 1,
                            borderRadius: 3,
                            yAxisID: 'yBar',
                            order: 2
                        },
                        {
                            type: 'bar',
                            label: 'Arus Kas Keluar (-)',
                            data: outflows,
                            backgroundColor: 'rgba(220, 38, 38, 0.7)',
                            borderColor: '#dc2626',
                            borderWidth: 1,
                            borderRadius: 3,
                            yAxisID: 'yBar',
                            order: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#1E293B' : '#0F172A',
                            titleColor: '#F8FAFC',
                            bodyColor: '#E2E8F0',
                            padding: 10,
                            boxPadding: 4,
                            callbacks: {
                                title: function(context) {
                                    const index = context[0].dataIndex;
                                    return fullLabels[index] || labels[index];
                                },
                                label: function(context) {
                                    const val = context.raw || 0;
                                    const formatted = 'Rp ' + Math.round(val).toLocaleString('id-ID');
                                    return context.dataset.label + ': ' + formatted;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                font: { size: 10.5 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 12
                            }
                        },
                        yBalance: {
                            type: 'linear',
                            position: 'left',
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                font: { size: 10.5 },
                                callback: function(value) {
                                    if (Math.abs(value) >= 1000000) {
                                        return (value / 1000000).toFixed(1) + ' Jt';
                                    } else if (Math.abs(value) >= 1000) {
                                        return (value / 1000).toFixed(0) + ' Rb';
                                    }
                                    return value;
                                }
                            }
                        },
                        yBar: {
                            type: 'linear',
                            position: 'right',
                            display: false,
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });

        // Modal Handlers
        function openCreatePlannedIncomeModal() {
            document.getElementById('modalCreatePlannedIncome').classList.remove('hidden');
            setTimeout(() => document.getElementById('createIncomeTitle')?.focus(), 50);
        }

        function closeCreatePlannedIncomeModal() {
            document.getElementById('modalCreatePlannedIncome').classList.add('hidden');
        }

        function openEditPlannedIncomeModal(plan) {
            const form = document.getElementById('formEditPlannedIncome');
            form.action = '/planned-incomes/' + plan.id;
            document.getElementById('editIncomeTitle').value = plan.title || '';
            document.getElementById('editIncomeAmount').value = plan.amount || '';
            document.getElementById('editIncomeDate').value = plan.expected_date || '';
            document.getElementById('editIncomeAccount').value = plan.account_id || '';
            document.getElementById('editIncomeCategory').value = plan.category_id || '';
            if (document.getElementById('editIncomeMember')) {
                document.getElementById('editIncomeMember').value = plan.member_id || '';
            }
            document.getElementById('editIncomeNotes').value = plan.notes || '';

            document.getElementById('modalEditPlannedIncome').classList.remove('hidden');
            setTimeout(() => document.getElementById('editIncomeTitle')?.focus(), 50);
        }

        function closeEditPlannedIncomeModal() {
            document.getElementById('modalEditPlannedIncome').classList.add('hidden');
        }

        function openRealizePlannedIncomeModal(plan) {
            const form = document.getElementById('formRealizePlannedIncome');
            form.action = '/planned-incomes/' + plan.id + '/realize';
            document.getElementById('realizePlanTitleDisplay').textContent = plan.title;
            document.getElementById('realizeActualAmount').value = plan.amount || '';
            if (plan.account_id) {
                document.getElementById('realizeAccount').value = plan.account_id;
            }
            if (plan.category_id) {
                document.getElementById('realizeCategory').value = plan.category_id;
            }

            document.getElementById('modalRealizePlannedIncome').classList.remove('hidden');
            setTimeout(() => document.getElementById('realizeActualAmount')?.focus(), 50);
        }

        function closeRealizePlannedIncomeModal() {
            document.getElementById('modalRealizePlannedIncome').classList.add('hidden');
        }

        function toggleRealizeAccountFields(checked) {
            const sec = document.getElementById('realizeAccountSection');
            if (checked) {
                sec.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                sec.classList.add('opacity-50', 'pointer-events-none');
            }
        }

        // Keyboard Escape Key Handler
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeCreatePlannedIncomeModal();
                closeEditPlannedIncomeModal();
                closeRealizePlannedIncomeModal();
            }
        });
    </script>
</x-layouts.app>
