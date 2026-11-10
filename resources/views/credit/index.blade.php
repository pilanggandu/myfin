<x-layouts.app title="Kredit & Cicilan">
    <!-- Page Heading -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / KEWAJIBAN & HUTANG</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Kredit & Cicilan
            </h1>
            <p class="text-xs text-muted">
                Kelola seluruh belanja, langganan, maupun cicilan yang memotong limit <strong>Kartu Kredit / Paylater</strong> secara terisolasi dari saldo kas. (Tagihan rutin non-kredit dikelola di <a href="{{ route('recurring.index') }}" class="text-forest font-semibold hover:underline">Jadwal Berulang</a>.)
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'credit-metrics\']')" 
                class="privacy-card-toggle inline-flex items-center gap-1.5 px-3 py-2 border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal Ringkasan Kredit"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Ringkasan</span>
            </button>
            <button 
                type="button" 
                onclick="openCreditAccountModal()" 
                class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 border border-line rounded-lg text-xs font-bold text-charcoal bg-panel hover:bg-cream transition cursor-pointer shadow-xs"
            >
                <span>+ Tambah Kartu / Kredit</span>
            </button>
            <a 
                href="{{ route('credit.purchases.create') }}" 
                class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Catat Belanja Cicilan</span>
            </a>
        </div>
    </div>

    <!-- Alert Status Feedback -->
    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl border border-emerald-500/20 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold ml-4">✕</button>
        </div>
    @endif

    <!-- Error Validation Feedback -->
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl border border-red/20 bg-red/10 text-red text-xs">
            <div class="font-bold mb-1">Terjadi kesalahan pada data:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Alert: Cicilan Terlambat / Jatuh Tempo Segera -->
    @if ($overdueInstallments->isNotEmpty())
        <div class="mb-6 p-4 rounded-xl border border-red/30 bg-red/10 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <span class="font-bold text-red text-sm block">Perhatian: Ada {{ $overdueInstallments->count() }} cicilan terlambat melewati tanggal jatuh tempo!</span>
                    <span class="text-muted">Total tunggakan: <strong class="text-charcoal">Rp {{ number_format($overdueInstallments->sum('total_amount'), 0, ',', '.') }}</strong>. Segera lakukan pelunasan untuk menjaga kesehatan finansial Anda.</span>
                </div>
            </div>
            <a href="{{ route('credit.index', ['tab' => 'plans']) }}" class="self-start sm:self-auto min-h-[36px] inline-flex items-center px-3.5 py-1.5 bg-red text-white font-bold rounded-lg hover:bg-red/90 transition shadow-xs text-xs whitespace-nowrap">
                Lihat Tagihan &rarr;
            </a>
        </div>
    @elseif ($dueSoonInstallments->isNotEmpty())
        <div class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⏳</span>
                <div>
                    <span class="font-bold text-amber-800 dark:text-amber-300 text-sm block">Pengingat: Ada {{ $dueSoonInstallments->count() }} cicilan jatuh tempo dalam 3 hari ke depan.</span>
                    <span class="text-muted">Total tagihan terdekat: <strong class="text-charcoal">Rp {{ number_format($dueSoonInstallments->sum('total_amount'), 0, ',', '.') }}</strong>. Pastikan saldo rekening kas mencukupi.</span>
                </div>
            </div>
            <a href="{{ route('credit.index', ['tab' => 'plans']) }}" class="self-start sm:self-auto min-h-[36px] inline-flex items-center px-3.5 py-1.5 bg-amber-600 text-white font-bold rounded-lg hover:bg-amber-700 transition shadow-xs text-xs whitespace-nowrap">
                Periksa Jadwal &rarr;
            </a>
        </div>
    @endif

    <!-- Metric Overview Cards -->
    <div data-privacy-scope="credit-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Limit Kredit -->
        <div class="panel-card p-5">
            <div class="flex items-center justify-between text-muted text-xs mb-2">
                <span class="font-semibold uppercase tracking-wider text-[11px]">Total Limit Kredit</span>
                <span class="text-base">💳</span>
            </div>
            <div class="font-serif text-2xl font-medium text-charcoal">
                <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}">Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}</span>
            </div>
            <div class="mt-2 text-xs text-muted">
                Dari {{ $creditAccounts->whereNull('archived_at')->count() }} akun aktif
            </div>
        </div>

        <!-- Total Kewajiban Aktif -->
        <div class="panel-card p-5 border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between text-muted text-xs mb-2">
                <span class="font-semibold uppercase tracking-wider text-[11px]">Total Kewajiban</span>
                <span class="text-base">⏳</span>
            </div>
            <div class="font-serif text-2xl font-medium text-amber-600 dark:text-amber-400">
                <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalLiability, 0, ',', '.') }}">Rp {{ number_format($totalLiability, 0, ',', '.') }}</span>
            </div>
            <div class="mt-2 text-xs text-muted">
                {{ $activePlans->count() }} rencana cicilan berjalan
            </div>
        </div>

        <!-- Sisa Limit Tersedia -->
        <div class="panel-card p-5 border-l-4 border-l-forest">
            <div class="flex items-center justify-between text-muted text-xs mb-2">
                <span class="font-semibold uppercase tracking-wider text-[11px]">Limit Tersedia</span>
                <span class="text-base">✨</span>
            </div>
            <div class="font-serif text-2xl font-medium text-forest">
                <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalAvailableLimit, 0, ',', '.') }}">Rp {{ number_format($totalAvailableLimit, 0, ',', '.') }}</span>
            </div>
            <div class="mt-2 text-xs text-muted">
                Dapat digunakan belanja
            </div>
        </div>

        <!-- Rasio Utilisasi Kredit -->
        <div class="panel-card p-5 border-l-4 {{ $utilizationStatus === 'safe' ? 'border-l-emerald-500' : ($utilizationStatus === 'moderate' ? 'border-l-amber-500' : 'border-l-red') }}">
            <div class="flex items-center justify-between text-muted text-xs mb-2">
                <span class="font-semibold uppercase tracking-wider text-[11px]">Utilisasi Limit</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $utilizationStatus === 'safe' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($utilizationStatus === 'moderate' ? 'bg-amber-500/10 text-amber-600' : 'bg-red/10 text-red') }}">
                    {{ $utilizationStatus === 'safe' ? 'Aman (<30%)' : ($utilizationStatus === 'moderate' ? 'Waspada' : 'Tinggi') }}
                </span>
            </div>
            <div class="font-serif text-2xl font-medium text-charcoal">
                {{ $totalUtilizationRate }}%
            </div>
            <div class="w-full bg-cream rounded-full h-1.5 mt-2.5 overflow-hidden border border-line/40">
                <div class="h-full rounded-full transition-all duration-300 {{ $utilizationStatus === 'safe' ? 'bg-forest' : ($utilizationStatus === 'moderate' ? 'bg-amber-500' : 'bg-red') }}" style="width: {{ min(100, $totalUtilizationRate) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Proyeksi Beban Cicilan 6 Bulan Mendatang -->
    @if ($totalLiability > 0)
        <div class="panel-card p-5 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-line mb-4">
                <div>
                    <h3 class="font-serif text-base text-charcoal font-medium">Proyeksi Beban Cicilan (6 Bulan Mendatang)</h3>
                    <p class="text-xs text-muted">Estimasi komitmen kas bulanan berdasarkan jadwal jatuh tempo cicilan aktif saat ini.</p>
                </div>
                <span class="text-xs font-mono font-bold text-forest bg-forest-light px-2.5 py-1 rounded-full border border-forest/20">
                    Sisa Pokok: Rp {{ number_format($totalLiability, 0, ',', '.') }}
                </span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach ($monthlyProjections as $proj)
                    <div class="p-3 rounded-xl border border-line bg-cream/30 dark:bg-sidebar/40 flex flex-col justify-between">
                        <span class="text-[11px] font-bold text-muted uppercase">{{ $proj['month_short'] }}</span>
                        <div class="mt-2">
                            <span class="font-mono text-sm font-bold {{ $proj['total_amount'] > 0 ? 'text-charcoal' : 'text-muted' }}">
                                Rp {{ number_format($proj['total_amount'], 0, ',', '.') }}
                            </span>
                            @if ($proj['total_amount'] > 0)
                                <div class="w-full bg-cream rounded-full h-1.5 mt-2 overflow-hidden border border-line/40">
                                    <div class="h-full bg-forest rounded-full" style="width: {{ $totalLiability > 0 ? min(100, round(($proj['total_amount'] / $totalLiability) * 100)) : 0 }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-line mb-6">
        <a 
            href="{{ route('credit.index', ['tab' => 'accounts']) }}" 
            class="px-4 py-3 text-xs font-bold border-b-2 transition {{ $tab === 'accounts' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            Akun Kredit & Kartu ({{ $creditAccounts->count() }})
        </a>
        <a 
            href="{{ route('credit.index', ['tab' => 'plans']) }}" 
            class="px-4 py-3 text-xs font-bold border-b-2 transition {{ $tab === 'plans' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            Cicilan Berjalan ({{ $activePlans->count() }})
        </a>
        <a 
            href="{{ route('credit.index', ['tab' => 'history']) }}" 
            class="px-4 py-3 text-xs font-bold border-b-2 transition {{ $tab === 'history' ? 'border-forest text-forest' : 'border-transparent text-muted hover:text-charcoal' }}"
        >
            Riwayat Selesai ({{ $completedPlans->count() }})
        </a>
    </div>

    <!-- Tab 1: Akun Kredit & Kartu -->
    @if ($tab === 'accounts')
        @if ($creditAccounts->isEmpty())
            <div class="panel-card p-12 text-center">
                <div class="text-4xl mb-3">💳</div>
                <h3 class="font-serif text-lg text-charcoal font-medium mb-1">Belum Ada Akun Kredit atau Kartu</h3>
                <p class="text-xs text-muted max-w-md mx-auto mb-5">
                    Tambahkan kartu kredit, layanan paylater, atau pinjaman untuk memantau limit dan memecah belanja menjadi cicilan rapi.
                </p>
                <button 
                    type="button" 
                    onclick="openCreditAccountModal()" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs"
                >
                    + Tambah Akun Kredit Pertama
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($creditAccounts as $account)
                    @php
                        $liability = $account->currentLiability();
                        $limit = $account->credit_limit;
                        $available = $account->availableLimit();
                        $usagePercent = $limit > 0 ? min(100, round(($liability / $limit) * 100)) : 0;
                    @endphp
                    <div class="panel-card p-5 flex flex-col justify-between {{ $account->isArchived() ? 'opacity-60 bg-cream/40' : '' }}">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="font-semibold text-sm text-charcoal">{{ $account->name }}</h3>
                                        @if ($account->isArchived())
                                            <span class="text-[10px] bg-muted/20 text-muted px-1.5 py-0.5 rounded font-bold uppercase">Diarsipkan</span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] text-muted capitalize">
                                        {{ str_replace('_', ' ', $account->type) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <span class="text-xs px-2 py-0.5 rounded font-bold {{ $usagePercent > 80 ? 'bg-red/10 text-red' : 'bg-forest/10 text-forest' }}">
                                        {{ $usagePercent }}% Terpakai
                                    </span>
                                    <button 
                                        type="button" 
                                        onclick="toggleCardPrivacy(this)" 
                                        class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs" 
                                        title="Sembunyikan / Tampilkan Nominal Kartu Ini"
                                    >
                                        <span class="privacy-toggle-icon"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Progress Bar Limit -->
                            <div class="w-full bg-cream rounded-full h-2 mb-4 overflow-hidden border border-line/40">
                                <div class="h-full rounded-full transition-all duration-300 {{ $usagePercent > 80 ? 'bg-red' : 'bg-forest' }}" style="width: {{ $usagePercent }}%"></div>
                            </div>

                            <!-- Detail Angka -->
                            <div class="space-y-2 text-xs border-t border-line/60 pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-muted">Batas Limit:</span>
                                    <span class="font-semibold text-charcoal privacy-balance" data-raw-value="{{ $account->formatted_limit }}">{{ $account->formatted_limit }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-muted">Limit Terpakai:</span>
                                    <span class="font-semibold text-amber-600 dark:text-amber-400 privacy-balance" data-raw-value="{{ $account->formatted_liability }}">{{ $account->formatted_liability }}</span>
                                </div>
                                @if ($account->nonInstallmentLiability() > 0)
                                    <div class="flex justify-between items-center text-[11px] text-muted -mt-1 pl-2">
                                        <span>• Pokok Cicilan:</span>
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($account->installmentPrincipalLiability(), fmod($account->installmentPrincipalLiability(), 1.0) == 0.0 ? 0 : 2, ',', '.') }}">Rp {{ number_format($account->installmentPrincipalLiability(), fmod($account->installmentPrincipalLiability(), 1.0) == 0.0 ? 0 : 2, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px] text-muted -mt-1 pl-2">
                                        <span>• Transaksi Non-Cicilan / Biaya:</span>
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($account->nonInstallmentLiability(), fmod($account->nonInstallmentLiability(), 1.0) == 0.0 ? 0 : 2, ',', '.') }}">Rp {{ number_format($account->nonInstallmentLiability(), fmod($account->nonInstallmentLiability(), 1.0) == 0.0 ? 0 : 2, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between items-center">
                                    <span class="text-muted">Limit Tersedia:</span>
                                    <span class="font-bold text-forest privacy-balance" data-raw-value="{{ $account->formatted_available_limit }}">{{ $account->formatted_available_limit }}</span>
                                </div>
                                @if ($account->installmentInterestAndFeeLiability() > 0)
                                    <div class="flex justify-between items-center text-[11px] text-muted border-t border-dashed border-line/40 pt-1.5 mt-1">
                                        <span title="Total sisa cicilan termasuk bunga sampai lunas (tidak memotong limit kartu)">Sisa Cicilan (+ Bunga):</span>
                                        <span class="font-medium text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($account->installmentTotalLiability(), fmod($account->installmentTotalLiability(), 1.0) == 0.0 ? 0 : 2, ',', '.') }}">Rp {{ number_format($account->installmentTotalLiability(), fmod($account->installmentTotalLiability(), 1.0) == 0.0 ? 0 : 2, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if ($account->billing_cycle_day || $account->due_day)
                                    <div class="flex justify-between items-center text-[11px] text-muted border-t border-dashed border-line/50 pt-2 mt-2">
                                        <span>Cetak: Tgl {{ $account->billing_cycle_day ?? '-' }}</span>
                                        <span>Jatuh Tempo: Tgl {{ $account->due_day ?? '-' }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between items-center text-[11px] border-t border-dashed border-line/50 pt-2 mt-2">
                                        <span class="text-amber-600 dark:text-amber-400 font-medium">⚠️ Belum set tgl cetak & tempo</span>
                                        <button type="button" onclick="editCreditAccount({{ json_encode($account) }})" class="text-[10px] text-forest font-bold hover:underline cursor-pointer">
                                            Atur
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @php
                                $statement = $account->getStatementBill();
                            @endphp
                            <!-- Box Lembar Tagihan Bulan Ini -->
                            <div class="mt-3 p-3 rounded-xl border border-line bg-cream/40 flex items-center justify-between gap-3">
                                <div>
                                    <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">Tagihan Bulan Ini</span>
                                    <span class="font-serif font-bold text-sm text-charcoal block">
                                        {{ $statement['formatted_total_pending'] }}
                                    </span>
                                    <span class="text-[10px] text-muted block">
                                        {{ $statement['pending_count'] }} item belum lunas
                                    </span>
                                </div>
                                <a 
                                    href="{{ route('credit.accounts.statement', $account) }}" 
                                    class="min-h-[34px] px-2.5 py-1.5 border border-line bg-panel text-charcoal hover:bg-cream rounded-lg text-xs font-bold inline-flex items-center gap-1 shadow-xs transition"
                                    title="Buka Lembar Tagihan (Statement) Bulan Ini"
                                >
                                    <span>📄 Tagihan</span>
                                </a>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="flex items-center justify-between border-t border-line mt-4 pt-3 text-xs">
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button" 
                                    onclick="openEditCreditAccountModal({{ json_encode($account) }})" 
                                    class="text-muted hover:text-charcoal font-bold py-1 min-h-[36px]"
                                >
                                    Edit
                                </button>
                                @if (!$account->isArchived() && $liability > 0)
                                    <button 
                                        type="button" 
                                        onclick="openPayBillModal({{ $account->id }}, '{{ addslashes($account->name) }}', {{ $statement['total_pending'] }}, '{{ $statement['formatted_total_pending'] }}', {{ $liability }}, '{{ $account->formatted_liability }}', {{ json_encode($statement['installments']->where('status', 'pending')->pluck('id')->values()) }})" 
                                        class="min-h-[34px] px-2.5 py-1 bg-forest text-white rounded-md text-[11px] font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs"
                                    >
                                        Bayar Tagihan
                                    </button>
                                @endif
                            </div>
                            @if ($account->isArchived())
                                <form method="POST" action="{{ route('credit.accounts.unarchive', $account) }}">
                                    @csrf
                                    <button type="submit" class="text-forest hover:text-forest-hover font-bold py-1 min-h-[36px]">
                                        Aktifkan Kembali
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('credit.accounts.destroy', $account) }}" onsubmit="return confirm('Arsipkan akun kredit ini? Data historis tetap tersimpan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red hover:text-red/80 font-bold py-1 min-h-[36px]">
                                        Arsipkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Tab 2: Cicilan Berjalan -->
    @elseif ($tab === 'plans')
        <!-- Filter & Search Toolbar -->
        <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-panel p-3.5 rounded-xl border border-line shadow-xs">
            <form method="GET" action="{{ route('credit.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                <input type="hidden" name="tab" value="plans">
                <div class="relative flex-1 min-w-[180px]">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $planSearch }}" 
                        placeholder="Cari nama barang atau cicilan..." 
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>
                <div class="w-full sm:w-auto min-w-[160px]">
                    <select 
                        name="account_id" 
                        onchange="this.form.submit()" 
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                    >
                        <option value="">-- Semua Akun Kredit --</option>
                        @foreach ($creditAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ $selectedAccountFilter == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="min-h-[36px] px-3.5 py-1.5 bg-forest text-white font-bold rounded-lg text-xs hover:bg-forest-hover transition cursor-pointer shadow-xs">
                    Cari
                </button>
                @if ($planSearch || $selectedAccountFilter)
                    <a href="{{ route('credit.index', ['tab' => 'plans']) }}" class="min-h-[36px] inline-flex items-center px-3 py-1.5 border border-line text-muted hover:text-charcoal font-semibold rounded-lg text-xs bg-panel transition">
                        Reset Filter
                    </a>
                @endif
            </form>
        </div>

        @if ($activePlans->isEmpty())
            <div class="panel-card p-12 text-center">
                <div class="text-4xl mb-3">📋</div>
                <h3 class="font-serif text-lg text-charcoal font-medium mb-1">Tidak Ada Cicilan Aktif</h3>
                <p class="text-xs text-muted max-w-md mx-auto mb-5">
                    @if ($planSearch || $selectedAccountFilter)
                        Tidak ada transaksi cicilan yang sesuai dengan filter pencarian.
                    @else
                        Semua transaksi cicilan Anda telah lunas atau Anda belum mencatat pembelian dengan kartu kredit/paylater.
                    @endif
                </p>
                <a 
                    href="{{ route('credit.purchases.create') }}" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs"
                >
                    Catat Pembelian Cicilan Baru
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($activePlans as $plan)
                    @php
                        $paidCount = $plan->installments->where('status', 'paid')->count();
                        $totalCount = $plan->installments->count();
                        $percent = $plan->total_amount > 0 ? min(100, round(($plan->paidPrincipal() / $plan->total_amount) * 100)) : 0;
                        $nextPending = $plan->installments->where('status', 'pending')->first();
                    @endphp
                    <div class="panel-card p-5">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold text-forest bg-forest/10 px-2 py-0.5 rounded">
                                        {{ $plan->creditAccount->name }}
                                    </span>
                                    <span class="text-xs text-muted">
                                        Kategori: <strong class="text-charcoal">{{ $plan->category->name }}</strong>
                                    </span>
                                    @if ($nextPending && $nextPending->isOverdue())
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red/15 text-red">
                                            ⚠️ Terlambat {{ $nextPending->daysOverdue() }} hari
                                        </span>
                                    @elseif ($nextPending && $nextPending->isDueToday())
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300">
                                            ⚡ Jatuh Tempo Hari Ini
                                        </span>
                                    @elseif ($nextPending && $nextPending->isDueSoon())
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                            ⏳ Segera
                                        </span>
                                    @endif
                                </div>
                                <h3 class="font-serif text-lg text-charcoal font-medium truncate">
                                    {{ $plan->name }}
                                </h3>
                                <div class="text-xs text-muted mt-1 flex flex-wrap items-center gap-3">
                                    <span>Mulai: {{ $plan->start_date->format('d M Y') }}</span>
                                    <span>•</span>
                                    <span>Tenor: <strong>{{ $plan->tenor }} bulan</strong></span>
                                    <span>•</span>
                                    <span>Progress: <strong>{{ $paidCount }} / {{ $totalCount }} lunas</strong> ({{ $percent }}%)</span>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 border-t md:border-t-0 md:border-l border-line pt-4 md:pt-0 md:pl-6">
                                <div class="text-right sm:text-left">
                                    <div class="text-[11px] text-muted uppercase tracking-wider font-semibold">Sisa Pokok</div>
                                    <div class="font-serif text-xl font-bold text-amber-600 dark:text-amber-400">
                                        {{ $plan->formatted_remaining }}
                                    </div>
                                    <div class="text-[11px] text-muted">
                                        Total: {{ $plan->formatted_total_amount }}
                                    </div>
                                </div>

                                <a 
                                    href="{{ route('credit.plans.show', $plan) }}" 
                                    class="min-h-[44px] inline-flex items-center justify-center gap-1.5 px-4 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs whitespace-nowrap"
                                >
                                    <span>Detail & Bayar</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </div>

                        <!-- Progress Bar Plan -->
                        <div class="w-full bg-cream rounded-full h-1.5 mt-4 overflow-hidden border border-line/40">
                            <div class="h-full bg-forest rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                        </div>

                        @if ($nextPending)
                            <div class="mt-3 text-xs bg-panel p-2.5 rounded-lg border border-line/60 flex items-center justify-between">
                                <span class="text-muted">
                                    Jatuh tempo berikutnya: <strong class="text-charcoal">{{ $nextPending->due_date->format('d M Y') }}</strong> (Cicilan #{{ $nextPending->installment_number }})
                                </span>
                                <span class="font-semibold text-charcoal">
                                    {{ $nextPending->formatted_principal }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    <!-- Tab 3: Riwayat Selesai -->
    @elseif ($tab === 'history')
        @if ($completedPlans->isEmpty())
            <div class="panel-card p-12 text-center">
                <div class="text-4xl mb-3">📁</div>
                <h3 class="font-serif text-lg text-charcoal font-medium mb-1">Belum Ada Riwayat Cicilan</h3>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Cicilan yang telah lunas seluruhnya atau dibatalkan akan dirangkum di sini sebagai arsip histori.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($completedPlans as $plan)
                    <div class="panel-card p-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded font-bold {{ $plan->isCompleted() ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-muted/20 text-muted' }}">
                                    {{ $plan->isCompleted() ? 'Lunas' : 'Dibatalkan' }}
                                </span>
                                <h4 class="font-semibold text-sm text-charcoal truncate">{{ $plan->name }}</h4>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                {{ $plan->creditAccount->name }} • {{ $plan->tenor }} bulan • Total {{ $plan->formatted_total_amount }}
                            </div>
                        </div>
                        <a 
                            href="{{ route('credit.plans.show', $plan) }}" 
                            class="min-h-[38px] inline-flex items-center px-3 py-1.5 border border-line rounded-lg text-xs font-semibold text-charcoal bg-panel hover:bg-cream transition"
                        >
                            Lihat Arsip
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <!-- Modal Tambah / Edit Akun Kredit -->
    <div id="creditAccountModal" onclick="if (event.target === this) closeCreditAccountModal()" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-md rounded-2xl border border-line shadow-2xl p-6 relative">
            <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
                <h3 id="modalTitle" class="font-serif text-lg text-charcoal font-medium">Tambah Akun Kredit</h3>
                <button type="button" onclick="closeCreditAccountModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal cursor-pointer">✕</button>
            </div>

            <form id="creditAccountForm" method="POST" action="{{ route('credit.accounts.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div>
                    <label for="credit_account_name" class="block text-xs font-semibold text-charcoal mb-1">Nama Kartu / Layanan Kredit</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="credit_account_name" 
                        required 
                        placeholder="Contoh: BCA Everyday Card, Mandiri Livin, SPayLater"
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div>
                    <label for="credit_account_type" class="block text-xs font-semibold text-charcoal mb-1">Tipe Kredit</label>
                    <select 
                        name="type" 
                        id="credit_account_type" 
                        required 
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                    >
                        <option value="credit_card">Kartu Kredit (Credit Card)</option>
                        <option value="paylater">PayLater</option>
                        <option value="loan">Pinjaman / Cicilan Tunai</option>
                    </select>
                </div>

                <div>
                    <label for="credit_account_limit" class="block text-xs font-semibold text-charcoal mb-1">Batas Limit Kredit (Rp)</label>
                    <input 
                        type="number" 
                        name="credit_limit" 
                        id="credit_account_limit" 
                        required 
                        min="0"
                        step="any"
                        placeholder="Contoh: 15000000"
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest font-mono"
                        onfocus="this.select()"
                    >
                    <span class="text-[10px] text-muted">Limit kredit yang diberikan oleh bank atau penyedia layanan.</span>
                </div>

                <div class="p-3.5 rounded-xl border border-line bg-cream/30 space-y-2.5">
                    <div>
                        <div class="text-xs font-bold text-charcoal">Siklus Penagihan & Jatuh Tempo</div>
                        <p class="text-[11px] text-muted">
                            Jadwal tanggal cetak tagihan bulanan dan tanggal batas akhir pembayaran.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="credit_account_billing_cycle" class="block text-xs font-semibold text-charcoal mb-1">
                                Tgl Cetak Tagihan (Terbit)
                            </label>
                            <input 
                                type="number" 
                                name="billing_cycle_day" 
                                id="credit_account_billing_cycle" 
                                min="1" 
                                max="31"
                                placeholder="Contoh: 20"
                                class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest font-mono"
                            >
                            <span class="text-[10px] text-muted block mt-0.5">Tgl 1-31 cut-off tagihan bulanan</span>
                        </div>
                        <div>
                            <label for="credit_account_due_day" class="block text-xs font-semibold text-charcoal mb-1">
                                Tgl Jatuh Tempo
                            </label>
                            <input 
                                type="number" 
                                name="due_day" 
                                id="credit_account_due_day" 
                                min="1" 
                                max="31"
                                placeholder="Contoh: 10"
                                class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest font-mono"
                            >
                            <span class="text-[10px] text-muted block mt-0.5">Tgl 1-31 batas akhir bayar</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closeCreditAccountModal()" 
                        class="min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSaveCreditAccount"
                        class="min-h-[44px] px-5 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer flex items-center gap-2"
                    >
                        <span id="btnSaveCreditAccountText">Simpan Akun Kredit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Bayar Tagihan Kartu Kredit -->
    <div id="payBillModal" onclick="if (event.target === this) closePayBillModal()" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-md rounded-2xl border border-line shadow-2xl p-6 relative">
            <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
                <div>
                    <h3 id="payBillModalTitle" class="font-serif text-lg text-charcoal font-medium">Bayar Tagihan Kartu Kredit</h3>
                    <p id="payBillModalSubtitle" class="text-xs text-muted">Pelunasan kewajiban / saldo hutang kartu</p>
                </div>
                <button type="button" onclick="closePayBillModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal cursor-pointer">✕</button>
            </div>

            <form id="payBillForm" method="POST" action="" class="space-y-4">
                @csrf
                <div id="payBillInstallmentIdsContainer"></div>

                <div id="quickAmountOptions" class="p-2.5 rounded-lg border border-line bg-cream/30 space-y-1.5">
                    <span class="text-[10px] uppercase font-bold text-muted block">Pilihan Pembayaran:</span>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            type="button" 
                            id="btnQuickStatement" 
                            onclick="selectQuickAmount('statement')" 
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-forest bg-forest/10 text-forest text-left transition cursor-pointer"
                        >
                            <span class="block text-[10px] text-muted font-normal">Tagihan Bulan Ini</span>
                            <span id="quickStatementAmountText" class="block font-mono">Rp 0</span>
                        </button>
                        <button 
                            type="button" 
                            id="btnQuickTotal" 
                            onclick="selectQuickAmount('total')" 
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-line bg-panel text-muted hover:text-charcoal text-left transition cursor-pointer"
                        >
                            <span class="block text-[10px] text-muted font-normal">Total Seluruh Kewajiban</span>
                            <span id="quickTotalAmountText" class="block font-mono">Rp 0</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="pay_bill_source_account_id" class="block text-xs font-semibold text-charcoal mb-1">
                        Rekening Kas / Bank Sumber Dana <span class="text-red">*</span>
                    </label>
                    <select 
                        name="source_account_id" 
                        id="pay_bill_source_account_id" 
                        required 
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                    >
                        <option value="">-- Pilih Rekening Sumber --</option>
                        @foreach ($cashAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Saldo: {{ $acc->formatted_balance }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="pay_bill_amount" class="block text-xs font-semibold text-charcoal mb-1">
                        Nominal Pembayaran (Rp) <span class="text-red">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="pay_bill_amount" 
                        required 
                        min="0.01"
                        step="any"
                        placeholder="Contoh: 1500000"
                        onfocus="this.select()"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono font-bold text-base focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                    <div id="payBillLiabilityHint" class="text-[10px] text-muted mt-1"></div>
                </div>

                <div>
                    <label for="pay_bill_payment_date" class="block text-xs font-semibold text-charcoal mb-1">
                        Tanggal Pembayaran <span class="text-red">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="payment_date" 
                        id="pay_bill_payment_date" 
                        required 
                        value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->toDateString() }}"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div>
                    <label for="pay_bill_notes" class="block text-xs font-semibold text-charcoal mb-1">Catatan (Opsional)</label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="pay_bill_notes" 
                        placeholder="Contoh: Pembayaran tagihan bulanan..."
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closePayBillModal()" 
                        class="min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSubmitPayBill"
                        class="min-h-[44px] px-5 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer flex items-center gap-2 shadow-xs"
                    >
                        <span id="btnSubmitPayBillText">Konfirmasi Bayar Tagihan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentModalStatementAmount = 0;
        let currentModalTotalLiability = 0;
        let currentModalPendingIds = [];

        function openPayBillModal(id, name, statementAmount, formattedStatementAmount, totalLiability, formattedTotalLiability, pendingIds = []) {
            document.getElementById('payBillModalTitle').textContent = 'Bayar Tagihan ' + name;
            document.getElementById('payBillForm').action = "/credit/accounts/" + id + "/pay-bill";

            currentModalStatementAmount = Number(statementAmount) || 0;
            currentModalTotalLiability = Number(totalLiability) || 0;
            currentModalPendingIds = Array.isArray(pendingIds) ? pendingIds : [];

            document.getElementById('quickStatementAmountText').textContent = formattedStatementAmount || 'Rp 0';
            document.getElementById('quickTotalAmountText').textContent = formattedTotalLiability || 'Rp 0';

            if (currentModalStatementAmount > 0) {
                selectQuickAmount('statement');
            } else {
                selectQuickAmount('total');
            }

            document.getElementById('payBillLiabilityHint').textContent = 'Kewajiban aktif total: ' + formattedTotalLiability;
            document.getElementById('payBillModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('pay_bill_source_account_id').focus(), 50);
        }

        function selectQuickAmount(type) {
            const btnStatement = document.getElementById('btnQuickStatement');
            const btnTotal = document.getElementById('btnQuickTotal');
            const inputAmount = document.getElementById('pay_bill_amount');
            const container = document.getElementById('payBillInstallmentIdsContainer');
            container.innerHTML = '';

            if (type === 'statement') {
                btnStatement.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold border border-forest bg-forest/10 text-forest text-left transition cursor-pointer';
                btnTotal.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold border border-line bg-panel text-muted hover:text-charcoal text-left transition cursor-pointer';
                inputAmount.value = currentModalStatementAmount;

                currentModalPendingIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'installment_ids[]';
                    input.value = id;
                    container.appendChild(input);
                });
            } else {
                btnTotal.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold border border-forest bg-forest/10 text-forest text-left transition cursor-pointer';
                btnStatement.className = 'px-2.5 py-1.5 rounded-lg text-xs font-bold border border-line bg-panel text-muted hover:text-charcoal text-left transition cursor-pointer';
                inputAmount.value = currentModalTotalLiability;
            }
        }

        function closePayBillModal() {
            document.getElementById('payBillModal').classList.add('hidden');
        }

        function openCreditAccountModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Akun Kredit';
            document.getElementById('creditAccountForm').action = "{{ route('credit.accounts.store') }}";
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('credit_account_name').value = '';
            document.getElementById('credit_account_type').value = 'credit_card';
            document.getElementById('credit_account_limit').value = '';
            document.getElementById('credit_account_billing_cycle').value = '';
            document.getElementById('credit_account_due_day').value = '';
            document.getElementById('btnSaveCreditAccountText').textContent = 'Simpan Akun Kredit';
            document.getElementById('creditAccountModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('credit_account_name').focus(), 50);
        }

        function openEditCreditAccountModal(account) {
            document.getElementById('modalTitle').textContent = 'Edit Akun Kredit';
            document.getElementById('creditAccountForm').action = "/credit/accounts/" + account.id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('credit_account_name').value = account.name;
            document.getElementById('credit_account_type').value = account.type;
            document.getElementById('credit_account_limit').value = account.credit_limit;
            document.getElementById('credit_account_billing_cycle').value = account.billing_cycle_day || '';
            document.getElementById('credit_account_due_day').value = account.due_day || '';
            document.getElementById('btnSaveCreditAccountText').textContent = 'Perbarui Akun Kredit';
            document.getElementById('creditAccountModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('credit_account_name').focus(), 50);
        }

        function closeCreditAccountModal() {
            document.getElementById('creditAccountModal').classList.add('hidden');
        }

        document.getElementById('creditAccountForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnSaveCreditAccount');
            const txt = document.getElementById('btnSaveCreditAccountText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Menyimpan...';
        });

        document.getElementById('payBillForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitPayBill');
            const txt = document.getElementById('btnSubmitPayBillText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Memproses Pembayaran...';
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('creditAccountModal').classList.contains('hidden')) {
                    closeCreditAccountModal();
                }
                if (!document.getElementById('payBillModal').classList.contains('hidden')) {
                    closePayBillModal();
                }
            }
        });
    </script>
</x-layouts.app>
