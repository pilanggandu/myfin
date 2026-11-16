<x-layouts.app title="Analisis Langganan & Tagihan Rutin">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / RECURRING / ANALISIS</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Analisis Langganan
            </h1>
            <p class="text-xs text-muted">
                Kelompokkan pembayaran berulang, pantau total beban langganan bulanan &amp; tahunan, deteksi kenaikan biaya, dan jadwalkan pembayaran berikutnya di ruang <strong>{{ $space?->name }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'subscriptions-metrics\']')" 
                class="privacy-card-toggle inline-flex items-center gap-1.5 px-3 py-2 min-h-[44px] sm:min-h-[38px] rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-xs transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Ringkasan</span>
            </button>

            <a href="{{ route('recurring.index') }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs font-bold px-3.5 py-2 inline-flex items-center justify-center gap-1.5 shadow-xs">
                <span>&larr; Kelola Semua Jadwal</span>
            </a>

            <a href="{{ route('recurring.create') }}" class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs font-bold px-4 py-2 inline-flex items-center justify-center gap-1.5 shadow-xs">
                <span>+ Tambah Langganan</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan Metrik Langganan (Executive Cards) -->
    <div data-privacy-scope="subscriptions-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="TOTAL BIAYA LANGGANAN" 
            :value="'Rp ' . number_format($analytics['summary']['total_monthly_cost'], 0, ',', '.') . ' /bln'" 
            :dark="true" 
            :sub="'Ekuivalen Rp ' . number_format($analytics['summary']['total_annual_cost'], 0, ',', '.') . ' /tahun'" 
        />

        <x-metric-card 
            label="LAYANAN BERLANGGANAN" 
            :value="$analytics['summary']['active_subscriptions_count'] . ' Layanan Aktif'" 
            :sub="$analytics['by_frequency']['monthly']['count'] . ' bulanan, ' . $analytics['by_frequency']['yearly']['count'] . ' tahunan, ' . $analytics['by_frequency']['weekly']['count'] . ' mingguan'" 
        />

        <x-metric-card 
            label="KENAIKAN BIAYA TERDETEKSI" 
            :value="$analytics['summary']['price_hike_count'] . ' Layanan Naik'" 
            :sub="$analytics['summary']['price_hike_count'] > 0 ? '+Rp ' . number_format($analytics['summary']['total_monthly_increase'], 0, ',', '.') . ' /bln (+Rp ' . number_format($analytics['summary']['total_annual_increase'], 0, ',', '.') . '/thn)' : 'Belum ada kenaikan harga terdeteksi'" 
        />

        <x-metric-card 
            label="TAGIHAN 30 HARI MENDATANG" 
            :value="'Rp ' . number_format($analytics['summary']['upcoming_30_days_total'], 0, ',', '.') . ''" 
            :sub="$analytics['summary']['upcoming_overdue_count'] > 0 ? $analytics['summary']['upcoming_overdue_count'] . ' jadwal terlambat' : '7 hari ke depan: Rp ' . number_format($analytics['summary']['upcoming_7_days_total'], 0, ',', '.')" 
        />
    </div>

    <!-- Peringatan Kenaikan Biaya (Price Hike Alert Banner) -->
    @if ($analytics['summary']['price_hike_count'] > 0)
        <div class="panel-card p-5 rounded-2xl border border-amber-500/30 bg-amber-500/10 dark:bg-amber-500/5 mb-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="text-2xl flex-shrink-0">⚠️</span>
                    <div>
                        <h2 class="font-bold text-sm text-amber-950 dark:text-amber-200">
                            Perhatian: Terdeteksi Kenaikan Biaya pada {{ $analytics['summary']['price_hike_count'] }} Layanan
                        </h2>
                        <p class="text-xs text-amber-900/90 dark:text-amber-300/90 mt-0.5">
                            Pengeluaran rutin bertambah <strong>+Rp {{ number_format($analytics['summary']['total_monthly_increase'], 0, ',', '.') }}/bulan</strong> (atau <strong>+Rp {{ number_format($analytics['summary']['total_annual_increase'], 0, ',', '.') }}/tahun</strong>) dibanding tarif sebelumnya.
                        </p>
                    </div>
                </div>

                <a href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'price_hikes'])) }}" class="btn-arus-secondary text-xs font-bold py-1.5 px-3 whitespace-nowrap self-start sm:self-auto">
                    Lihat Rincian Kenaikan &rarr;
                </a>
            </div>

            <!-- List Cepat Kenaikan Biaya -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                @foreach ($analytics['price_hikes'] as $hike)
                    <div class="p-3.5 rounded-xl bg-panel border border-amber-500/30 flex items-center justify-between gap-3 shadow-2xs">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs">{{ $hike['category']['icon'] }}</span>
                                <h3 class="font-bold text-xs text-charcoal truncate" title="{{ $hike['name'] }}">
                                    {{ $hike['name'] }}
                                </h3>
                            </div>
                            <div class="text-[11px] text-muted mt-1">
                                <span class="line-through">Rp {{ number_format($hike['previous_amount'], 0, ',', '.') }}</span>
                                <span class="text-charcoal font-semibold mx-1">&rarr;</span>
                                <strong class="text-amber-800 dark:text-amber-300 font-mono">Rp {{ number_format($hike['current_amount'], 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-900 dark:text-amber-200 block">
                                +Rp {{ number_format($hike['price_difference'], 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-muted mt-0.5 block">+{{ $hike['percentage_increase'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Toolbar Filter & Pencarian -->
    <div class="panel-card p-4 rounded-2xl border border-line bg-panel mb-6 shadow-xs">
        <form method="GET" action="{{ route('recurring.subscriptions') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <!-- Search Field -->
            <div class="flex-1 min-w-0">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted text-xs">
                        🔍
                    </span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}" 
                        placeholder="Cari langganan (e.g. Netflix, Spotify, iCloud, Indihome)..." 
                        class="field-input h-10 pl-8 pr-3 text-xs w-full"
                    />
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Filter Status -->
                <select name="status" onchange="this.form.submit()" class="field-select text-xs h-10 px-3 w-full sm:w-auto">
                    <option value="active" {{ ($filters['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Aktif Saja</option>
                    <option value="all" {{ ($filters['status'] ?? '') === 'all' ? 'selected' : '' }}>Semua (Aktif &amp; Dijeda)</option>
                    <option value="paused" {{ ($filters['status'] ?? '') === 'paused' ? 'selected' : '' }}>Dijeda</option>
                </select>

                <!-- Filter Frekuensi -->
                <select name="frequency" onchange="this.form.submit()" class="field-select text-xs h-10 px-3 w-full sm:w-auto">
                    <option value="">Semua Frekuensi</option>
                    <option value="monthly" {{ ($filters['frequency'] ?? '') === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    <option value="yearly" {{ ($filters['frequency'] ?? '') === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                    <option value="weekly" {{ ($filters['frequency'] ?? '') === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                </select>

                <!-- Filter Kategori -->
                @if ($categories->isNotEmpty())
                    <select name="category_id" onchange="this.form.submit()" class="field-select text-xs h-10 px-3 w-full sm:w-auto">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? null) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->icon }} {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Filter Rekening -->
                @if ($accounts->isNotEmpty())
                    <select name="source_account_id" onchange="this.form.submit()" class="field-select text-xs h-10 px-3 w-full sm:w-auto">
                        <option value="">Semua Rekening</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ ($filters['source_account_id'] ?? null) == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Checkbox Hanya Kenaikan Biaya -->
                <label class="flex items-center gap-1.5 text-xs text-charcoal font-semibold cursor-pointer px-2 py-2 rounded-lg border border-line bg-sidebar/50">
                    <input 
                        type="checkbox" 
                        name="has_price_hike" 
                        value="1" 
                        {{ ! empty($filters['has_price_hike']) ? 'checked' : '' }} 
                        onchange="this.form.submit()" 
                        class="rounded border-line text-forest focus:ring-forest"
                    />
                    <span>Hanya Kenaikan Biaya</span>
                </label>

                @if (! empty($filters['search']) || ! empty($filters['frequency']) || ! empty($filters['category_id']) || ! empty($filters['source_account_id']) || ! empty($filters['has_price_hike']) || ($filters['status'] ?? 'active') !== 'active')
                    <a href="{{ route('recurring.subscriptions', ['tab' => $tab]) }}" class="text-xs text-muted hover:text-charcoal underline px-1">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tab Navigasi Pengelompokan & Jadwal -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-1 text-xs font-bold border-b border-line">
        <a 
            href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'category'])) }}" 
            class="whitespace-nowrap px-4 py-2.5 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'category' ? 'border-forest text-forest bg-panel/50 rounded-t-lg' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <span>🗂️ Berdasarkan Kategori</span>
            <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-cream dark:bg-line/40 text-muted">
                {{ count($analytics['by_category']) }}
            </span>
        </a>

        <a 
            href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'timeline'])) }}" 
            class="whitespace-nowrap px-4 py-2.5 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'timeline' ? 'border-forest text-forest bg-panel/50 rounded-t-lg' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <span>📅 Jadwal Berikutnya</span>
            @if ($analytics['summary']['upcoming_overdue_count'] > 0)
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-rose-500/20 text-rose-700 dark:text-rose-300">
                    {{ $analytics['summary']['upcoming_overdue_count'] }} Terlambat
                </span>
            @elseif ($analytics['summary']['upcoming_due_today_count'] > 0)
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-800 dark:text-amber-300">
                    {{ $analytics['summary']['upcoming_due_today_count'] }} Hari Ini
                </span>
            @endif
        </a>

        <a 
            href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'price_hikes'])) }}" 
            class="whitespace-nowrap px-4 py-2.5 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'price_hikes' ? 'border-forest text-forest bg-panel/50 rounded-t-lg' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <span>📈 Riwayat Kenaikan Biaya</span>
            @if ($analytics['summary']['price_hike_count'] > 0)
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-800 dark:text-amber-300">
                    {{ $analytics['summary']['price_hike_count'] }}
                </span>
            @endif
        </a>

        <a 
            href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'frequency'])) }}" 
            class="whitespace-nowrap px-4 py-2.5 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'frequency' ? 'border-forest text-forest bg-panel/50 rounded-t-lg' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <span>🔁 Frekuensi Pembayaran</span>
        </a>

        <a 
            href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'account'])) }}" 
            class="whitespace-nowrap px-4 py-2.5 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'account' ? 'border-forest text-forest bg-panel/50 rounded-t-lg' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            <span>💳 Metode Bayar / Rekening</span>
        </a>

        @if ($space && ! $space->isPersonal())
            <a 
                href="{{ route('recurring.subscriptions', array_merge(request()->query(), ['tab' => 'member'])) }}" 
                class="whitespace-nowrap px-4 py-2.5 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'member' ? 'border-forest text-forest bg-panel/50 rounded-t-lg' : 'border-transparent text-muted hover:text-charcoal' }}"
            >
                <span>👥 Anggota Penanggung Jawab</span>
            </a>
        @endif
    </div>

    <!-- TAB CONTENT -->

    @if ($analytics['subscriptions']->isEmpty())
        <div class="panel-card p-12 text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-full bg-cream dark:bg-line/30 flex items-center justify-center text-3xl">
                📺
            </div>
            <div class="font-bold text-base text-charcoal">Tidak Ada Data Langganan</div>
            <p class="text-xs text-muted max-w-md mx-auto">
                Tidak ditemukan jadwal pengeluaran rutin atau pembayaran berulang yang cocok dengan filter aktif saat ini.
            </p>
            <div class="pt-2">
                <a href="{{ route('recurring.create') }}" class="btn-arus-primary inline-flex text-xs">
                    + Tambah Jadwal Langganan Pertama
                </a>
            </div>
        </div>

    @elseif ($tab === 'category')
        <!-- 1. Tampilan Pengelompokan Berdasarkan Kategori -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @foreach ($analytics['by_category'] as $catGroup)
                    <div class="panel-card p-5 rounded-2xl border border-line bg-panel flex flex-col justify-between shadow-xs">
                        <div>
                            <!-- Header Kategori -->
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-line">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background-color: {{ $catGroup['category_color'] }}15; border: 1px solid {{ $catGroup['category_color'] }}30;">
                                        {{ $catGroup['category_icon'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-sm text-charcoal truncate">
                                            {{ $catGroup['category_name'] }}
                                        </h3>
                                        <div class="text-[11px] text-muted">
                                            {{ $catGroup['count'] }} layanan berlangganan
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <div class="font-mono text-base font-bold text-forest">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($catGroup['monthly_cost'], 0, ',', '.') }}">
                                            Rp {{ number_format($catGroup['monthly_cost'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] text-muted font-normal font-sans">/bln</span>
                                    </div>
                                    <div class="text-[10px] text-muted">
                                        {{ $catGroup['share_percentage'] }}% dari total beban
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Share Bar -->
                            <div class="w-full h-1.5 rounded-full bg-cream dark:bg-line/60 overflow-hidden my-3">
                                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $catGroup['share_percentage'] }}%; background-color: {{ $catGroup['category_color'] }};"></div>
                            </div>

                            <!-- Daftar Layanan di Bawah Kategori Ini -->
                            <div class="divide-y divide-line/60">
                                @foreach ($catGroup['items'] as $item)
                                    <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <a href="{{ route('recurring.show', $item['id']) }}" class="font-bold text-charcoal hover:text-forest truncate">
                                                    {{ $item['name'] }}
                                                </a>
                                                @if ($item['has_price_increase'])
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/20 text-amber-800 dark:text-amber-300" title="Terjadi kenaikan harga">
                                                        Naik +{{ $item['percentage_increase'] }}%
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-muted flex items-center gap-2 mt-0.5">
                                                <span>{{ $item['frequency_label'] }}</span>
                                                <span>&bull;</span>
                                                <span>{{ $item['account']['name'] ?? 'Rekening -' }}</span>
                                                <span>&bull;</span>
                                                <span class="{{ $item['due_status'] === 'overdue' ? 'text-rose-500 font-bold' : ($item['due_status'] === 'due_today' ? 'text-amber-500 font-bold' : '') }}">
                                                    Jatuh tempo: {{ $item['next_due_date'] ? $item['next_due_date']->translatedFormat('d M') : '-' }} ({{ $item['due_status_label'] }})
                                                </span>
                                            </div>
                                        </div>

                                        <div class="text-right flex-shrink-0">
                                            <div class="font-mono font-bold text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}">
                                                    Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                            @if ($item['frequency'] !== 'monthly')
                                                <div class="text-[10px] text-muted font-mono">
                                                    ~Rp {{ number_format($item['normalized_monthly_cost'], 0, ',', '.') }}/bln
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Footer Kategori -->
                        <div class="pt-3 mt-3 border-t border-line/60 flex items-center justify-between text-[11px] text-muted">
                            <span>Total Tahunan:</span>
                            <span class="font-mono font-bold text-charcoal">
                                Rp {{ number_format($catGroup['annual_cost'], 0, ',', '.') }} /tahun
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @elseif ($tab === 'timeline')
        <!-- 2. Tampilan Jadwal Pembayaran Berikutnya (Upcoming Horizons) -->
        <div class="space-y-6">
            @php
                $buckets = [
                    'overdue' => ['badge' => 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30', 'icon' => '⚠️'],
                    'due_today' => ['badge' => 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30', 'icon' => '🔔'],
                    'this_week' => ['badge' => 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30', 'icon' => '📅'],
                    'this_month' => ['badge' => 'bg-forest/15 text-forest border-forest/30', 'icon' => '🗓️'],
                    'later' => ['badge' => 'bg-muted/15 text-muted border-muted/30', 'icon' => '⏳'],
                ];
            @endphp

            @foreach ($analytics['upcoming_timeline'] as $key => $bucket)
                @if ($bucket['count'] > 0)
                    <div class="panel-card p-5 rounded-2xl border border-line bg-panel shadow-xs space-y-4">
                        <div class="flex items-center justify-between gap-2 pb-3 border-b border-line">
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ $buckets[$key]['icon'] }}</span>
                                <h3 class="font-bold text-sm text-charcoal">
                                    {{ $bucket['label'] }}
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $buckets[$key]['badge'] }} border">
                                    {{ $bucket['count'] }} tagihan
                                </span>
                            </div>

                            <div class="font-mono text-sm font-bold text-forest">
                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($bucket['total_amount'], 0, ',', '.') }}">
                                    Rp {{ number_format($bucket['total_amount'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-line/60">
                            @foreach ($bucket['items'] as $item)
                                <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0" style="background-color: {{ $item['category']['color'] }}15;">
                                            {{ $item['category']['icon'] }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <a href="{{ route('recurring.show', $item['id']) }}" class="font-bold text-sm text-charcoal hover:text-forest truncate">
                                                    {{ $item['name'] }}
                                                </a>
                                                @if ($item['has_price_increase'])
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/20 text-amber-800 dark:text-amber-300">
                                                        Naik +Rp {{ number_format($item['price_difference'], 0, ',', '.') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-muted flex items-center gap-2 mt-0.5 flex-wrap">
                                                <span>{{ $item['category']['name'] }}</span>
                                                <span>&bull;</span>
                                                <span>{{ $item['frequency_label'] }}</span>
                                                @if ($item['account'])
                                                    <span>&bull;</span>
                                                    <span>💳 {{ $item['account']['name'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 flex-shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-line/40">
                                        <div class="text-left sm:text-right">
                                            <div class="font-mono font-bold text-sm text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}">
                                                    Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="text-[10px] {{ $item['due_status'] === 'overdue' ? 'text-rose-500 font-bold' : ($item['due_status'] === 'due_today' ? 'text-amber-500 font-bold' : 'text-muted') }}">
                                                {{ $item['next_due_date'] ? $item['next_due_date']->translatedFormat('d M Y') : '-' }} ({{ $item['due_status_label'] }})
                                            </div>
                                        </div>

                                        <a href="{{ route('recurring.show', $item['id']) }}" class="btn-arus-secondary min-h-[36px] px-3 text-xs font-bold whitespace-nowrap">
                                            Catat / Detail &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

    @elseif ($tab === 'price_hikes')
        <!-- 3. Tampilan Riwayat Kenaikan Biaya (Price Hike History) -->
        <div class="space-y-6">
            @if (empty($analytics['price_hikes']))
                <div class="panel-card p-10 text-center space-y-2">
                    <div class="text-3xl">🎉</div>
                    <div class="font-bold text-sm text-charcoal">Tidak Ada Kenaikan Biaya Terdeteksi</div>
                    <p class="text-xs text-muted max-w-md mx-auto">
                        Seluruh tarif langganan Anda saat ini stabil dan belum mengalami lonjakan harga dari pencatatan sebelumnya.
                    </p>
                </div>
            @else
                <div class="panel-card p-5 rounded-2xl border border-line bg-panel shadow-xs space-y-4">
                    <div class="flex items-center justify-between gap-3 pb-3 border-b border-line">
                        <div>
                            <h3 class="font-bold text-base text-forest">Daftar Langganan dengan Kenaikan Biaya</h3>
                            <p class="text-xs text-muted">Perbandingan tarif awal/sebelumnya terhadap tarif terkini.</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-900 dark:text-amber-200">
                            {{ count($analytics['price_hikes']) }} Layanan
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-line text-[10px] uppercase font-bold text-muted">
                                    <th class="py-2.5 px-3">Layanan Langganan</th>
                                    <th class="py-2.5 px-3">Frekuensi</th>
                                    <th class="py-2.5 px-3 text-right">Tarif Awal / Lalu</th>
                                    <th class="py-2.5 px-3 text-right">Tarif Sekarang</th>
                                    <th class="py-2.5 px-3 text-right">Kenaikan</th>
                                    <th class="py-2.5 px-3 text-right">Dampak / Tahun</th>
                                    <th class="py-2.5 px-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/60">
                                @foreach ($analytics['price_hikes'] as $hike)
                                    <tr class="hover:bg-cream/20 dark:hover:bg-line/20 transition">
                                        <td class="py-3 px-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-base">{{ $hike['category']['icon'] }}</span>
                                                <div>
                                                    <a href="{{ route('recurring.show', $hike['id']) }}" class="font-bold text-charcoal hover:text-forest">
                                                        {{ $hike['name'] }}
                                                    </a>
                                                    <div class="text-[10px] text-muted">{{ $hike['category']['name'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-sidebar text-muted">
                                                {{ $hike['frequency_label'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono text-muted line-through">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($hike['previous_amount'], 0, ',', '.') }}">
                                                Rp {{ number_format($hike['previous_amount'], 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-charcoal">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($hike['current_amount'], 0, ',', '.') }}">
                                                Rp {{ number_format($hike['current_amount'], 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-amber-700 dark:text-amber-300">
                                            +Rp {{ number_format($hike['price_difference'], 0, ',', '.') }}
                                            <span class="text-[10px] block font-sans font-normal text-amber-800/80 dark:text-amber-400">
                                                (+{{ $hike['percentage_increase'] }}%)
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                            +Rp {{ number_format($hike['annual_increase'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <a href="{{ route('recurring.edit', $hike['id']) }}" class="text-xs font-bold text-forest hover:underline">
                                                Edit Tarif
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    @elseif ($tab === 'frequency')
        <!-- 4. Tampilan Berdasarkan Frekuensi Pembayaran -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($analytics['by_frequency'] as $freqKey => $fGroup)
                    <div class="panel-card p-5 rounded-2xl border border-line bg-panel flex flex-col justify-between shadow-xs">
                        <div>
                            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line">
                                <div>
                                    <h3 class="font-bold text-base text-charcoal">{{ $fGroup['label'] }}</h3>
                                    <div class="text-[11px] text-muted">{{ $fGroup['count'] }} layanan langganan</div>
                                </div>
                                <span class="text-xl">
                                    {{ $freqKey === 'monthly' ? '📅' : ($freqKey === 'yearly' ? '📆' : '🗓️') }}
                                </span>
                            </div>

                            <div class="my-4 space-y-2">
                                <div class="text-xs text-muted">Beban Bulanan Ekuivalen:</div>
                                <div class="font-mono text-2xl font-bold text-forest">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($fGroup['normalized_monthly_cost'], 0, ',', '.') }}">
                                        Rp {{ number_format($fGroup['normalized_monthly_cost'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-xs text-muted font-normal font-sans">/bln</span>
                                </div>
                                <div class="text-[11px] text-muted">
                                    Porsi {{ $fGroup['share_percentage'] }}% dari total biaya bulanan
                                </div>
                            </div>

                            <div class="divide-y divide-line/60 pt-2 border-t border-line/60">
                                @forelse ($fGroup['items'] as $item)
                                    <div class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <span class="truncate font-semibold text-charcoal">{{ $item['name'] }}</span>
                                        <span class="font-mono font-bold text-charcoal flex-shrink-0">
                                            Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="py-3 text-center text-xs text-muted">Tidak ada layanan</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="pt-3 mt-3 border-t border-line/60 flex items-center justify-between text-xs text-muted">
                            <span>Total Tahunan:</span>
                            <span class="font-mono font-bold text-charcoal">
                                Rp {{ number_format($fGroup['annual_cost'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @elseif ($tab === 'account')
        <!-- 5. Tampilan Berdasarkan Metode Bayar / Rekening Sumber -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($analytics['by_account'] as $accGroup)
                    <div class="panel-card p-5 rounded-2xl border border-line bg-panel flex flex-col justify-between shadow-xs">
                        <div>
                            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line">
                                <div>
                                    <h3 class="font-bold text-sm text-charcoal truncate">{{ $accGroup['account_name'] }}</h3>
                                    <div class="text-[11px] text-muted capitalize">{{ $accGroup['account_type'] }} &bull; {{ $accGroup['count'] }} autodebit</div>
                                </div>
                                <span class="text-xl">💳</span>
                            </div>

                            <div class="my-3 space-y-1">
                                <div class="text-[11px] text-muted">Beban Langganan Bulanan:</div>
                                <div class="font-mono text-xl font-bold text-forest">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($accGroup['monthly_cost'], 0, ',', '.') }}">
                                        Rp {{ number_format($accGroup['monthly_cost'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-muted font-normal font-sans">/bln</span>
                                </div>
                                <div class="text-[10px] text-muted">
                                    {{ $accGroup['share_percentage'] }}% dari total langganan
                                </div>
                            </div>

                            <div class="divide-y divide-line/60 pt-2 border-t border-line/60">
                                @foreach ($accGroup['items'] as $item)
                                    <div class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <span class="font-semibold text-charcoal truncate block">{{ $item['name'] }}</span>
                                            <span class="text-[10px] text-muted">{{ $item['frequency_label'] }}</span>
                                        </div>
                                        <span class="font-mono font-bold text-charcoal flex-shrink-0">
                                            Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-3 mt-3 border-t border-line/60 flex items-center justify-between text-xs text-muted">
                            <span>Beban Tahunan:</span>
                            <span class="font-mono font-bold text-charcoal">
                                Rp {{ number_format($accGroup['annual_cost'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @elseif ($tab === 'member')
        <!-- 6. Tampilan Berdasarkan Anggota (Shared Space) -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($analytics['by_member'] as $memGroup)
                    <div class="panel-card p-5 rounded-2xl border border-line bg-panel flex flex-col justify-between shadow-xs">
                        <div>
                            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background-color: {{ $memGroup['member_color'] }};">
                                        {{ strtoupper(substr($memGroup['member_name'], 0, 1)) }}
                                    </span>
                                    <div>
                                        <h3 class="font-bold text-sm text-charcoal truncate">{{ $memGroup['member_name'] }}</h3>
                                        <div class="text-[11px] text-muted">{{ $memGroup['count'] }} layanan langganan</div>
                                    </div>
                                </div>
                            </div>

                            <div class="my-3 space-y-1">
                                <div class="text-[11px] text-muted">Total Porsi Bulanan:</div>
                                <div class="font-mono text-xl font-bold text-forest">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($memGroup['monthly_cost'], 0, ',', '.') }}">
                                        Rp {{ number_format($memGroup['monthly_cost'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-muted font-normal font-sans">/bln</span>
                                </div>
                                <div class="text-[10px] text-muted">
                                    {{ $memGroup['share_percentage'] }}% dari total beban ruang
                                </div>
                            </div>

                            <div class="divide-y divide-line/60 pt-2 border-t border-line/60">
                                @foreach ($memGroup['items'] as $item)
                                    <div class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <span class="font-semibold text-charcoal truncate block">{{ $item['name'] }}</span>
                                            <span class="text-[10px] text-muted">{{ $item['category']['name'] }} &bull; {{ $item['frequency_label'] }}</span>
                                        </div>
                                        <span class="font-mono font-bold text-charcoal flex-shrink-0">
                                            Rp {{ number_format($item['raw_amount'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
