<x-layouts.app title="Laporan Keuangan & Arus Kas">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / LAPORAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Laporan Keuangan & Arus Kas
            </h1>
            <p class="text-xs text-muted">
                Data mentah arus kas harian, mutasi masuk-keluar, dan analisis sebaran pada periode <strong>{{ $periodLabel }}</strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'reports-summary\']')" 
                class="privacy-card-toggle inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal Ringkasan Laporan"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Ringkasan</span>
            </button>

            <!-- Filter Periode Laporan (Bulan & Tahun) -->
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                @if (request()->filled('account_id'))
                    <input type="hidden" name="account_id" value="{{ request('account_id') }}">
                @endif
                @if (request()->filled('sort_order'))
                    <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">
                @endif

                <select name="month" class="field-select text-xs h-11 sm:h-10 py-1.5 min-w-[120px] flex-1 sm:flex-initial" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>

                <select name="year" class="field-select text-xs h-11 sm:h-10 py-1.5 min-w-[90px] w-28 sm:w-auto" onchange="this.form.submit()">
                    @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    <!-- Tab Navigasi Utama Laporan -->
    <div class="flex items-center gap-2 mb-6 border-b border-line overflow-x-auto whitespace-nowrap">
        <a 
            href="{{ route('reports.index', array_merge(request()->query(), ['tab' => 'daily'])) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 {{ $activeTab === 'daily' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <span>Arus Kas Harian (Raw Data)</span>
            @if ($activeDaysCount > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $activeTab === 'daily' ? 'bg-forest/15 text-forest' : 'bg-cream text-muted dark:bg-line/50' }}">
                    {{ $activeDaysCount }} hari
                </span>
            @endif
        </a>

        <a 
            href="{{ route('reports.index', array_merge(request()->query(), ['tab' => 'analytics'])) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 {{ $activeTab === 'analytics' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
            </svg>
            <span>Ringkasan & Analisis</span>
        </a>
    </div>

    @if ($activeTab === 'daily')
        <!-- ========================================== -->
        <!-- TAB 1: ARUS KAS HARIAN (RAW DATA)          -->
        <!-- ========================================== -->

        <!-- KPI Summary Bar Kas Periode -->
        <div data-privacy-scope="reports-summary" class="privacy-scope grid grid-cols-1 min-[420px]:grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 gap-3.5 mb-6">
            <!-- Saldo Awal -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Saldo Kas Awal</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-charcoal">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($startingCashBalance, 0, ',', '.') }}">
                            Rp {{ number_format($startingCashBalance, 0, ',', '.') }}
                        </span>
                    </div>
                    <span class="text-[11px] text-muted">Per awal periode</span>
                </div>
            </div>

            <!-- Kas Masuk (Inflow) -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-forest">Total Kas Masuk</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-forest">
                        <span class="privacy-balance" data-raw-value="+Rp {{ number_format($totalPeriodInflow, 0, ',', '.') }}">
                            +Rp {{ number_format($totalPeriodInflow, 0, ',', '.') }}
                        </span>
                    </div>
                    <span class="text-[11px] text-muted">{{ $incomeCount }} transaksi masuk</span>
                </div>
            </div>

            <!-- Kas Keluar (Outflow) -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-red dark:text-rose-400">Total Kas Keluar</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-red dark:text-rose-400">
                        <span class="privacy-balance" data-raw-value="-Rp {{ number_format($totalPeriodOutflow, 0, ',', '.') }}">
                            -Rp {{ number_format($totalPeriodOutflow, 0, ',', '.') }}
                        </span>
                    </div>
                    <span class="text-[11px] text-muted">{{ $expenseCount }} transaksi keluar</span>
                </div>
            </div>

            <!-- Arus Kas Bersih (Net) -->
            <div class="panel-card p-4 flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-charcoal">Net Arus Kas</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold {{ $totalPeriodNet >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }}">
                        <span class="privacy-balance" data-raw-value="{{ $totalPeriodNet >= 0 ? '+' : '' }}Rp {{ number_format($totalPeriodNet, 0, ',', '.') }}">
                            {{ $totalPeriodNet >= 0 ? '+' : '' }}Rp {{ number_format($totalPeriodNet, 0, ',', '.') }}
                        </span>
                    </div>
                    <span class="text-[11px] text-muted">{{ $totalPeriodNet >= 0 ? 'Surplus kas' : 'Defisit kas' }}</span>
                </div>
            </div>

            <!-- Saldo Akhir -->
            <div class="panel-card p-4 flex flex-col justify-between min-[420px]:col-span-2 sm:col-span-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Saldo Kas Akhir</span>
                <div class="mt-2">
                    <div class="text-base sm:text-lg font-mono font-bold text-forest">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($closingCashBalance, 0, ',', '.') }}">
                            Rp {{ number_format($closingCashBalance, 0, ',', '.') }}
                        </span>
                    </div>
                    <span class="text-[11px] text-muted">Saldo berjalan akhir</span>
                </div>
            </div>
        </div>

        <!-- Filter & Toolbar Raw Data -->
        <div class="panel-card p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <input type="hidden" name="tab" value="daily">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">

                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Filter Rekening</label>
                        <select name="account_id" class="field-select h-9 py-1 text-xs min-w-[180px]" onchange="this.form.submit()">
                            <option value="">Semua Rekening Kas</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ (string)$selectedAccountId === (string)$acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }} ({{ strtoupper($acc->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($viewMode === 'list')
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-muted mb-1">Urutan Hari</label>
                            <select name="sort_order" class="field-select h-9 py-1 text-xs min-w-[160px]" onchange="this.form.submit()">
                                <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>
                                    ⬇️ Terbaru ke Terlama
                                </option>
                                <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>
                                    ⬆️ Terlama ke Terbaru (Kronologis)
                                </option>
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2 self-stretch sm:self-auto pt-2 sm:pt-0 flex-wrap">
                    <div class="inline-flex rounded-lg border border-line bg-panel p-0.5 shadow-2xs">
                        <a 
                            href="{{ route('reports.index', array_merge(request()->query(), ['tab' => 'daily', 'view' => 'list'])) }}" 
                            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-md text-xs font-semibold transition {{ $viewMode === 'list' ? 'bg-cream dark:bg-line/60 text-charcoal font-bold shadow-2xs' : 'text-muted hover:text-charcoal' }}"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <span>Daftar</span>
                        </a>
                        <a 
                            href="{{ route('reports.index', array_merge(request()->query(), ['tab' => 'daily', 'view' => 'calendar'])) }}" 
                            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-md text-xs font-semibold transition {{ $viewMode === 'calendar' ? 'bg-cream dark:bg-line/60 text-charcoal font-bold shadow-2xs' : 'text-muted hover:text-charcoal' }}"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            <span>Kalender</span>
                        </a>
                    </div>

                    <a 
                        href="{{ route('reports.export-csv', array_merge(request()->query(), ['month' => $month, 'year' => $year, 'account_id' => $selectedAccountId, 'sort_order' => $sortOrder])) }}" 
                        class="btn-arus-secondary min-h-[44px] sm:min-h-[36px] sm:h-9 px-3.5 text-xs font-bold inline-flex items-center gap-1.5"
                        title="Unduh data mentah arus kas harian format CSV untuk Excel"
                    >
                        <svg class="w-4 h-4 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span>Export CSV</span>
                    </a>
                </div>
            </form>
        </div>

        @if ($viewMode === 'list')
        @if (empty($dailyCashflows))
            <div class="panel-card p-12 text-center space-y-3">
                <span class="text-4xl block">📊</span>
                <div class="font-bold text-base text-charcoal">Belum Ada Transaksi Arus Kas</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Tidak ditemukan pencatatan mutasi kas (pemasukan/pengeluaran) pada periode {{ $periodLabel }}.
                </p>
            </div>
        @else
            <div class="space-y-4 mb-6">
                @foreach ($dailyCashflows as $day)
                    @php
                        $dayCarbon = $day->carbon;
                        $spaceNow = $space ? $space->now() : now();
                        $isToday = $day->date === $spaceNow->toDateString();
                        $isYesterday = $day->date === $spaceNow->copy()->subDay()->toDateString();
                        $isFuture = $day->date > $spaceNow->toDateString();
                    @endphp

                    <div class="panel-card p-0 overflow-hidden border border-line shadow-2xs">
                        <!-- Bar Header Harian -->
                        <div class="px-4 sm:px-6 py-3 bg-cream/40 dark:bg-line/25 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                            <!-- Kiri: Tanggal & Label Relatif -->
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-forest/10 dark:bg-forest/20 text-forest flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                                    </svg>
                                </span>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-sm text-charcoal">
                                            {{ $day->day_name }}, {{ $day->date_formatted }}
                                        </span>

                                        @if ($isFuture)
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30">
                                                Mendatang
                                            </span>
                                        @elseif ($isToday)
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                                                Hari Ini
                                            </span>
                                        @elseif ($isYesterday)
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-cream dark:bg-line/50 text-muted">
                                                Kemarin
                                            </span>
                                        @endif

                                        <span class="text-xs text-muted font-mono font-medium">
                                            ({{ $day->count }} transaksi)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Kanan: Subtotal Arus Kas Hari Tersebut & Saldo Berjalan -->
                            <div class="flex items-center gap-3 sm:gap-4 flex-wrap text-xs">
                                @if ($day->inflow > 0)
                                    <div class="flex items-center gap-1 font-mono font-bold text-forest">
                                        <span class="text-[10px] font-sans font-medium text-muted">Masuk:</span>
                                        <span class="privacy-balance" data-raw-value="+Rp {{ number_format($day->inflow, 0, ',', '.') }}">+Rp {{ number_format($day->inflow, 0, ',', '.') }}</span>
                                    </div>
                                @endif

                                @if ($day->outflow > 0)
                                    <div class="flex items-center gap-1 font-mono font-bold text-red dark:text-rose-400">
                                        <span class="text-[10px] font-sans font-medium text-muted">Keluar:</span>
                                        <span class="privacy-balance" data-raw-value="-Rp {{ number_format($day->outflow, 0, ',', '.') }}">-Rp {{ number_format($day->outflow, 0, ',', '.') }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center gap-1 font-mono font-bold {{ $day->net >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }}">
                                    <span class="text-[10px] font-sans font-medium text-muted">Net:</span>
                                    <span class="privacy-balance" data-raw-value="{{ $day->net >= 0 ? '+' : '' }}Rp {{ number_format($day->net, 0, ',', '.') }}">{{ $day->net >= 0 ? '+' : '' }}Rp {{ number_format($day->net, 0, ',', '.') }}</span>
                                </div>

                                <div class="px-2 py-1 sm:py-0.5 rounded bg-panel dark:bg-line/40 border border-line text-[11px] font-mono text-charcoal flex items-center gap-1.5">
                                    <span class="text-muted">Saldo Kas:</span>
                                    <span class="font-bold privacy-balance" data-raw-value="Rp {{ number_format($day->closing_balance, 0, ',', '.') }}">Rp {{ number_format($day->closing_balance, 0, ',', '.') }}</span>
                                    <button 
                                        type="button" 
                                        onclick="toggleCardPrivacy(this)" 
                                        class="privacy-card-toggle w-11 h-11 sm:w-5 sm:h-5 -my-2 -mr-1.5 sm:my-0 sm:mr-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10px] shrink-0" 
                                        title="Sembunyikan / Tampilkan Nominal Hari Ini"
                                        aria-label="Sembunyikan atau tampilkan nominal hari ini"
                                    >
                                        <span class="privacy-toggle-icon"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="md:hidden divide-y divide-line">
                            @foreach ($day->items as $item)
                                @php
                                    $tx = $item->transaction;
                                    $src = $tx->sourceAccount();
                                    $dst = $tx->destinationAccount();
                                    $isInc = $tx->isIncome();
                                    $isExp = $tx->isExpense();
                                    $isTrans = $tx->isTransfer();
                                @endphp
                                <div class="p-3.5 space-y-1.5 text-xs">
                                    <div class="flex items-start justify-between gap-2.5">
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('transactions.show', $tx) }}" class="font-bold text-charcoal hover:text-forest hover:underline block truncate">
                                                {{ $tx->description }}
                                            </a>
                                            <div class="text-[11px] text-muted flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5">
                                                <span class="font-mono">{{ $tx->formattedTime() ?: '-' }}</span>
                                                <span>&bull;</span>
                                                <span class="font-mono">
                                                    @if ($isTrans)
                                                        {{ $src?->name }} &rarr; {{ $dst?->name }}
                                                    @elseif ($isExp)
                                                        {{ $src?->name ?? 'Kas' }}
                                                    @else
                                                        {{ $dst?->name ?? 'Kas' }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right font-mono font-bold shrink-0 {{ $isInc ? 'text-forest' : ($isExp ? 'text-red dark:text-rose-400' : 'text-charcoal') }}">
                                            <span class="privacy-balance" data-raw-value="{{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($tx->totalAmount(), 0, ',', '.') }}">
                                                {{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($tx->totalAmount(), 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center flex-wrap gap-1.5 text-[10px]">
                                        @if ($isInc)
                                            <span class="px-1.5 py-0.5 rounded font-bold bg-forest/10 dark:bg-forest/20 text-forest">MASUK</span>
                                        @elseif ($isExp)
                                            <span class="px-1.5 py-0.5 rounded font-bold bg-red/10 dark:bg-red/20 text-red dark:text-rose-400">KELUAR</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded font-bold bg-cream dark:bg-line/50 text-muted">TRANSFER</span>
                                        @endif
                                        @if ($tx->category)
                                            <span class="px-2 py-0.5 rounded font-medium bg-cream dark:bg-line/40 text-charcoal">{{ $tx->category->name }}</span>
                                        @endif
                                        @if ($tx->member)
                                            <span class="px-1.5 py-0.5 rounded-full font-bold text-white" style="background-color: {{ $tx->member->color ?: '#0f4c3a' }};">{{ $tx->member->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Item-item Transaksi Mentah Harian -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line bg-cream/20 text-muted uppercase font-bold text-[10px] tracking-wider">
                                        <th class="py-2 px-4 sm:px-6 w-16">Waktu</th>
                                        <th class="py-2 px-3">Keterangan</th>
                                        <th class="py-2 px-3">Kategori</th>
                                        <th class="py-2 px-3">Rekening</th>
                                        <th class="py-2 px-3">Jenis</th>
                                        <th class="py-2 px-4 sm:px-6 text-right">Arus Kas (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($day->items as $item)
                                        @php
                                            $tx = $item->transaction;
                                            $src = $tx->sourceAccount();
                                            $dst = $tx->destinationAccount();
                                            $isInc = $tx->isIncome();
                                            $isExp = $tx->isExpense();
                                            $isTrans = $tx->isTransfer();
                                        @endphp
                                        <tr class="hover:bg-cream/20 dark:hover:bg-line/15 transition-colors">
                                            <!-- Jam -->
                                            <td class="py-2.5 px-4 sm:px-6 font-mono text-muted whitespace-nowrap">
                                                {{ $tx->formattedTime() ?: '-' }}
                                            </td>

                                            <!-- Deskripsi -->
                                            <td class="py-2.5 px-3">
                                                <div class="font-bold text-charcoal">
                                                    <a href="{{ route('transactions.show', $tx) }}" class="hover:text-forest hover:underline">
                                                        {{ $tx->description }}
                                                    </a>
                                                </div>
                                                @if ($tx->member)
                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-bold text-white shadow-2xs mt-0.5" style="background-color: {{ $tx->member->color ?: '#0f4c3a' }};">
                                                        {{ $tx->member->name }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Kategori -->
                                            <td class="py-2.5 px-3 text-muted">
                                                @if ($tx->category)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-cream dark:bg-line/40 text-charcoal">
                                                        {{ $tx->category->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted/60">-</span>
                                                @endif
                                            </td>

                                            <!-- Rekening -->
                                            <td class="py-2.5 px-3 whitespace-nowrap text-muted font-mono text-[11px]">
                                                @if ($isTrans)
                                                    <span>{{ $src?->name }} &rarr; {{ $dst?->name }}</span>
                                                @elseif ($isExp)
                                                    <span>{{ $src?->name ?? 'Kas' }}</span>
                                                @else
                                                    <span>{{ $dst?->name ?? 'Kas' }}</span>
                                                @endif
                                            </td>

                                            <!-- Tipe -->
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                @if ($isInc)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-forest/10 dark:bg-forest/20 text-forest">
                                                        MASUK
                                                    </span>
                                                @elseif ($isExp)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red/10 dark:bg-red/20 text-red dark:text-rose-400">
                                                        KELUAR
                                                    </span>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-cream dark:bg-line/50 text-muted">
                                                        TRANSFER
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Nominal Arus Kas -->
                                            <td class="py-2.5 px-4 sm:px-6 text-right font-mono font-bold whitespace-nowrap {{ $isInc ? 'text-forest' : ($isExp ? 'text-red dark:text-rose-400' : 'text-charcoal') }}">
                                                <span class="privacy-balance" data-raw-value="{{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($tx->totalAmount(), 0, ',', '.') }}">
                                                    {{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($tx->totalAmount(), 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        @else
            <div class="panel-card p-0 overflow-hidden border border-line shadow-2xs mb-6">
                <div class="px-4 sm:px-6 py-3.5 bg-cream/40 dark:bg-line/25 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a 
                            href="{{ route('reports.index', array_merge(request()->query(), ['tab' => 'daily', 'view' => 'calendar', 'month' => $prevMonth, 'year' => $prevYear])) }}"
                            class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line bg-panel flex items-center justify-center text-charcoal hover:bg-cream dark:hover:bg-line/50 transition shadow-2xs cursor-pointer"
                            title="Bulan Sebelumnya"
                            aria-label="Bulan Sebelumnya"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </a>

                        <div class="text-sm sm:text-base font-bold text-charcoal px-2">
                            {{ \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
                        </div>

                        <a 
                            href="{{ route('reports.index', array_merge(request()->query(), ['tab' => 'daily', 'view' => 'calendar', 'month' => $nextMonth, 'year' => $nextYear])) }}"
                            class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line bg-panel flex items-center justify-center text-charcoal hover:bg-cream dark:hover:bg-line/50 transition shadow-2xs cursor-pointer"
                            title="Bulan Berikutnya"
                            aria-label="Bulan Berikutnya"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3 text-[11px] text-muted flex-wrap">
                        <button 
                            type="button" 
                            id="toggleBillsBtn"
                            onclick="toggleCalendarBills()" 
                            class="min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg border text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer shadow-2xs bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30"
                            title="Tampilkan atau sembunyikan tagihan & cicilan jatuh tempo"
                        >
                            <span>🔔</span>
                            <span>Tagihan & Cicilan</span>
                            <span id="billsStatusDot" class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        </button>

                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-forest"></span>
                            <span>Masuk (+)</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red dark:bg-rose-400"></span>
                            <span>Keluar (-)</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-charcoal dark:bg-white"></span>
                            <span>Arus Kas Bersih (Net)</span>
                        </span>
                    </div>
                </div>

                <!-- Kalender Ringkas Mobile (Tanpa Horizontal Scroll) -->
                <div class="md:hidden">
                    <div class="grid grid-cols-7 border-b border-line bg-cream/30 dark:bg-line/20 text-center text-[11px] font-bold text-muted py-2">
                        <div>Sen</div>
                        <div>Sel</div>
                        <div>Rab</div>
                        <div>Kam</div>
                        <div>Jum</div>
                        <div class="text-red/80 dark:text-rose-400/80">Sab</div>
                        <div class="text-red/80 dark:text-rose-400/80">Min</div>
                    </div>
                    <div class="divide-y divide-line">
                        @foreach ($calendarWeeks as $week)
                            <div class="grid grid-cols-7 divide-x divide-line">
                                @foreach ($week as $day)
                                    <button
                                        type="button"
                                        @if ($day->is_current_month) onclick="openCalendarDayModal('{{ $day->date }}')" @else disabled @endif
                                        class="min-h-[54px] p-1 flex flex-col items-center justify-between transition-colors {{ $day->is_current_month ? 'bg-panel cursor-pointer active:bg-cream' : 'bg-cream/20 dark:bg-line/10 opacity-35 cursor-default' }} {{ $day->is_today ? 'ring-2 ring-inset ring-forest bg-forest/5' : '' }}"
                                    >
                                        <span class="inline-flex items-center justify-center text-xs font-bold {{ $day->is_today ? 'w-5 h-5 rounded-full bg-forest text-cream text-[11px]' : ($day->is_weekend ? 'text-red/90 dark:text-rose-400' : 'text-charcoal') }}">
                                            {{ $day->day_number }}
                                        </span>
                                        <div class="flex items-center justify-center gap-1 min-h-[10px]">
                                            @if ($day->inflow > 0)
                                                <span class="w-1.5 h-1.5 rounded-full bg-forest" title="Kas masuk"></span>
                                            @endif
                                            @if ($day->outflow > 0)
                                                <span class="w-1.5 h-1.5 rounded-full bg-red" title="Kas keluar"></span>
                                            @endif
                                            @if ($day->bills_count > 0)
                                                <span class="calendar-bill-indicator w-1.5 h-1.5 rounded-full bg-amber-500" title="Tagihan jatuh tempo"></span>
                                            @endif
                                        </div>
                                        <span class="text-[9px] font-mono text-muted leading-none">
                                            {{ $day->count > 0 ? $day->count . ' tx' : '' }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    <div class="p-3 bg-sidebar/40 border-t border-line text-[11px] text-muted text-center">
                        Ketuk tanggal untuk melihat rincian transaksi dan tagihan.
                    </div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <div class="min-w-[680px]">
                        <div class="grid grid-cols-7 calendar-grid-7 border-b border-line bg-cream/30 dark:bg-line/20 text-center text-xs font-bold text-muted py-2" style="display: grid !important; grid-template-columns: repeat(7, minmax(0, 1fr)) !important;">
                            <div class="py-1">Sen</div>
                            <div class="py-1">Sel</div>
                            <div class="py-1">Rab</div>
                            <div class="py-1">Kam</div>
                            <div class="py-1">Jum</div>
                            <div class="py-1 text-red/80 dark:text-rose-400/80">Sab</div>
                            <div class="py-1 text-red/80 dark:text-rose-400/80">Min</div>
                        </div>

                        <div class="divide-y divide-line">
                            @foreach ($calendarWeeks as $week)
                                <div class="grid grid-cols-7 calendar-grid-7 divide-x divide-line" style="display: grid !important; grid-template-columns: repeat(7, minmax(0, 1fr)) !important;">
                                    @foreach ($week as $day)
                                        <div 
                                            @if ($day->is_current_month) onclick="openCalendarDayModal('{{ $day->date }}')" @endif
                                            class="calendar-day-cell p-2 flex flex-col justify-between transition-colors relative select-none {{ $day->is_current_month ? 'bg-panel cursor-pointer hover:bg-cream/50 dark:hover:bg-line/30' : 'bg-cream/20 dark:bg-line/10 opacity-40 cursor-default' }} {{ $day->is_today ? 'ring-2 ring-forest bg-forest/5 z-1' : '' }}"
                                        >
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="inline-flex items-center justify-center text-xs font-bold {{ $day->is_today ? 'w-5 h-5 rounded-full bg-forest text-cream text-[11px]' : ($day->is_weekend ? 'text-red/90 dark:text-rose-400' : 'text-charcoal') }}">
                                                    {{ $day->day_number }}
                                                </span>
                                                @if ($day->count > 0)
                                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-cream dark:bg-line text-muted">
                                                        {{ $day->count }} tx
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="my-auto py-1">
                                                @if ($day->has_transactions)
                                                    <div class="space-y-0.5 text-[11px] font-mono leading-tight">
                                                        @if ($day->inflow > 0)
                                                            <div class="text-forest font-semibold truncate flex items-center justify-between" title="Masuk: Rp {{ number_format($day->inflow, 0, ',', '.') }}">
                                                                <span class="text-[9px] font-sans font-normal opacity-70">In</span>
                                                                <span class="privacy-balance" data-raw-value="+Rp {{ number_format($day->inflow, 0, ',', '.') }}">+{{ number_format($day->inflow, 0, ',', '.') }}</span>
                                                            </div>
                                                        @endif
                                                        @if ($day->outflow > 0)
                                                            <div class="text-red dark:text-rose-400 font-semibold truncate flex items-center justify-between" title="Keluar: Rp {{ number_format($day->outflow, 0, ',', '.') }}">
                                                                <span class="text-[9px] font-sans font-normal opacity-70">Out</span>
                                                                <span class="privacy-balance" data-raw-value="-Rp {{ number_format($day->outflow, 0, ',', '.') }}">-{{ number_format($day->outflow, 0, ',', '.') }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="pt-0.5 border-t border-line/60 flex items-center justify-between font-bold text-[10px] {{ $day->net >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }}" title="Net: {{ $day->net >= 0 ? '+' : '-' }}Rp {{ number_format(abs($day->net), 0, ',', '.') }}">
                                                            <span class="text-[9px] font-sans font-normal opacity-70">Net</span>
                                                            <span class="privacy-balance" data-raw-value="{{ $day->net >= 0 ? '+' : '-' }}Rp {{ number_format(abs($day->net), 0, ',', '.') }}">{{ $day->net >= 0 ? '+' : '-' }}{{ number_format(abs($day->net), 0, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($day->bills_count > 0)
                                                    <div class="calendar-bill-indicator mt-1 px-1 py-0.5 rounded text-[9px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 truncate flex items-center justify-between" title="{{ $day->bills_count }} Tagihan & Cicilan (Rp {{ number_format($day->bills_total_amount, 0, ',', '.') }})">
                                                        <span class="truncate">🔔 {{ $day->bills_count }} Tagihan</span>
                                                        <span class="font-mono text-[8px] shrink-0 ml-0.5">{{ $day->bills_pending_count > 0 ? $day->bills_pending_count . ' due' : '✓' }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div id="calDayData-{{ $day->date }}" class="hidden" 
                                                data-date="{{ $day->date }}" 
                                                data-formatted-date="{{ $day->carbon->translatedFormat('l, d F Y') }}" 
                                                data-inflow="{{ $day->inflow }}" 
                                                data-outflow="{{ $day->outflow }}" 
                                                data-net="{{ $day->net }}" 
                                                data-count="{{ $day->count }}" 
                                                data-create-url="{{ route('transactions.create', ['date' => $day->date]) }}"
                                                data-items="{{ json_encode($day->modal_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                                data-bills="{{ json_encode($day->bills, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div id="calendarDayModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-charcoal/60 backdrop-blur-xs transition-opacity" onclick="closeCalendarDayModal()"></div>

                <div class="flex min-h-full items-start sm:items-center justify-center p-2.5 sm:p-4 text-center">
                    <div class="relative transform overflow-hidden rounded-2xl bg-panel border border-line text-left shadow-xl transition-all my-2 sm:my-8 sm:w-full sm:max-w-2xl w-full max-h-[calc(100dvh-1rem)] sm:max-h-[90dvh] flex flex-col">
                        <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-line flex items-center justify-between gap-2 bg-cream/30 dark:bg-line/20 shrink-0">
                            <div class="min-w-0">
                                <h3 id="calModalDateTitle" class="text-base font-bold text-charcoal"></h3>
                                <p id="calModalSubTitle" class="text-xs text-muted mt-0.5"></p>
                            </div>
                            <button type="button" onclick="closeCalendarDayModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream dark:hover:bg-line/50 transition shrink-0" aria-label="Tutup modal">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 sm:p-5 space-y-4 sm:space-y-5 overflow-y-auto flex-1">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
                                <div class="p-3 rounded-xl border border-line bg-cream/30 dark:bg-line/20 flex items-center justify-between sm:block">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted block sm:mb-1">Kas Masuk</span>
                                    <div id="calModalInflow" class="text-sm sm:text-base font-mono font-bold text-forest privacy-balance">Rp 0</div>
                                </div>
                                <div class="p-3 rounded-xl border border-line bg-cream/30 dark:bg-line/20 flex items-center justify-between sm:block">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted block sm:mb-1">Kas Keluar</span>
                                    <div id="calModalOutflow" class="text-sm sm:text-base font-mono font-bold text-red dark:text-rose-400 privacy-balance">Rp 0</div>
                                </div>
                                <div class="p-3 rounded-xl border border-line bg-cream/30 dark:bg-line/20 flex items-center justify-between sm:block">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted block sm:mb-1">Arus Kas Bersih</span>
                                    <div id="calModalNet" class="text-sm sm:text-base font-mono font-bold privacy-balance">Rp 0</div>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-charcoal">Daftar Transaksi</h4>
                                    <span id="calModalTxCountBadge" class="text-[11px] font-mono text-muted"></span>
                                </div>

                                <div id="calModalEmptyState" class="hidden p-8 text-center border border-dashed border-line rounded-xl">
                                    <p class="text-xs text-muted">Tidak ada transaksi yang tercatat pada tanggal ini.</p>
                                </div>

                                <div id="calModalMobileList" class="sm:hidden space-y-2"></div>

                                <div id="calModalTableWrapper" class="hidden sm:block overflow-x-auto max-h-[340px] overflow-y-auto border border-line rounded-xl">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-cream/40 dark:bg-line/30 border-b border-line text-[11px] font-bold text-muted uppercase sticky top-0 backdrop-blur-xs">
                                            <tr>
                                                <th class="py-2.5 px-3">Jam</th>
                                                <th class="py-2.5 px-3">Deskripsi & Kategori</th>
                                                <th class="py-2.5 px-3">Rekening</th>
                                                <th class="py-2.5 px-3 text-right">Nominal</th>
                                                <th class="py-2.5 px-3 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="calModalTbody" class="divide-y divide-line font-medium text-charcoal">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="calModalBillsSection" class="border-t border-line pt-4 hidden">
                                <div class="flex items-center justify-between mb-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs">🔔</span>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-charcoal">Kewajiban Tagihan & Cicilan</h4>
                                    </div>
                                    <span id="calModalBillsCountBadge" class="text-[11px] font-mono text-amber-600 dark:text-amber-400 font-semibold"></span>
                                </div>

                                <div id="calModalBillsList" class="space-y-2 max-h-[220px] overflow-y-auto">
                                </div>
                            </div>
                        </div>

                        <div class="px-4 sm:px-5 py-3.5 border-t border-line bg-cream/20 dark:bg-line/10 flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-2.5 shrink-0">
                            <a id="calModalCreateTxBtn" href="#" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[36px] text-xs px-3.5 font-bold inline-flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>Catat Transaksi di Tanggal Ini</span>
                            </a>
                            <button type="button" onclick="closeCalendarDayModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[36px] text-xs px-4 font-semibold">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function openCalendarDayModal(dateStr) {
                    const dataEl = document.getElementById('calDayData-' + dateStr);
                    if (!dataEl) return;

                    const formattedDate = dataEl.dataset.formattedDate || dateStr;
                    const inflow = parseFloat(dataEl.dataset.inflow || '0');
                    const outflow = parseFloat(dataEl.dataset.outflow || '0');
                    const net = parseFloat(dataEl.dataset.net || '0');
                    const count = parseInt(dataEl.dataset.count || '0', 10);
                    const createUrl = dataEl.dataset.createUrl || '#';
                    const items = JSON.parse(dataEl.dataset.items || '[]');

                    document.getElementById('calModalDateTitle').textContent = formattedDate;
                    document.getElementById('calModalSubTitle').textContent = count > 0 ? (count + ' transaksi tercatat') : 'Tidak ada mutasi';
                    
                    const inflowEl = document.getElementById('calModalInflow');
                    const inflowFormatted = 'Rp ' + Number(inflow).toLocaleString('id-ID');
                    inflowEl.setAttribute('data-raw-value', inflowFormatted);
                    inflowEl.textContent = inflowFormatted;

                    const outflowEl = document.getElementById('calModalOutflow');
                    const outflowFormatted = 'Rp ' + Number(outflow).toLocaleString('id-ID');
                    outflowEl.setAttribute('data-raw-value', outflowFormatted);
                    outflowEl.textContent = outflowFormatted;

                    const netEl = document.getElementById('calModalNet');
                    const netFormatted = (net >= 0 ? '+Rp ' : '-Rp ') + Number(Math.abs(net)).toLocaleString('id-ID');
                    netEl.setAttribute('data-raw-value', netFormatted);
                    netEl.textContent = netFormatted;
                    if (net >= 0) {
                        netEl.className = 'text-sm sm:text-base font-mono font-bold text-forest privacy-balance';
                    } else {
                        netEl.className = 'text-sm sm:text-base font-mono font-bold text-red dark:text-rose-400 privacy-balance';
                    }

                    document.getElementById('calModalTxCountBadge').textContent = count + ' transaksi';
                    document.getElementById('calModalCreateTxBtn').href = createUrl;

                    const tbody = document.getElementById('calModalTbody');
                    const mobileList = document.getElementById('calModalMobileList');
                    const emptyState = document.getElementById('calModalEmptyState');
                    const tableWrapper = document.getElementById('calModalTableWrapper');
                    tbody.innerHTML = '';
                    if (mobileList) {
                        mobileList.innerHTML = '';
                    }

                    if (items.length === 0) {
                        emptyState.classList.remove('hidden');
                        tableWrapper.classList.add('hidden');
                        mobileList?.classList.add('hidden');
                    } else {
                        emptyState.classList.add('hidden');
                        tableWrapper.classList.remove('hidden');
                        mobileList?.classList.remove('hidden');

                        items.forEach(function(item) {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-cream/30 dark:hover:bg-line/20 transition-colors';

                            let typeBadge = '';
                            let amountClass = 'text-charcoal';
                            let amountPrefix = '';

                            if (item.type === 'income') {
                                typeBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-forest/10 dark:bg-forest/20 text-forest">MASUK</span>';
                                amountClass = 'text-forest';
                                amountPrefix = '+Rp ';
                            } else if (item.type === 'expense') {
                                typeBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red/10 dark:bg-red/20 text-red dark:text-rose-400">KELUAR</span>';
                                amountClass = 'text-red dark:text-rose-400';
                                amountPrefix = '-Rp ';
                            } else {
                                typeBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-cream dark:bg-line/50 text-muted">TRANSFER</span>';
                            }

                            let accountStr = '-';
                            if (item.type === 'transfer') {
                                accountStr = (item.source_account || '-') + ' &rarr; ' + (item.dest_account || '-');
                            } else if (item.type === 'expense') {
                                accountStr = item.source_account || 'Kas';
                            } else {
                                accountStr = item.dest_account || 'Kas';
                            }

                            const nominalVal = item.type === 'income' ? item.inflow : (item.type === 'expense' ? item.outflow : Math.max(item.inflow, item.outflow));
                            const formattedNominal = amountPrefix + Number(nominalVal).toLocaleString('id-ID');

                            tr.innerHTML = `
                                <td class="py-2.5 px-3 font-mono text-[11px] text-muted whitespace-nowrap">${item.time}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-semibold text-charcoal">${item.description}</div>
                                    <div class="text-[11px] text-muted flex items-center gap-1.5 mt-0.5">
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium bg-cream dark:bg-line/40">${item.category_name}</span>
                                        ${typeBadge}
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-muted whitespace-nowrap">${accountStr}</td>
                                <td class="py-2.5 px-3 font-mono font-bold text-right whitespace-nowrap ${amountClass}">
                                    <span class="privacy-balance" data-raw-value="${formattedNominal}">${formattedNominal}</span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <a href="${item.url}" class="text-[11px] font-semibold text-forest hover:underline inline-flex items-center gap-0.5">
                                        Detail &rarr;
                                    </a>
                                </td>
                            `;
                            tbody.appendChild(tr);

                            if (mobileList) {
                                const card = document.createElement('div');
                                card.className = 'p-3 rounded-xl border border-line bg-sidebar/30 space-y-1.5 text-xs';
                                card.innerHTML = `
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-charcoal truncate">${item.description}</div>
                                            <div class="text-[11px] text-muted mt-0.5 flex flex-wrap items-center gap-1.5">
                                                <span class="font-mono">${item.time}</span>
                                                <span>&bull;</span>
                                                <span class="font-mono">${accountStr}</span>
                                            </div>
                                        </div>
                                        <div class="font-mono font-bold shrink-0 ${amountClass}">
                                            <span class="privacy-balance" data-raw-value="${formattedNominal}">${formattedNominal}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 pt-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-cream dark:bg-line/40 text-charcoal">${item.category_name}</span>
                                            ${typeBadge}
                                        </div>
                                        <a href="${item.url}" class="min-h-[36px] px-2 text-[11px] font-bold text-forest hover:underline inline-flex items-center">
                                            Detail &rarr;
                                        </a>
                                    </div>
                                `;
                                mobileList.appendChild(card);
                            }
                        });
                    }

                    const bills = JSON.parse(dataEl.dataset.bills || '[]');
                    const billsSection = document.getElementById('calModalBillsSection');
                    const billsList = document.getElementById('calModalBillsList');
                    const billsBadge = document.getElementById('calModalBillsCountBadge');

                    if (bills && bills.length > 0) {
                        billsSection.classList.remove('hidden');
                        billsBadge.textContent = bills.length + ' kewajiban';
                        billsList.innerHTML = '';

                        bills.forEach(function(bill) {
                            const itemEl = document.createElement('div');
                            itemEl.className = 'p-3 rounded-xl border border-line bg-cream/20 dark:bg-line/20 flex items-center justify-between gap-3 text-xs';

                            const isPaid = bill.status === 'paid';
                            const badgeColor = isPaid
                                ? 'bg-forest/10 dark:bg-forest/20 text-forest'
                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
                            const icon = bill.kind === 'installment' ? '💳' : '🔔';
                            const kindLabel = bill.kind_label || (bill.kind === 'installment' ? 'Kredit & Cicilan' : 'Jadwal Rutin');
                            const kindBadge = bill.kind === 'installment'
                                ? `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 shrink-0">${kindLabel}</span>`
                                : `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 shrink-0">${kindLabel}</span>`;

                            itemEl.innerHTML = `
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-base shrink-0">${icon}</span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold text-charcoal truncate">${bill.title}</span>
                                            ${kindBadge}
                                        </div>
                                        <div class="text-[11px] text-muted truncate mt-0.5">${bill.subtitle}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="font-mono font-bold text-charcoal">
                                            <span class="privacy-balance" data-raw-value="Rp ${Number(bill.amount).toLocaleString('id-ID')}">Rp ${Number(bill.amount).toLocaleString('id-ID')}</span>
                                        </div>
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold ${badgeColor}">
                                            ${bill.status_label}
                                        </span>
                                    </div>
                                    <a href="${bill.url}" class="px-2.5 py-1.5 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-[11px] font-semibold text-charcoal transition">
                                        Lihat &rarr;
                                    </a>
                                </div>
                            `;
                            billsList.appendChild(itemEl);
                        });
                    } else {
                        billsSection.classList.add('hidden');
                        billsList.innerHTML = '';
                    }

                    const modal = document.getElementById('calendarDayModal');
                    if (typeof window.syncPrivacyContainer === 'function') {
                        window.syncPrivacyContainer(modal);
                    }
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeCalendarDayModal() {
                    const modal = document.getElementById('calendarDayModal');
                    if (modal) {
                        modal.classList.add('hidden');
                    }
                    document.body.classList.remove('overflow-hidden');
                }

                let calendarBillsVisible = true;
                function toggleCalendarBills() {
                    calendarBillsVisible = !calendarBillsVisible;
                    const badges = document.querySelectorAll('.calendar-bill-indicator');
                    badges.forEach(function(el) {
                        el.style.display = calendarBillsVisible ? '' : 'none';
                    });

                    const btn = document.getElementById('toggleBillsBtn');
                    const dot = document.getElementById('billsStatusDot');
                    if (btn && dot) {
                        if (calendarBillsVisible) {
                            btn.className = 'min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg border text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer shadow-2xs bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30';
                            dot.className = 'w-1.5 h-1.5 rounded-full bg-amber-500';
                        } else {
                            btn.className = 'min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg border text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer shadow-2xs bg-panel text-muted border-line hover:text-charcoal';
                            dot.className = 'w-1.5 h-1.5 rounded-full bg-muted/40';
                        }
                    }
                }

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeCalendarDayModal();
                    }
                });
            </script>
        @endif

    @else
        <!-- ========================================== -->
        <!-- TAB 2: RINGKASAN & ANALISIS TREN           -->
        <!-- ========================================== -->

        <!-- Ringkasan Arus Kas Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-metric-card 
                label="PEMASUKAN BERSIH" 
                :value="'Rp ' . number_format($totalIncome, 0, ',', '.')" 
                :dark="true" 
                :sub="$incomeCount . ' transaksi kas masuk'" 
            />

            <x-metric-card 
                label="PENGELUARAN BERSIH" 
                :value="'Rp ' . number_format($totalExpense, 0, ',', '.')" 
                :sub="$expenseCount . ' transaksi kas keluar'" 
            />

            <x-metric-card 
                label="ARUS KAS BERSIH (NET)" 
                :value="($netCashFlow >= 0 ? '+' : '') . 'Rp ' . number_format($netCashFlow, 0, ',', '.')" 
                :sub="$netCashFlow >= 0 ? 'Surplus kas periode ini' : 'Defisit kas periode ini'" 
            />

            <x-metric-card 
                label="TOTAL AKTIVITAS MUTASI" 
                :value="($incomeCount + $expenseCount) . ' Transaksi'" 
                sub="Pencatatan posted aktual" 
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Grafik & Tabel Sebaran Pengeluaran per Kategori -->
            <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                        <span class="text-sm font-bold text-forest">Sebaran Pengeluaran per Kategori</span>
                        <span class="text-xs text-muted">{{ $categoryBreakdown->count() }} kategori</span>
                    </div>

                    @if ($categoryBreakdown->isEmpty())
                        <div class="p-8 text-center text-xs text-muted">
                            Belum ada transaksi pengeluaran pada periode {{ $periodLabel }}.
                        </div>
                    @else
                        <div class="p-5">
                            <!-- Canvas Chart.js -->
                            <div class="max-w-[260px] mx-auto mb-6">
                                <canvas id="categoryChart" width="260" height="260"></canvas>
                            </div>

                            <!-- Mobile Category Breakdown List -->
                            <div class="sm:hidden divide-y divide-line border-t border-line">
                                @foreach ($categoryBreakdown as $cat)
                                    <div class="py-2.5 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-charcoal truncate">{{ $cat->name }}</div>
                                            <div class="text-[11px] text-muted">{{ $cat->count }} transaksi</div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-mono font-bold text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($cat->amount, 0, ',', '.') }}">Rp {{ number_format($cat->amount, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="text-[11px] font-bold text-forest">{{ $cat->percentage }}%</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Accessible Alternative Table -->
                            <div class="hidden sm:block overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                            <th class="py-2.5 px-3">Kategori</th>
                                            <th class="py-2.5 px-3 text-right">Jumlah Trx</th>
                                            <th class="py-2.5 px-3 text-right">Nominal</th>
                                            <th class="py-2.5 px-3 text-right">Porsi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        @foreach ($categoryBreakdown as $cat)
                                            <tr class="hover:bg-cream/20 transition-colors">
                                                <td class="py-2.5 px-3 font-semibold text-charcoal">
                                                    {{ $cat->name }}
                                                </td>
                                                <td class="py-2.5 px-3 text-right text-muted">
                                                    {{ $cat->count }}
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-charcoal">
                                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($cat->amount, 0, ',', '.') }}">Rp {{ number_format($cat->amount, 0, ',', '.') }}</span>
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-bold text-forest">
                                                    {{ $cat->percentage }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-cream/20 border-t border-line text-[11px] text-muted text-center">
                    Mutasi transfer antar-rekening dan transaksi dibatalkan (void) otomatis dikecualikan.
                </div>
            </div>

            <!-- Grafik & Tabel Tren Arus Kas 6 Bulan Terakhir -->
            <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                        <span class="text-sm font-bold text-forest">Tren Arus Kas (6 Bulan Terakhir)</span>
                        <span class="text-xs text-muted">Pemasukan vs Pengeluaran</span>
                    </div>

                    <div class="p-5">
                        <!-- Canvas Chart.js -->
                        <div class="h-56 mb-6">
                            <canvas id="trendChart"></canvas>
                        </div>

                        <!-- Mobile Trend List -->
                        <div class="sm:hidden divide-y divide-line border-t border-line">
                            @foreach ($monthlyTrend as $m)
                                <div class="py-2.5 space-y-1 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-charcoal">{{ $m->label }}</span>
                                        <span class="font-mono font-bold {{ $m->net >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }}">
                                            <span class="privacy-balance" data-raw-value="{{ $m->net >= 0 ? '+' : '' }}Rp {{ number_format($m->net, 0, ',', '.') }}">{{ $m->net >= 0 ? '+' : '' }}Rp {{ number_format($m->net, 0, ',', '.') }}</span>
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-mono">
                                        <span class="text-forest">+<span class="privacy-balance" data-raw-value="Rp {{ number_format($m->income, 0, ',', '.') }}">Rp {{ number_format($m->income, 0, ',', '.') }}</span></span>
                                        <span class="text-red dark:text-rose-400">-<span class="privacy-balance" data-raw-value="Rp {{ number_format($m->expense, 0, ',', '.') }}">Rp {{ number_format($m->expense, 0, ',', '.') }}</span></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Accessible Alternative Table -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                        <th class="py-2.5 px-3">Periode</th>
                                        <th class="py-2.5 px-3 text-right text-forest">Pemasukan</th>
                                        <th class="py-2.5 px-3 text-right text-red dark:text-rose-400">Pengeluaran</th>
                                        <th class="py-2.5 px-3 text-right">Net Cash Flow</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($monthlyTrend as $m)
                                        <tr class="hover:bg-cream/20 transition-colors">
                                            <td class="py-2.5 px-3 font-semibold text-charcoal">
                                                {{ $m->label }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-semibold text-forest">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($m->income, 0, ',', '.') }}">Rp {{ number_format($m->income, 0, ',', '.') }}</span>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-semibold text-red dark:text-rose-400">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($m->expense, 0, ',', '.') }}">Rp {{ number_format($m->expense, 0, ',', '.') }}</span>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold {{ $m->net >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }}">
                                                <span class="privacy-balance" data-raw-value="{{ $m->net >= 0 ? '+' : '' }}Rp {{ number_format($m->net, 0, ',', '.') }}">{{ $m->net >= 0 ? '+' : '' }}Rp {{ number_format($m->net, 0, ',', '.') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-cream/20 border-t border-line text-[11px] text-muted text-center">
                    Angka riil berdasarkan tanggal transaksi pada zona waktu ruang aktif ({{ $space?->timezone }}).
                </div>
            </div>
        </div>

        <!-- Script Chart.js Loaded Only on Analytics Tab -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var isDark = document.documentElement.classList.contains('dark');
                var textColor = isDark ? '#BAC8BC' : '#4A5B52';
                var gridColor = isDark ? 'rgba(52, 72, 61, 0.4)' : 'rgba(226, 232, 227, 0.6)';

                @if ($categoryBreakdown->isNotEmpty())
                    var catLabels = @json($categoryBreakdown->pluck('name'));
                    var catData = @json($categoryBreakdown->pluck('amount'));
                    var catColors = [
                        '#24483B', '#3E6B5A', '#5A8E7A', '#7BAE99', '#9CCDB8',
                        '#BFDEC3', '#D6E8D8', '#C75C5C', '#D88A8A', '#E8BABA'
                    ];

                    var ctxCat = document.getElementById('categoryChart');
                    if (ctxCat) {
                        new Chart(ctxCat, {
                            type: 'doughnut',
                            data: {
                                labels: catLabels,
                                datasets: [{
                                    data: catData,
                                    backgroundColor: catColors.slice(0, catLabels.length),
                                    borderWidth: 1,
                                    borderColor: isDark ? '#1C2A24' : '#FFFFFF'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                var val = context.raw || 0;
                                                return ' Rp ' + val.toLocaleString('id-ID');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif

                var trendLabels = @json(collect($monthlyTrend)->pluck('label'));
                var trendIncome = @json(collect($monthlyTrend)->pluck('income'));
                var trendExpense = @json(collect($monthlyTrend)->pluck('expense'));

                var ctxTrend = document.getElementById('trendChart');
                if (ctxTrend) {
                    new Chart(ctxTrend, {
                        type: 'bar',
                        data: {
                            labels: trendLabels,
                            datasets: [
                                {
                                    label: 'Pemasukan',
                                    data: trendIncome,
                                    backgroundColor: '#24483B',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Pengeluaran',
                                    data: trendExpense,
                                    backgroundColor: '#C75C5C',
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    ticks: { color: textColor, font: { size: 10 } },
                                    grid: { display: false }
                                },
                                y: {
                                    ticks: { 
                                        color: textColor, 
                                        font: { size: 10 },
                                        callback: function(value) {
                                            if (value >= 1000000) return (value / 1000000) + ' jt';
                                            if (value >= 1000) return (value / 1000) + ' rb';
                                            return value;
                                        }
                                    },
                                    grid: { color: gridColor }
                                }
                            },
                            plugins: {
                                legend: {
                                    labels: { color: textColor, font: { size: 11 } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            var val = context.raw || 0;
                                            return context.dataset.label + ': Rp ' + val.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endif
</x-layouts.app>
