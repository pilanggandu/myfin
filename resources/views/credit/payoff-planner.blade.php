<x-layouts.app title="Perencanaan Pelunasan Utang">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / KEWAJIBAN &amp; HUTANG</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Perencanaan Pelunasan Utang
            </h1>
            <p class="text-xs text-muted">
                Bandingkan strategi pelunasan (<strong>Avalanche vs Snowball</strong>), proyeksikan tanggal bebas utang, dan lihat dampak nyata pembayaran ekstra per bulan.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a 
                href="{{ route('credit.index') }}" 
                class="min-h-[44px] sm:min-h-[38px] inline-flex items-center justify-center gap-1.5 px-3.5 py-2 border border-line rounded-lg text-xs font-bold text-charcoal bg-panel hover:bg-cream transition cursor-pointer shadow-xs"
            >
                <span>&larr; Kembali ke Kredit &amp; Cicilan</span>
            </a>
            <a 
                href="{{ route('credit.purchases.create') }}" 
                class="btn-arus-primary min-h-[44px] sm:min-h-[38px] w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 text-xs font-bold shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Catat Belanja Cicilan</span>
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-line mb-6 overflow-x-auto whitespace-nowrap">
        <a 
            href="{{ route('credit.index', ['tab' => 'accounts']) }}" 
            class="min-h-[44px] px-4 py-3 text-xs font-bold border-b-2 transition inline-flex items-center shrink-0 border-transparent text-muted hover:text-charcoal"
        >
            Akun Kredit &amp; Kartu ({{ $creditAccountsCount }})
        </a>
        <a 
            href="{{ route('credit.index', ['tab' => 'plans']) }}" 
            class="min-h-[44px] px-4 py-3 text-xs font-bold border-b-2 transition inline-flex items-center shrink-0 border-transparent text-muted hover:text-charcoal"
        >
            Cicilan Berjalan ({{ $activePlansCount }})
        </a>
        <a 
            href="{{ route('credit.index', ['tab' => 'history']) }}" 
            class="min-h-[44px] px-4 py-3 text-xs font-bold border-b-2 transition inline-flex items-center shrink-0 border-transparent text-muted hover:text-charcoal"
        >
            Riwayat Selesai ({{ $completedPlansCount }})
        </a>
        <a 
            href="{{ route('credit.payoff.index') }}" 
            class="min-h-[44px] px-4 py-3 text-xs font-bold border-b-2 transition inline-flex items-center shrink-0 border-forest text-forest dark:border-emerald-400 dark:text-emerald-400"
        >
            <span class="mr-1">🎯</span> Rencana Pelunasan Utang
        </a>
    </div>

    @if (! $planData['has_debts'])
        <!-- Tampilan Saat Tidak Ada Utang Aktif -->
        <div class="panel-card p-12 text-center max-w-2xl mx-auto my-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl">
                🎉
            </div>
            <h3 class="font-serif text-2xl text-charcoal font-bold mb-2">Bebas dari Utang &amp; Cicilan!</h3>
            <p class="text-xs text-muted leading-relaxed mb-6">
                Tidak ada cicilan atau tagihan kartu kredit aktif di ruang ini. Seluruh kewajiban telah lunas sepenuhnya atau belum ada pembelanjaan cicilan yang tercatat.
            </p>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('credit.index') }}" class="btn-arus-secondary px-4 py-2 text-xs font-semibold">
                    Kelola Akun Kredit
                </a>
                <a href="{{ route('credit.purchases.create') }}" class="btn-arus-primary px-4 py-2 text-xs font-bold">
                    + Catat Belanja Cicilan Baru
                </a>
            </div>
        </div>
    @else
        <!-- Panel Kontrol Interaktif Dana Ekstra -->
        <div class="panel-card p-5 sm:p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex-1 space-y-4">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-forest dark:text-emerald-400 mb-1 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span>Simulasi Dana Pembayaran Tambahan (Akselerasi Pelunasan)</span>
                        </div>
                        <h2 class="font-serif text-xl text-charcoal font-bold">Berapa dana ekstra yang ingin Anda alokasikan?</h2>
                        <p class="text-xs text-muted">
                            Kewajiban minimum bulanan Anda saat ini adalah <strong>Rp {{ number_format($planData['total_monthly_min'], 0, ',', '.') }}</strong> dari total <strong>{{ $planData['debts_count'] }} pos utang</strong>.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Pembayaran Ekstra per Bulan -->
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">
                                Pembayaran Ekstra per Bulan (Rp)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-semibold text-muted">Rp</span>
                                <input 
                                    type="number" 
                                    id="extraMonthlyInput" 
                                    value="{{ (int) $extraMonthly }}" 
                                    step="50000" 
                                    min="0" 
                                    class="field-input pl-9 w-full text-xs font-bold font-mono h-10" 
                                    oninput="debouncedSimulate()"
                                >
                            </div>
                            <!-- Tombol Cepat Ekstra -->
                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                <button type="button" onclick="setExtraMonthly(0)" class="px-2 py-1 rounded text-[10px] font-semibold bg-line/50 text-muted hover:text-charcoal">
                                    Rp 0
                                </button>
                                <button type="button" onclick="setExtraMonthly(250000)" class="px-2 py-1 rounded text-[10px] font-semibold bg-forest/10 text-forest hover:bg-forest/20">
                                    +250rb
                                </button>
                                <button type="button" onclick="setExtraMonthly(500000)" class="px-2 py-1 rounded text-[10px] font-semibold bg-forest/10 text-forest hover:bg-forest/20">
                                    +500rb
                                </button>
                                <button type="button" onclick="setExtraMonthly(1000000)" class="px-2 py-1 rounded text-[10px] font-semibold bg-forest/10 text-forest hover:bg-forest/20">
                                    +1 Juta
                                </button>
                                <button type="button" onclick="setExtraMonthly(2000000)" class="px-2 py-1 rounded text-[10px] font-semibold bg-forest/10 text-forest hover:bg-forest/20">
                                    +2 Juta
                                </button>
                            </div>
                        </div>

                        <!-- Pembayaran Ekstra Sekaligus (Lump Sum) -->
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">
                                Pembayaran Sekaligus / Lump Sum (THR/Bonus)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-semibold text-muted">Rp</span>
                                <input 
                                    type="number" 
                                    id="oneTimeInput" 
                                    value="{{ (int) $oneTimePayment }}" 
                                    step="100000" 
                                    min="0" 
                                    class="field-input pl-9 w-full text-xs font-bold font-mono h-10" 
                                    oninput="debouncedSimulate()"
                                >
                            </div>
                            <p class="text-[11px] text-muted mt-2">Disuntikkan langsung di bulan ke-1 untuk memangkas pokok utang prioritas pertama.</p>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Angka Utama -->
                <div class="lg:w-72 p-4 rounded-xl bg-forest/5 dark:bg-forest/15 border border-forest/20 space-y-3 shrink-0">
                    <div class="text-xs font-bold text-forest dark:text-emerald-400">Total Komitmen Pelunasan</div>
                    <div>
                        <div class="text-[11px] text-muted">Angsuran Pokok + Ekstra:</div>
                        <div id="totalCommitmentDisplay" class="text-2xl font-serif font-bold text-charcoal mt-0.5">
                            Rp {{ number_format($planData['total_budget_avalanche'], 0, ',', '.') }}
                        </div>
                        <div class="text-[10px] text-muted">per bulan</div>
                    </div>
                    <div class="pt-2 border-t border-forest/20 text-[11px] space-y-1">
                        <div class="flex justify-between">
                            <span class="text-muted">Total Sisa Pokok:</span>
                            <strong class="text-charcoal">Rp {{ number_format($planData['total_debt_amount'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Pos Utang Aktif:</span>
                            <strong class="text-charcoal">{{ $planData['debts_count'] }} Pos</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu Perbandingan 3 Strategi Pelunasan (Side-by-side) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- 1. Strategi Avalanche -->
            <div class="panel-card p-5 sm:p-6 border-2 border-emerald-500/40 dark:border-emerald-500/60 flex flex-col justify-between relative overflow-hidden bg-gradient-to-b from-emerald-50/20 to-transparent dark:from-emerald-950/20">
                <div class="absolute top-0 right-0 bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-bl-xl shadow-xs">
                    Rekomendasi Hemat Uang
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">STRATEGI 1</div>
                        <h3 class="font-serif text-xl font-bold text-charcoal">Debt Avalanche</h3>
                        <p class="text-xs text-muted mt-1">
                            Memprioritaskan utang dengan <strong>suku bunga tertinggi</strong> lebih dulu untuk menghentikan akumulasi beban bunga secara matematis.
                        </p>
                    </div>

                    <!-- Metrik Kunci -->
                    <div class="space-y-3 p-4 rounded-xl bg-panel border border-line">
                        <div>
                            <div class="text-[11px] text-muted">Estimasi Tanggal Bebas Utang:</div>
                            <div id="avalancheDate" class="text-xl font-serif font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $planData['strategies']['avalanche']['debt_free_date'] }}
                            </div>
                            <div id="avalancheMonths" class="text-xs text-charcoal font-semibold mt-0.5">
                                Dalam {{ $planData['strategies']['avalanche']['total_months'] }} Bulan
                            </div>
                        </div>

                        <div class="pt-2 border-t border-line grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-muted text-[11px]">Bunga Dibayar:</span>
                                <div id="avalancheInterest" class="font-bold text-charcoal">
                                    Rp {{ number_format($planData['strategies']['avalanche']['total_interest_paid'], 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <span class="text-muted text-[11px]">Total Pengeluaran:</span>
                                <div id="avalancheTotal" class="font-bold text-charcoal">
                                    Rp {{ number_format($planData['strategies']['avalanche']['total_amount_paid'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Highlight Penghematan -->
                    <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs space-y-1.5">
                        <div class="font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                            <span>✨</span>
                            <span id="avalancheSavingText">Hemat Rp {{ number_format($planData['comparison']['avalanche_vs_standard']['interest_saved'], 0, ',', '.') }}</span>
                        </div>
                        <p id="avalancheTimeText" class="text-[11px] text-emerald-700 dark:text-emerald-400">
                            Lunas {{ $planData['comparison']['avalanche_vs_standard']['months_saved'] }} bulan lebih cepat dibandingkan cicilan standar reguler.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-line mt-4">
                    <button type="button" onclick="selectStrategy('avalanche')" class="btn-arus-primary w-full py-2 text-xs font-bold">
                        Pilih Strategi Avalanche
                    </button>
                </div>
            </div>

            <!-- 2. Strategi Snowball -->
            <div class="panel-card p-5 sm:p-6 border border-line flex flex-col justify-between relative bg-gradient-to-b from-blue-50/20 to-transparent dark:from-blue-950/20">
                <div class="absolute top-0 right-0 bg-blue-600 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-bl-xl shadow-xs">
                    Rekomendasi Motivasi Cepat
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="text-[11px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">STRATEGI 2</div>
                        <h3 class="font-serif text-xl font-bold text-charcoal">Debt Snowball</h3>
                        <p class="text-xs text-muted mt-1">
                            Memprioritaskan utang dengan <strong>saldo terkecil</strong> lebih dulu untuk meraih kemenangan psikologis dan mengurangi jumlah tagihan secepat mungkin.
                        </p>
                    </div>

                    <!-- Metrik Kunci -->
                    <div class="space-y-3 p-4 rounded-xl bg-panel border border-line">
                        <div>
                            <div class="text-[11px] text-muted">Estimasi Tanggal Bebas Utang:</div>
                            <div id="snowballDate" class="text-xl font-serif font-bold text-blue-600 dark:text-blue-400">
                                {{ $planData['strategies']['snowball']['debt_free_date'] }}
                            </div>
                            <div id="snowballMonths" class="text-xs text-charcoal font-semibold mt-0.5">
                                Dalam {{ $planData['strategies']['snowball']['total_months'] }} Bulan
                            </div>
                        </div>

                        <div class="pt-2 border-t border-line grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-muted text-[11px]">Bunga Dibayar:</span>
                                <div id="snowballInterest" class="font-bold text-charcoal">
                                    Rp {{ number_format($planData['strategies']['snowball']['total_interest_paid'], 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <span class="text-muted text-[11px]">Total Pengeluaran:</span>
                                <div id="snowballTotal" class="font-bold text-charcoal">
                                    Rp {{ number_format($planData['strategies']['snowball']['total_amount_paid'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Highlight Penghematan -->
                    <div class="p-3.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs space-y-1.5">
                        <div class="font-bold text-blue-800 dark:text-blue-300 flex items-center gap-1.5">
                            <span>⚡</span>
                            <span id="snowballSavingText">Hemat Rp {{ number_format($planData['comparison']['snowball_vs_standard']['interest_saved'], 0, ',', '.') }}</span>
                        </div>
                        <p id="snowballTimeText" class="text-[11px] text-blue-700 dark:text-blue-400">
                            Lunas {{ $planData['comparison']['snowball_vs_standard']['months_saved'] }} bulan lebih cepat dibandingkan cicilan standar reguler.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-line mt-4">
                    <button type="button" onclick="selectStrategy('snowball')" class="btn-arus-secondary w-full py-2 text-xs font-semibold">
                        Pilih Strategi Snowball
                    </button>
                </div>
            </div>

            <!-- 3. Jadwal Standar / Minimum -->
            <div class="panel-card p-5 sm:p-6 border border-line flex flex-col justify-between opacity-80 hover:opacity-100 transition">
                <div class="space-y-4">
                    <div>
                        <div class="text-[11px] font-bold text-muted uppercase tracking-wider">STRATEGI 3 (BASELINE)</div>
                        <h3 class="font-serif text-xl font-bold text-charcoal">Jadwal Standar</h3>
                        <p class="text-xs text-muted mt-1">
                            Membayar angsuran minimum sesuai tenor yang ada tanpa tambahan dana ekstra dan tanpa pengalihan (*rollover*).
                        </p>
                    </div>

                    <!-- Metrik Kunci -->
                    <div class="space-y-3 p-4 rounded-xl bg-sidebar/50 border border-line">
                        <div>
                            <div class="text-[11px] text-muted">Estimasi Tanggal Lunas:</div>
                            <div id="standardDate" class="text-xl font-serif font-bold text-charcoal">
                                {{ $planData['strategies']['standard']['debt_free_date'] }}
                            </div>
                            <div id="standardMonths" class="text-xs text-muted mt-0.5">
                                Dalam {{ $planData['strategies']['standard']['total_months'] }} Bulan
                            </div>
                        </div>

                        <div class="pt-2 border-t border-line grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-muted text-[11px]">Bunga Dibayar:</span>
                                <div id="standardInterest" class="font-bold text-charcoal">
                                    Rp {{ number_format($planData['strategies']['standard']['total_interest_paid'], 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <span class="text-muted text-[11px]">Total Pengeluaran:</span>
                                <div id="standardTotal" class="font-bold text-charcoal">
                                    Rp {{ number_format($planData['strategies']['standard']['total_amount_paid'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-sidebar border border-line text-xs text-muted">
                        Ini adalah skenario dasar jika Anda tidak mengalokasikan dana ekstra untuk mempercepat pelunasan utang.
                    </div>
                </div>

                <div class="pt-4 border-t border-line mt-4">
                    <button type="button" onclick="selectStrategy('standard')" class="btn-arus-secondary w-full py-2 text-xs text-muted hover:text-charcoal">
                        Lihat Detail Standar
                    </button>
                </div>
            </div>
        </div>

        <!-- Bagian Urutan Prioritas Pelunasan & Roadmap -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Roadmap Urutan Pelunasan Utang -->
            <div class="lg:col-span-2 panel-card p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-serif text-lg font-bold text-charcoal flex items-center gap-2">
                            <span>🗺️</span>
                            <span>Urutan Prioritas Pelunasan (<span id="activeStrategyTitle">Avalanche</span>)</span>
                        </h3>
                        <p class="text-xs text-muted">Setiap kali satu utang lunas, cicilannya dialihkan (*snowballed*) untuk melunasi pos berikutnya.</p>
                    </div>
                    <div class="flex items-center gap-1 bg-sidebar p-1 rounded-lg border border-line text-xs">
                        <button 
                            type="button" 
                            id="btnToggleAvalanche" 
                            onclick="selectStrategy('avalanche')" 
                            class="px-2.5 py-1 rounded-md font-bold bg-panel text-forest dark:text-emerald-400 shadow-2xs transition"
                        >
                            Avalanche
                        </button>
                        <button 
                            type="button" 
                            id="btnToggleSnowball" 
                            onclick="selectStrategy('snowball')" 
                            class="px-2.5 py-1 rounded-md font-semibold text-muted hover:text-charcoal transition"
                        >
                            Snowball
                        </button>
                    </div>
                </div>

                <div id="payoffOrderList" class="space-y-3 divide-y divide-line">
                    @foreach ($planData['strategies']['avalanche']['payoff_order'] as $idx => $order)
                        <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 font-bold text-xs flex items-center justify-center shrink-0">
                                    #{{ $idx + 1 }}
                                </span>
                                <div>
                                    <div class="font-bold text-sm text-charcoal">{{ $order['name'] }}</div>
                                    <div class="text-[11px] text-muted">Target lunas: <strong class="text-emerald-600 dark:text-emerald-400">{{ $order['date'] }}</strong> (Bulan ke-{{ $order['month'] }})</div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                Target Lunas #{{ $idx + 1 }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Panduan Strategi Edukatif -->
            <div class="space-y-4">
                <div class="panel-card p-5 space-y-3">
                    <h3 class="font-serif text-sm font-bold text-charcoal flex items-center gap-1.5">
                        <span class="text-forest dark:text-emerald-400">💡</span>
                        <span>Kapan Memilih Avalanche?</span>
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Pilih <strong>Avalanche</strong> jika tujuan utama Anda adalah meminimalkan total nominal uang yang keluar untuk membayar bunga. Secara hitungan matematika murni, strategi ini selalu menghasilkan biaya termurah.
                    </p>
                </div>

                <div class="panel-card p-5 space-y-3">
                    <h3 class="font-serif text-sm font-bold text-charcoal flex items-center gap-1.5">
                        <span class="text-blue-600 dark:text-blue-400">🎯</span>
                        <span>Kapan Memilih Snowball?</span>
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Pilih <strong>Snowball</strong> jika Anda memiliki banyak kartu/pinjaman dan membutuhkan suntikan motivasi psikologis dengan memangkas jumlah pos tagihan satu per satu secara cepat.
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabel Linimasa Amortisasi Bulan demi Bulan -->
        <div class="panel-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-line flex items-center justify-between bg-sidebar/30">
                <div>
                    <h3 class="font-serif text-base font-bold text-charcoal">Linimasa Proyeksi Pelunasan (Amortisasi Bulanan)</h3>
                    <p class="text-xs text-muted">Simulasi perkembangan sisa utang dan tonggak pencapaian lunas bulan demi bulan.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded bg-panel border border-line text-muted">
                    Menampilkan 60 Bulan Pertama
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-line">
                    <thead class="bg-sidebar/50 text-[11px] font-bold text-muted uppercase tracking-wider">
                        <tr>
                            <th class="p-3 w-16 text-center">Bulan</th>
                            <th class="p-3 w-32">Periode</th>
                            <th class="p-3 w-36">Pembayaran Pokok</th>
                            <th class="p-3 w-32">Beban Bunga</th>
                            <th class="p-3 w-36">Total Dibayar</th>
                            <th class="p-3 w-40">Sisa Total Utang</th>
                            <th class="p-3 min-w-[200px]">Peristiwa / Tonggak Lunas</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody" class="divide-y divide-line">
                        @foreach ($planData['strategies']['avalanche']['schedule'] as $row)
                            <tr class="hover:bg-cream/40 dark:hover:bg-line/20 {{ !empty($row['paid_off_debts']) ? 'bg-emerald-50/20 dark:bg-emerald-950/15' : '' }}">
                                <td class="p-3 text-center font-bold text-muted font-mono">
                                    {{ $row['month_index'] }}
                                </td>
                                <td class="p-3 font-semibold text-charcoal whitespace-nowrap">
                                    {{ $row['date_formatted'] }}
                                </td>
                                <td class="p-3 font-mono font-medium text-charcoal whitespace-nowrap">
                                    Rp {{ number_format($row['principal_paid'], 0, ',', '.') }}
                                </td>
                                <td class="p-3 font-mono text-muted whitespace-nowrap">
                                    Rp {{ number_format($row['interest_paid'], 0, ',', '.') }}
                                </td>
                                <td class="p-3 font-mono font-bold text-forest dark:text-emerald-400 whitespace-nowrap">
                                    Rp {{ number_format($row['total_paid'], 0, ',', '.') }}
                                </td>
                                <td class="p-3 font-mono font-bold text-charcoal whitespace-nowrap">
                                    Rp {{ number_format($row['remaining_debt'], 0, ',', '.') }}
                                </td>
                                <td class="p-3">
                                    @if (! empty($row['paid_off_debts']))
                                        @foreach ($row['paid_off_debts'] as $paidName)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                <span>🎉 LUNAS:</span>
                                                <span>{{ $paidName }}</span>
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        let currentStrategy = 'avalanche';
        let latestPlanData = @json($planData);
        let simulationTimeout = null;

        function setExtraMonthly(amount) {
            document.getElementById('extraMonthlyInput').value = amount;
            runSimulation();
        }

        function debouncedSimulate() {
            clearTimeout(simulationTimeout);
            simulationTimeout = setTimeout(runSimulation, 350);
        }

        async function runSimulation() {
            const extraMonthly = document.getElementById('extraMonthlyInput').value || 0;
            const oneTimePayment = document.getElementById('oneTimeInput').value || 0;

            try {
                const response = await fetch("{{ route('credit.payoff.simulate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        extra_monthly: extraMonthly,
                        one_time_payment: oneTimePayment
                    })
                });

                const res = await response.json();
                if (res.success && res.data) {
                    latestPlanData = res.data;
                    updateUI();
                }
            } catch (err) {
                console.error('Simulation error:', err);
            }
        }

        function selectStrategy(strategy) {
            currentStrategy = strategy;
            updateUI();
        }

        function updateUI() {
            if (!latestPlanData || !latestPlanData.has_debts) return;

            const av = latestPlanData.strategies.avalanche;
            const sn = latestPlanData.strategies.snowball;
            const st = latestPlanData.strategies.standard;
            const cmp = latestPlanData.comparison;

            // Total komitmen bulanan
            document.getElementById('totalCommitmentDisplay').textContent = 'Rp ' + Number(latestPlanData.total_budget_avalanche).toLocaleString('id-ID');

            // Kartu Avalanche
            document.getElementById('avalancheDate').textContent = av.debt_free_date;
            document.getElementById('avalancheMonths').textContent = `Dalam ${av.total_months} Bulan`;
            document.getElementById('avalancheInterest').textContent = 'Rp ' + Number(av.total_interest_paid).toLocaleString('id-ID');
            document.getElementById('avalancheTotal').textContent = 'Rp ' + Number(av.total_amount_paid).toLocaleString('id-ID');
            document.getElementById('avalancheSavingText').textContent = `Hemat Rp ${Number(cmp.avalanche_vs_standard.interest_saved).toLocaleString('id-ID')}`;
            document.getElementById('avalancheTimeText').textContent = `Lunas ${cmp.avalanche_vs_standard.months_saved} bulan lebih cepat dibandingkan cicilan standar reguler.`;

            // Kartu Snowball
            document.getElementById('snowballDate').textContent = sn.debt_free_date;
            document.getElementById('snowballMonths').textContent = `Dalam ${sn.total_months} Bulan`;
            document.getElementById('snowballInterest').textContent = 'Rp ' + Number(sn.total_interest_paid).toLocaleString('id-ID');
            document.getElementById('snowballTotal').textContent = 'Rp ' + Number(sn.total_amount_paid).toLocaleString('id-ID');
            document.getElementById('snowballSavingText').textContent = `Hemat Rp ${Number(cmp.snowball_vs_standard.interest_saved).toLocaleString('id-ID')}`;
            document.getElementById('snowballTimeText').textContent = `Lunas ${cmp.snowball_vs_standard.months_saved} bulan lebih cepat dibandingkan cicilan standar reguler.`;

            // Kartu Standard
            document.getElementById('standardDate').textContent = st.debt_free_date;
            document.getElementById('standardMonths').textContent = `Dalam ${st.total_months} Bulan`;
            document.getElementById('standardInterest').textContent = 'Rp ' + Number(st.total_interest_paid).toLocaleString('id-ID');
            document.getElementById('standardTotal').textContent = 'Rp ' + Number(st.total_amount_paid).toLocaleString('id-ID');

            // Tombol Toggle Aktif
            const btnAv = document.getElementById('btnToggleAvalanche');
            const btnSn = document.getElementById('btnToggleSnowball');
            if (currentStrategy === 'avalanche') {
                btnAv.className = 'px-2.5 py-1 rounded-md font-bold bg-panel text-forest dark:text-emerald-400 shadow-2xs transition';
                btnSn.className = 'px-2.5 py-1 rounded-md font-semibold text-muted hover:text-charcoal transition';
                document.getElementById('activeStrategyTitle').textContent = 'Avalanche';
            } else {
                btnSn.className = 'px-2.5 py-1 rounded-md font-bold bg-panel text-blue-600 dark:text-blue-400 shadow-2xs transition';
                btnAv.className = 'px-2.5 py-1 rounded-md font-semibold text-muted hover:text-charcoal transition';
                document.getElementById('activeStrategyTitle').textContent = 'Snowball';
            }

            // Update Daftar Urutan Pelunasan
            const stratObj = currentStrategy === 'snowball' ? sn : (currentStrategy === 'standard' ? st : av);
            const orderList = document.getElementById('payoffOrderList');
            if (stratObj.payoff_order && stratObj.payoff_order.length > 0) {
                orderList.innerHTML = stratObj.payoff_order.map((o, idx) => `
                    <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 font-bold text-xs flex items-center justify-center shrink-0">
                                #${idx + 1}
                            </span>
                            <div>
                                <div class="font-bold text-sm text-charcoal">${o.name}</div>
                                <div class="text-[11px] text-muted">Target lunas: <strong class="text-emerald-600 dark:text-emerald-400">${o.date}</strong> (Bulan ke-${o.month})</div>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            Target Lunas #${idx + 1}
                        </span>
                    </div>
                `).join('');
            } else {
                orderList.innerHTML = '<p class="text-xs text-muted">Tidak ada utang yang terdaftar.</p>';
            }

            // Update Tabel Linimasa Amortisasi
            const tbody = document.getElementById('scheduleTableBody');
            if (stratObj.schedule && stratObj.schedule.length > 0) {
                tbody.innerHTML = stratObj.schedule.map(r => `
                    <tr class="hover:bg-cream/40 dark:hover:bg-line/20 ${r.paid_off_debts && r.paid_off_debts.length > 0 ? 'bg-emerald-50/20 dark:bg-emerald-950/15' : ''}">
                        <td class="p-3 text-center font-bold text-muted font-mono">${r.month_index}</td>
                        <td class="p-3 font-semibold text-charcoal whitespace-nowrap">${r.date_formatted}</td>
                        <td class="p-3 font-mono font-medium text-charcoal whitespace-nowrap">Rp ${Number(r.principal_paid).toLocaleString('id-ID')}</td>
                        <td class="p-3 font-mono text-muted whitespace-nowrap">Rp ${Number(r.interest_paid).toLocaleString('id-ID')}</td>
                        <td class="p-3 font-mono font-bold text-forest dark:text-emerald-400 whitespace-nowrap">Rp ${Number(r.total_paid).toLocaleString('id-ID')}</td>
                        <td class="p-3 font-mono font-bold text-charcoal whitespace-nowrap">Rp ${Number(r.remaining_debt).toLocaleString('id-ID')}</td>
                        <td class="p-3">
                            ${r.paid_off_debts && r.paid_off_debts.length > 0
                                ? r.paid_off_debts.map(p => `
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <span>🎉 LUNAS:</span>
                                        <span>${p}</span>
                                    </span>
                                `).join(' ')
                                : '<span class="text-muted text-[11px]">-</span>'
                            }
                        </td>
                    </tr>
                `).join('');
            }
        }
    </script>
</x-layouts.app>
