<x-layouts.app title="Dashboard">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-7 panel-card p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <span class="avatar-circle w-12 h-12 text-base font-bold flex-shrink-0 shadow-xs">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                @php
                    $hour = (int) now()->format('H');
                    if ($hour >= 5 && $hour < 11) {
                        $greeting = 'Selamat Pagi';
                    } elseif ($hour >= 11 && $hour < 15) {
                        $greeting = 'Selamat Siang';
                    } elseif ($hour >= 15 && $hour < 18) {
                        $greeting = 'Selamat Sore';
                    } else {
                        $greeting = 'Selamat Malam';
                    }
                @endphp
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg sm:text-xl font-bold text-charcoal leading-tight">
                        {{ $greeting }}, {{ $user->name }}! 👋
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border border-line bg-cream text-[11px] font-semibold text-charcoal">
                        <span class="w-1.5 h-1.5 rounded-full {{ $netCashFlow >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        {{ $netCashFlow >= 0 ? 'Kondisi: Surplus' : 'Kondisi: Defisit' }}
                    </span>
                    @if (($overdueScheduleCount ?? 0) > 0)
                        <a href="#dashboard-timeline" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border border-rose-300 bg-rose-100 dark:bg-rose-950/60 text-[11px] font-bold text-rose-800 dark:text-rose-300 hover:opacity-90 transition text-decoration-none">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $overdueScheduleCount }} Jadwal Jatuh Tempo
                        </a>
                    @endif
                </div>
                <p class="text-xs text-muted mt-1 leading-normal">
                    Berikut ringkasan kondisi keuangan ruang <strong class="text-charcoal font-semibold">{{ $space?->name }}</strong> ({{ $space?->isPersonal() ? 'Personal' : 'Bersama' }}) yang perlu diperhatikan hari ini.
                </p>
            </div>
        </div>

        <div class="lg:col-span-5 grid grid-cols-1 min-[360px]:grid-cols-2 gap-2.5">
            <a href="{{ route('transactions.index') }}" class="panel-card p-3 min-h-[56px] flex items-center gap-2.5 hover:bg-cream hover:border-sage transition cursor-pointer text-decoration-none group">
                <span class="w-8 h-8 rounded-lg bg-forest-light text-forest flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 17.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <span class="block text-xs font-bold text-charcoal group-hover:text-forest truncate">Transaksi</span>
                    <span class="block text-[10.5px] text-muted truncate">Catatan arus kas</span>
                </div>
            </a>

            <a href="{{ route('accounts.index') }}" class="panel-card p-3 flex items-center gap-2.5 hover:bg-cream hover:border-sage transition cursor-pointer text-decoration-none group">
                <span class="w-8 h-8 rounded-lg bg-forest-light text-forest flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <span class="block text-xs font-bold text-charcoal group-hover:text-forest truncate">Rekening & Wallet</span>
                    <span class="block text-[10.5px] text-muted truncate">{{ $accounts->count() }} akun aktif</span>
                </div>
            </a>

            <a href="{{ route('budgets.index') }}" class="panel-card p-3 flex items-center gap-2.5 hover:bg-cream hover:border-sage transition cursor-pointer text-decoration-none group">
                <span class="w-8 h-8 rounded-lg bg-forest-light text-forest flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <span class="block text-xs font-bold text-charcoal group-hover:text-forest truncate">Anggaran</span>
                    <span class="block text-[10.5px] text-muted truncate">Kontrol limit bulanan</span>
                </div>
            </a>

            <a href="{{ route('reports.index') }}" class="panel-card p-3 flex items-center gap-2.5 hover:bg-cream hover:border-sage transition cursor-pointer text-decoration-none group">
                <span class="w-8 h-8 rounded-lg bg-forest-light text-forest flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <span class="block text-xs font-bold text-charcoal group-hover:text-forest truncate">Laporan Keuangan</span>
                    <span class="block text-[10.5px] text-muted truncate">Arus kas & riwayat</span>
                </div>
            </a>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-2.5 rounded-lg bg-panel border border-line">
        <div class="flex items-center gap-2 flex-wrap text-xs text-muted">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-cream border border-line text-charcoal font-semibold">
                <span>🏠</span>
                <span>{{ $space?->name }}</span>
                <span class="text-[10px] text-muted font-normal">({{ $space?->isPersonal() ? 'Personal' : 'Bersama' }})</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-cream border border-line text-charcoal font-semibold">
                <span>📅</span>
                <span>{{ now()->translatedFormat('F Y') }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-cream border border-line text-charcoal font-semibold">
                <span>💳</span>
                <span>{{ $accounts->count() }} Rekening Aktif</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-cream border border-line text-charcoal font-semibold">
                <span>💱</span>
                <span>IDR (Rp)</span>
            </span>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'dashboard-metrics\']')" 
                class="privacy-card-toggle btn-arus-secondary inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold min-h-[44px] sm:min-h-0 sm:h-8.5 py-2 sm:py-1" 
                title="Sembunyikan / Tampilkan Saldo Ringkasan"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Saldo Ringkasan</span>
            </button>

            <button 
                type="button" 
                onclick="openQuickTransactionModal()" 
                class="btn-arus-primary cursor-pointer text-xs font-bold min-h-[44px] sm:min-h-0 sm:h-8.5 py-2 sm:py-1"
            >
                ⚡ Catat Cepat
            </button>

            <a 
                href="{{ route('transactions.create') }}" 
                class="btn-arus-secondary text-xs font-semibold min-h-[44px] sm:min-h-0 sm:h-8.5 py-2 sm:py-1"
            >
                + Transaksi Baru
            </a>
        </div>
    </div>

    <div class="panel-card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3.5 mb-4 border-b border-line">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-forest shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </span>
                <h2 class="text-sm font-bold text-charcoal">Ringkasan Keuangan</h2>
            </div>
            <div class="inline-flex rounded-lg p-0.5 bg-cream border border-line text-xs font-semibold shrink-0">
                <span class="px-2.5 py-1.5 sm:py-1 rounded-md bg-forest text-white">Bulan Ini</span>
                <a href="{{ route('reports.index') }}" class="px-2.5 py-1.5 sm:py-1 text-muted hover:text-charcoal transition text-decoration-none inline-flex items-center">Semua Periode</a>
            </div>
        </div>

        <div data-privacy-scope="dashboard-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5">
            <x-metric-card 
                :notch="$totalBalance >= 0 ? 'green' : 'pink'"
                label="TOTAL SALDO" 
                :value="'Rp ' . number_format($totalBalance, 0, ',', '.')" 
                :sub="$accounts->count() . ' rekening aktif terdaftar'" 
                :toggle="true"
            />

            <x-metric-card 
                notch="green"
                label="PEMASUKAN" 
                :value="'Rp ' . number_format($monthlyIncome, 0, ',', '.')" 
                :badge="'Bulan Ini'"
                :sub="'Periode: ' . now()->translatedFormat('M Y')" 
                :toggle="true"
            />

            <x-metric-card 
                notch="cream"
                label="PENGELUARAN" 
                :value="'Rp ' . number_format($monthlyExpense, 0, ',', '.')" 
                :sub="'Periode: ' . now()->translatedFormat('M Y')" 
                :toggle="true"
            />

            <x-metric-card 
                :notch="$netCashFlow >= 0 ? 'green' : 'pink'"
                label="ARUS KAS BERSIH" 
                :value="($netCashFlow >= 0 ? '+' : '') . 'Rp ' . number_format($netCashFlow, 0, ',', '.')" 
                :sub="$netCashFlow >= 0 ? 'Surplus kas periode ini' : 'Defisit kas periode ini'" 
                :toggle="true"
            />

            @php
                $budgetProgress = $totalBudgetLimit > 0 ? min(100, (int) round(($totalBudgetSpent / $totalBudgetLimit) * 100)) : 0;
            @endphp
            <x-metric-card 
                notch="orange"
                label="ANGGARAN BULAN INI" 
                :progress="$budgetProgress"
                :value="'Rp ' . number_format($budgetRemaining, 0, ',', '.')" 
                :sub="$totalBudgetLimit > 0 ? round(($totalBudgetSpent / $totalBudgetLimit) * 100) . '% terpakai dari Rp ' . number_format($totalBudgetLimit, 0, ',', '.') : 'Belum diatur'" 
                :toggle="true"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <div class="lg:col-span-7">
            <div class="panel-card p-0 overflow-hidden h-full flex flex-col justify-between" data-privacy-scope="dashboard-recent-tx">
                <div>
                    <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-forest shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <h2 class="text-xs font-bold text-charcoal truncate">Transaksi Terkini</h2>
                            @if ($recentTransactions->isNotEmpty())
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-cream text-muted border border-line shrink-0">
                                    {{ $recentTransactions->count() }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                type="button" 
                                onclick="toggleCardPrivacy(this)" 
                                class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                                title="Sembunyikan / Tampilkan Nominal Transaksi Terkini"
                                aria-label="Sembunyikan atau tampilkan nominal transaksi terkini"
                            >
                                <span class="privacy-toggle-icon"></span>
                            </button>
                            <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                                Lihat semua &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="divide-y divide-line">
                        @forelse ($recentTransactions as $tx)
                            @php
                                $accEntry = $tx->entries->first();
                                $accName = $accEntry?->account?->name ?? 'Kas Utama';
                                $amount = $tx->totalAmount();
                            @endphp
                            <div class="px-4 sm:px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between hover:bg-cream/40 transition gap-1.5 sm:gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('transactions.show', $tx) }}" class="text-xs font-bold text-charcoal hover:text-forest truncate">
                                            {{ $tx->description ?: ($tx->category?->name ?? 'Transaksi Kas') }}
                                        </a>
                                        @if ($tx->isVoid())
                                            <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-semibold uppercase shrink-0">
                                                Batal
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-muted flex items-center flex-wrap gap-x-1.5 gap-y-0.5 mt-0.5">
                                        <span>{{ $tx->transaction_date->format('d M Y') }}</span>
                                        <span>&bull;</span>
                                        <span class="truncate">{{ $accName }}</span>
                                        @if ($tx->category)
                                            <span>&bull;</span>
                                            <span class="truncate font-medium text-charcoal/80">{{ $tx->category->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $tx->type === 'income' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900' : ($tx->type === 'expense' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900' : 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-900') }}">
                                        {{ $tx->type }}
                                    </span>
                                    <span class="text-xs font-bold font-mono privacy-balance {{ $tx->isVoid() ? 'line-through text-muted' : ($tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : ($tx->type === 'expense' ? 'text-rose-600 dark:text-rose-400' : 'text-charcoal')) }}" data-raw-value="{{ ($tx->type === 'income' ? '+' : ($tx->type === 'expense' ? '-' : '⇄ ')) . 'Rp ' . number_format($amount, 0, ',', '.') }}">
                                        {{ ($tx->type === 'income' ? '+' : ($tx->type === 'expense' ? '-' : '⇄ ')) . 'Rp ' . number_format($amount, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-muted">
                                Belum ada transaksi tercatat pada ruang ini.
                                <div class="mt-2.5">
                                    <button type="button" onclick="openQuickTransactionModal()" class="btn-arus-primary text-xs py-1 px-3">
                                        ⚡ Catat Transaksi Pertama
                                    </button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="px-4 sm:px-5 py-2.5 bg-sidebar/50 border-t border-line text-[11px] text-muted flex items-center justify-between">
                    <span>Menampilkan {{ $recentTransactions->count() }} transaksi terakhir</span>
                    <a href="{{ route('transactions.create') }}" class="text-forest font-semibold hover:underline">
                        + Tambah Transaksi
                    </a>
                </div>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="panel-card p-0 overflow-hidden h-full flex flex-col justify-between" data-privacy-scope="dashboard-accounts">
                <div>
                    <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-forest shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                </svg>
                            </span>
                            <h2 class="text-xs font-bold text-charcoal truncate">Rekening & Wallet</h2>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                type="button" 
                                onclick="toggleCardPrivacy(this)" 
                                class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                                title="Sembunyikan / Tampilkan Saldo Rekening & Dompet"
                                aria-label="Sembunyikan atau tampilkan saldo rekening"
                            >
                                <span class="privacy-toggle-icon"></span>
                            </button>
                            <a href="{{ route('accounts.create') }}" class="min-h-[38px] sm:min-h-0 sm:h-6 px-3 sm:px-2 text-[11px] sm:text-[10.5px] font-bold rounded bg-forest text-white hover:bg-forest-hover transition inline-flex items-center">
                                + Tambah
                            </a>
                        </div>
                    </div>

                    <div class="divide-y divide-line">
                        @forelse ($accounts as $acc)
                            @php
                                $accBal = $acc->currentBalance();
                                $typeIcon = match($acc->type) {
                                    'bank' => '🏦',
                                    'e-wallet' => '📱',
                                    'cash' => '💵',
                                    'investment' => '📈',
                                    'loan' => '🤝',
                                    default => '💳',
                                };
                                $availableLimit = $acc->availableLimit();
                            @endphp
                            <div class="px-4 sm:px-5 py-3 flex flex-col min-[420px]:flex-row min-[420px]:items-center justify-between hover:bg-cream/40 transition gap-2 sm:gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-cream border border-line flex items-center justify-center text-sm flex-shrink-0">
                                        {{ $typeIcon }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <a href="{{ route('accounts.show', $acc) }}" class="text-xs font-bold text-charcoal hover:text-forest truncate">
                                                {{ $acc->name }}
                                            </a>
                                            @if ($acc->isCreditCard() && $availableLimit !== null)
                                                <span class="text-[9.5px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.2 rounded uppercase">
                                                    Sisa Limit
                                                </span>
                                            @elseif ($acc->isLoan())
                                                <span class="text-[9.5px] font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.2 rounded uppercase">
                                                    Kewajiban
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10.5px] text-muted block uppercase">
                                            @if ($acc->isCreditCard())
                                                KARTU KREDIT {{ $acc->account_number ? '• ' . $acc->account_number : '' }}
                                            @elseif ($acc->isLoan())
                                                PINJAMAN {{ $acc->account_number ? '• ' . $acc->account_number : '' }}
                                            @else
                                                {{ $acc->type }} {{ $acc->account_number ? '• ' . $acc->account_number : '' }}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="text-left min-[420px]:text-right pl-10.5 min-[420px]:pl-0 shrink-0">
                                    @if ($acc->isCreditCard() && $availableLimit !== null)
                                        <span class="text-xs font-bold font-mono text-emerald-600 dark:text-emerald-400 block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($availableLimit, 0, ',', '.') }}">
                                            Rp {{ number_format($availableLimit, 0, ',', '.') }}
                                        </span>
                                        <div class="text-[10.5px] text-muted block">
                                            Terpakai: <span class="privacy-balance font-mono" data-raw-value="{{ 'Rp ' . number_format($acc->liabilityAmount(), 0, ',', '.') }}">Rp {{ number_format($acc->liabilityAmount(), 0, ',', '.') }}</span> &bull; 
                                            <a href="{{ route('accounts.show', $acc) }}" class="text-forest hover:underline">
                                                Mutasi &rarr;
                                            </a>
                                        </div>
                                    @elseif ($acc->isLoan())
                                        <span class="text-xs font-bold font-mono text-charcoal block privacy-balance" data-raw-value="{{ 'Rp ' . number_format(abs($accBal), 0, ',', '.') }}">
                                            Rp {{ number_format(abs($accBal), 0, ',', '.') }}
                                        </span>
                                        <div class="text-[10.5px] text-muted block">
                                            Sisa Hutang &bull; 
                                            <a href="{{ route('accounts.show', $acc) }}" class="text-forest hover:underline">
                                                Mutasi &rarr;
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-xs font-bold font-mono {{ $accBal < 0 ? 'text-red dark:text-rose-400' : ($accBal > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-charcoal') }} block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($accBal, 0, ',', '.') }}">
                                            Rp {{ number_format($accBal, 0, ',', '.') }}
                                        </span>
                                        <a href="{{ route('accounts.show', $acc) }}" class="text-[10.5px] text-forest hover:underline">
                                            Mutasi &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-muted">
                                Belum ada rekening kas atau bank terdaftar.
                                <div class="mt-2.5">
                                    <a href="{{ route('accounts.create') }}" class="btn-arus-primary text-xs py-1 px-3">
                                        + Tambah Rekening Pertama
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="px-4 sm:px-5 py-2.5 bg-sidebar/50 border-t border-line text-[11px] text-muted flex items-center justify-between">
                    <span>Total Saldo Terdaftar:</span>
                    <strong class="font-mono {{ $totalBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-400' }} privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalBalance, 0, ',', '.') }}">
                        Rp {{ number_format($totalBalance, 0, ',', '.') }}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline Event 30 Hari Mendatang & Jatuh Tempo --}}
    <div id="dashboard-timeline" class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-timeline">
        <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2.5">
            <div class="flex items-center flex-wrap gap-2 min-w-0">
                <span class="text-forest shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.253 3.75h18M4.5 7.5h15a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 19.5 22.5h-15A2.25 2.25 0 0 1 2.25 20.25V9.75A2.25 2.25 0 0 1 4.5 7.5Z" />
                    </svg>
                </span>
                <h2 class="text-xs font-bold text-charcoal">Timeline 30 Hari Mendatang &amp; Jatuh Tempo</h2>
                @if (!empty($timelineEvents))
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-cream text-muted border border-line shrink-0">
                        {{ count($timelineEvents) }}
                    </span>
                @endif
                @if (($overdueScheduleCount ?? 0) > 0)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 uppercase animate-pulse">
                        🚨 {{ $overdueScheduleCount }} Jatuh Tempo
                    </span>
                @else
                    <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-forest-light text-forest uppercase">
                        Agenda Kas
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @php
                    $timelineInflowCount = collect($timelineEvents)->where('flow', 'in')->count();
                    $timelineOutflowCount = collect($timelineEvents)->where('flow', 'out')->count();
                @endphp
                @if (!empty($timelineEvents))
                    <div class="inline-flex rounded-lg p-0.5 bg-cream dark:bg-line/40 border border-line text-[11px] font-semibold" role="tablist">
                        <button type="button" onclick="filterTimelineEvents('all', this)" class="timeline-filter-btn px-2.5 py-1 rounded-md bg-forest text-white transition cursor-pointer">Semua ({{ count($timelineEvents) }})</button>
                        @if (($overdueScheduleCount ?? 0) > 0)
                            <button type="button" id="timelineBtnOverdue" onclick="filterTimelineEvents('overdue', this)" class="timeline-filter-btn px-2.5 py-1 rounded-md text-rose-700 dark:text-rose-400 font-bold hover:bg-rose-100 dark:hover:bg-rose-950/50 transition cursor-pointer">
                                🚨 Jatuh Tempo ({{ $overdueScheduleCount }})
                            </button>
                        @endif
                        <button type="button" onclick="filterTimelineEvents('in', this)" class="timeline-filter-btn px-2.5 py-1 rounded-md text-muted hover:text-charcoal transition cursor-pointer">Masuk ({{ $timelineInflowCount }})</button>
                        <button type="button" onclick="filterTimelineEvents('out', this)" class="timeline-filter-btn px-2.5 py-1 rounded-md text-muted hover:text-charcoal transition cursor-pointer">Keluar ({{ $timelineOutflowCount }})</button>
                    </div>
                @endif

                <button 
                    type="button" 
                    onclick="toggleCardPrivacy(this)" 
                    class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                    title="Sembunyikan / Tampilkan Nominal Timeline"
                    aria-label="Sembunyikan atau tampilkan nominal timeline"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
                <a href="{{ route('projections.index', ['horizon' => 30]) }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                    Simulasi &rarr;
                </a>
            </div>
        </div>

        @if (($overdueScheduleCount ?? 0) > 0)
            <div class="px-4 py-2.5 bg-rose-50 dark:bg-rose-950/40 border-b border-rose-200 dark:border-rose-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-2 text-rose-800 dark:text-rose-200">
                    <span class="text-base shrink-0">🚨</span>
                    <span>
                        <strong>Jadwal Sudah Jatuh Tempo:</strong> Terdapat <strong>{{ $overdueScheduleCount }} jadwal/transaksi</strong> yang telah melewati tanggal jatuh tempo dan belum diselesaikan.
                    </span>
                </div>
                <button type="button" onclick="filterTimelineEvents('overdue', document.getElementById('timelineBtnOverdue'))" class="font-bold text-rose-700 dark:text-rose-300 hover:underline shrink-0 text-left sm:text-right cursor-pointer">
                    Lihat Yang Jatuh Tempo &rarr;
                </button>
            </div>
        @endif

        @if (!empty($timelineProjection))
            @php
                $totInflows = $timelineProjection['total_inflows'] ?? 0;
                $totOutflows = $timelineProjection['total_outflows'] ?? 0;
                $netChange = $timelineProjection['net_change'] ?? 0;
                $isDeficitRisk = $timelineProjection['is_deficit_risk'] ?? false;
                $deficitDate = $timelineProjection['deficit_date'] ?? null;
                $maxDeficit = $timelineProjection['max_deficit_amount'] ?? 0;
            @endphp

            @if ($isDeficitRisk && $deficitDate)
                <div class="px-4 py-2.5 bg-rose-50 dark:bg-rose-950/40 border-b border-rose-200 dark:border-rose-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2 text-rose-800 dark:text-rose-200">
                        <span class="text-base shrink-0">⚠️</span>
                        <span>
                            <strong>Perhatian Saldo:</strong> Diproyeksikan terjadi defisit kas pada <strong>{{ \Carbon\Carbon::parse($deficitDate)->translatedFormat('d M Y') }}</strong> (potensi minus <span class="font-mono font-bold privacy-balance" data-raw-value="Rp {{ number_format($maxDeficit, 0, ',', '.') }}">Rp {{ number_format($maxDeficit, 0, ',', '.') }}</span>).
                        </span>
                    </div>
                    <a href="{{ route('projections.index', ['horizon' => 30]) }}" class="font-bold text-rose-700 dark:text-rose-300 hover:underline shrink-0 text-left sm:text-right">
                        Cek Proyeksi &rarr;
                    </a>
                </div>
            @endif

            <div class="p-3 sm:p-4 grid grid-cols-2 sm:grid-cols-4 gap-2 border-b border-line bg-cream/40 text-center">
                <div class="p-2.5 rounded-lg bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Total Event</span>
                    <span class="text-xs sm:text-sm font-bold text-charcoal block mt-0.5">
                        {{ count($timelineEvents) }} Transaksi
                    </span>
                </div>
                <div class="p-2.5 rounded-lg bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Estimasi Masuk</span>
                    <span class="text-xs sm:text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400 block mt-0.5 privacy-balance" data-raw-value="{{ '+Rp ' . number_format($totInflows, 0, ',', '.') }}">
                        +Rp {{ number_format($totInflows, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-2.5 rounded-lg bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Estimasi Keluar</span>
                    <span class="text-xs sm:text-sm font-bold font-mono text-rose-600 dark:text-rose-400 block mt-0.5 privacy-balance" data-raw-value="{{ '-Rp ' . number_format($totOutflows, 0, ',', '.') }}">
                        -Rp {{ number_format($totOutflows, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-2.5 rounded-lg bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Perubahan Bersih</span>
                    <span class="text-xs sm:text-sm font-bold font-mono {{ $netChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} block mt-0.5 privacy-balance" data-raw-value="{{ ($netChange >= 0 ? '+' : '') . 'Rp ' . number_format($netChange, 0, ',', '.') }}">
                        {{ ($netChange >= 0 ? '+' : '') . 'Rp ' . number_format($netChange, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        @endif

        <div class="divide-y divide-line" id="timelineEventsContainer">
            @forelse ($timelineEvents as $idx => $evt)
                @php
                    $eventDate = \Carbon\Carbon::parse($evt['date'])->startOfDay();
                    $diffDays = (int) $now->copy()->startOfDay()->diffInDays($eventDate, false);
                    $isItemOverdue = !empty($evt['is_overdue']) || $diffDays < 0;

                    if ($isItemOverdue) {
                        $relativeLabel = 'Lewat ' . abs($diffDays) . ' hr (Jatuh Tempo)';
                        $relativeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-bold border border-rose-300';
                    } elseif ($diffDays === 0) {
                        $relativeLabel = 'Hari Ini';
                        $relativeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300';
                    } elseif ($diffDays === 1) {
                        $relativeLabel = 'Besok';
                        $relativeClass = 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300';
                    } elseif ($diffDays === 2) {
                        $relativeLabel = 'Lusa';
                        $relativeClass = 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300';
                    } else {
                        $relativeLabel = $diffDays . ' hr lagi';
                        $relativeClass = 'bg-cream text-muted dark:bg-line/50';
                    }

                    $badgeTheme = match($evt['source_type']) {
                        'planned_income' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                        'installment' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                        'short_plan' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
                        default => ($evt['flow'] === 'in' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300'),
                    };

                    $badgeIcon = match($evt['source_type']) {
                        'planned_income' => '💰',
                        'installment' => '🛡️',
                        'short_plan' => '📝',
                        default => '📅',
                    };
                    $isOverflow = $idx >= 6;
                @endphp
                <div 
                    class="timeline-event-row px-4 sm:px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 transition {{ $isOverflow ? 'timeline-event-overflow hidden' : '' }} {{ $isItemOverdue ? 'bg-rose-50/50 dark:bg-rose-950/20 border-l-4 border-rose-500 hover:bg-rose-50/80 dark:hover:bg-rose-950/40' : 'hover:bg-cream/40' }}" 
                    data-flow="{{ $evt['flow'] }}"
                    data-is-overdue="{{ $isItemOverdue ? 'true' : 'false' }}"
                >
                    <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="w-11 text-center py-1 px-1 rounded-md bg-panel border {{ $isItemOverdue ? 'border-rose-300 bg-rose-50 dark:bg-rose-950/50' : 'border-line' }} shrink-0">
                                <span class="block text-[11px] font-bold {{ $isItemOverdue ? 'text-rose-700 dark:text-rose-300' : 'text-charcoal' }} leading-none">{{ $eventDate->format('d') }}</span>
                                <span class="block text-[9.5px] font-semibold text-muted uppercase leading-tight">{{ $eventDate->translatedFormat('M') }}</span>
                            </div>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase shrink-0 {{ $relativeClass }}">
                                {{ $relativeLabel }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if ($isItemOverdue)
                                    <span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-rose-600 text-white uppercase shrink-0">
                                        Sudah Jatuh Tempo
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded text-[10px] font-bold shrink-0 {{ $badgeTheme }}">
                                    <span>{{ $badgeIcon }}</span>
                                    <span>{{ $evt['source_label'] }}</span>
                                </span>
                                @if (!empty($evt['detail_url']))
                                    <a href="{{ $evt['detail_url'] }}" class="text-xs font-bold text-charcoal hover:text-forest truncate">
                                        {{ $evt['title'] }}
                                    </a>
                                @else
                                    <span class="text-xs font-bold text-charcoal truncate">
                                        {{ $evt['title'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-muted flex items-center flex-wrap gap-x-1.5 gap-y-0.5 mt-0.5">
                                <span>{{ $evt['day_name'] }}, {{ $evt['date_formatted'] }}</span>
                                <span>&bull;</span>
                                <span class="truncate">{{ $evt['category_name'] }}</span>
                                <span>&bull;</span>
                                <span class="truncate">{{ $evt['account_name'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 text-left sm:text-right shrink-0 pl-14 sm:pl-0">
                        <div class="sm:text-right">
                            @if ($evt['flow'] === 'in')
                                <span class="text-xs font-bold font-mono text-emerald-600 dark:text-emerald-400 block privacy-balance" data-raw-value="{{ '+Rp ' . number_format($evt['amount'], 0, ',', '.') }}">
                                    +Rp {{ number_format($evt['amount'], 0, ',', '.') }}
                                </span>
                            @else
                                <span class="text-xs font-bold font-mono text-rose-600 dark:text-rose-400 block privacy-balance" data-raw-value="{{ '-Rp ' . number_format($evt['amount'], 0, ',', '.') }}">
                                    -Rp {{ number_format($evt['amount'], 0, ',', '.') }}
                                </span>
                            @endif
                            <div class="text-[10px] text-muted font-mono flex items-center gap-1 sm:justify-end mt-0.5">
                                <span>Proyeksi:</span>
                                <span class="privacy-balance font-semibold {{ $evt['is_negative'] ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-charcoal' }}" data-raw-value="{{ 'Rp ' . number_format($evt['running_balance'], 0, ',', '.') }}">
                                    Rp {{ number_format($evt['running_balance'], 0, ',', '.') }}
                                </span>
                                @if ($evt['is_negative'])
                                    <span class="text-[9px] font-bold text-rose-700 dark:text-rose-400 uppercase">Defisit</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-muted">
                    <div class="w-10 h-10 mx-auto rounded-full bg-cream dark:bg-line/40 flex items-center justify-center text-lg mb-2">
                        📅
                    </div>
                    <div class="font-bold text-charcoal text-sm mb-1">Tidak Ada Event Terjadwal</div>
                    <p class="max-w-md mx-auto text-muted mb-3">
                        Belum ada transaksi berulang, cicilan tempo, atau rencana pemasukan yang terjadwal.
                    </p>
                    <div class="flex items-center justify-center gap-2 flex-wrap">
                        <a href="{{ route('recurring.create') }}" class="btn-arus-secondary text-xs py-1 px-3">
                            + Jadwal Rutin
                        </a>
                        <a href="{{ route('projections.index', ['tab' => 'planned_incomes']) }}" class="btn-arus-primary text-xs py-1 px-3">
                            + Rencana Pemasukan
                        </a>
                    </div>
                </div>
            @endforelse

            <div id="timelineEmptyFilter" class="p-6 text-center text-xs text-muted hidden">
                Tidak ada event yang sesuai dengan filter yang dipilih.
            </div>
        </div>

        @if (count($timelineEvents) > 6)
            <div id="timelineExpandWrapper" class="px-4 sm:px-5 py-2.5 bg-sidebar/50 border-t border-line flex items-center justify-between text-xs">
                <button type="button" onclick="toggleTimelineExpand()" id="timelineExpandBtn" class="font-semibold text-forest hover:underline cursor-pointer inline-flex items-center gap-1">
                    <span>Tampilkan {{ count($timelineEvents) - 6 }} Event Lainnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <a href="{{ route('projections.index', ['horizon' => 30]) }}" class="text-muted hover:text-charcoal transition">
                    Lihat Selengkapnya di Proyeksi &rarr;
                </a>
            </div>
        @elseif (!empty($timelineEvents))
            <div class="px-4 sm:px-5 py-2.5 bg-sidebar/50 border-t border-line flex items-center justify-between text-[11px] text-muted">
                <span>Menampilkan seluruh {{ count($timelineEvents) }} jadwal &amp; event</span>
                <a href="{{ route('projections.index', ['horizon' => 30]) }}" class="text-forest font-semibold hover:underline">
                    Buka Halaman Proyeksi &rarr;
                </a>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-recurring">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center flex-wrap gap-2 min-w-0">
                    <span class="text-forest shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal">Jadwal &amp; Tagihan Berulang</h2>
                    <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 uppercase">Rutin &amp; Langganan</span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                        title="Sembunyikan / Tampilkan Nominal Tagihan Berulang"
                        aria-label="Sembunyikan atau tampilkan nominal tagihan berulang"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('recurring.index') }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

            <div class="p-4 border-b border-line bg-cream/40 grid grid-cols-3 gap-2 text-center">
                <div class="p-2 rounded bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Terlambat</span>
                    <span class="text-sm font-bold {{ ($recurringSummary['overdue_count'] ?? 0) > 0 ? 'text-rose-600' : 'text-charcoal' }}">
                        {{ $recurringSummary['overdue_count'] ?? 0 }}
                    </span>
                </div>
                <div class="p-2 rounded bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Hari Ini</span>
                    <span class="text-sm font-bold {{ ($recurringSummary['due_today_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-charcoal' }}">
                        {{ $recurringSummary['due_today_count'] ?? 0 }}
                    </span>
                </div>
                <div class="p-2 rounded bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Mendatang</span>
                    <span class="text-sm font-bold text-charcoal">
                        {{ $recurringSummary['upcoming_count'] ?? 0 }}
                    </span>
                </div>
            </div>

            <div class="divide-y divide-line">
                @php
                    $recItems = collect($recurringSummary['items'] ?? [])->take(4);
                @endphp
                @forelse ($recItems as $item)
                    <div class="px-4 sm:px-5 py-2.5 flex items-center justify-between text-xs">
                        <div class="min-w-0">
                            <span class="font-bold text-charcoal block truncate">{{ $item['name'] }}</span>
                            <span class="text-[10.5px] text-muted block">Jatuh Tempo: {{ \Carbon\Carbon::parse($item['due_date'])->format('d M Y') }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-mono font-bold block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($item['amount'], 0, ',', '.') }}">
                                Rp {{ number_format($item['amount'], 0, ',', '.') }}
                            </span>
                            <span class="text-[9.5px] px-1.5 py-0.2 rounded font-semibold uppercase {{ $item['status'] === 'overdue' ? 'bg-rose-100 text-rose-800' : ($item['status'] === 'due_today' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800') }}">
                                {{ $item['status'] === 'overdue' ? 'Terlambat' : ($item['status'] === 'due_today' ? 'Hari Ini' : 'Mendatang') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-muted">
                        Tidak ada tagihan atau jadwal berulang yang aktif.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-budgets">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-forest shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal truncate">Kontrol Anggaran Bulan Ini</h2>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                        title="Sembunyikan / Tampilkan Nominal Anggaran"
                        aria-label="Sembunyikan atau tampilkan nominal anggaran"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('budgets.index') }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                        Rincian &rarr;
                    </a>
                </div>
            </div>

            <div class="p-4 divide-y divide-line space-y-3">
                @forelse ($currentBudgets->take(4) as $b)
                    @php
                        $pct = $b->calculated_percentage ?? 0;
                        $isOver = $b->calculated_is_over ?? false;
                    @endphp
                    <div class="pt-2 first:pt-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5 text-xs mb-1">
                            <span class="font-bold text-charcoal truncate">{{ $b->category?->name ?? 'Umum' }}</span>
                            <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                <span class="privacy-balance font-bold {{ $isOver ? 'text-rose-600' : 'text-charcoal' }}" data-raw-value="{{ 'Rp ' . number_format($b->calculated_spent, 0, ',', '.') }}">
                                    Rp {{ number_format($b->calculated_spent, 0, ',', '.') }}
                                </span>
                                <span class="text-muted">/</span>
                                <span class="text-muted privacy-balance" data-raw-value="{{ 'Rp ' . number_format($b->limit_amount, 0, ',', '.') }}">
                                    Rp {{ number_format($b->limit_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-cream rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $isOver ? 'bg-rose-500' : ($pct > 80 ? 'bg-amber-500' : 'bg-forest') }}" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-muted">
                        Belum ada pos anggaran aktif untuk bulan ini.
                        <div class="mt-2">
                            <a href="{{ route('budgets.index') }}" class="text-forest font-bold hover:underline">
                                + Atur Anggaran Kategori
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-credit">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center flex-wrap gap-2 min-w-0">
                    <span class="text-forest shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal">Kewajiban Kredit &amp; Cicilan</h2>
                    <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 uppercase">Kartu Kredit</span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                        title="Sembunyikan / Tampilkan Kewajiban Kredit"
                        aria-label="Sembunyikan atau tampilkan kewajiban kredit"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('credit.index') }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

            <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-2 border-b border-line bg-cream/40">
                <div class="p-2.5 sm:p-2 rounded-lg sm:rounded bg-panel border border-line flex items-center justify-between sm:block sm:text-center">
                    <span class="block text-[10px] uppercase font-bold text-muted">Limit Gabungan</span>
                    <span class="text-xs font-bold font-mono text-charcoal block sm:mt-0.5 privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalCreditLimit, 0, ',', '.') }}">
                        Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-2.5 sm:p-2 rounded-lg sm:rounded bg-panel border border-line flex items-center justify-between sm:block sm:text-center">
                    <span class="block text-[10px] uppercase font-bold text-muted">Kewajiban Terpakai</span>
                    <span class="text-xs font-bold font-mono text-amber-600 block sm:mt-0.5 privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalCreditLiability, 0, ',', '.') }}">
                        Rp {{ number_format($totalCreditLiability, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-2.5 sm:p-2 rounded-lg sm:rounded bg-panel border border-line flex items-center justify-between sm:block sm:text-center">
                    <span class="block text-[10px] uppercase font-bold text-muted">Limit Tersedia</span>
                    <span class="text-xs font-bold font-mono text-forest block sm:mt-0.5 privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalAvailableCredit, 0, ',', '.') }}">
                        Rp {{ number_format($totalAvailableCredit, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="divide-y divide-line">
                @forelse ($upcomingInstallments as $inst)
                    @php
                        $dueStatus = $inst->dueStatus();
                    @endphp
                    <div class="px-4 sm:px-5 py-2.5 flex flex-wrap items-center justify-between text-xs hover:bg-cream/40 transition gap-2 sm:gap-3">
                        <div class="min-w-0 flex-1">
                            @if ($inst->installmentPlan)
                                <a href="{{ route('credit.plans.show', $inst->installmentPlan) }}" class="font-bold text-charcoal hover:text-forest block truncate">
                                    {{ $inst->installmentPlan->name }}
                                </a>
                            @else
                                <span class="font-bold text-charcoal block truncate">Cicilan</span>
                            @endif
                            <div class="text-[10.5px] text-muted flex items-center flex-wrap gap-x-1.5 gap-y-0.5 mt-0.5">
                                <span class="truncate">{{ $inst->installmentPlan?->creditAccount?->name }}</span>
                                <span>&bull;</span>
                                <span>Jatuh Tempo: {{ $inst->due_date?->format('d M Y') }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-mono font-bold block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($inst->total_amount, 0, ',', '.') }}">
                                Rp {{ number_format($inst->total_amount, 0, ',', '.') }}
                            </span>
                            <span class="text-[9.5px] px-1.5 py-0.2 rounded font-semibold uppercase {{ $dueStatus === 'overdue' ? 'bg-rose-100 text-rose-800' : ($dueStatus === 'due_today' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800') }}">
                                {{ $inst->dueStatusLabel() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-muted">
                        Tidak ada tagihan cicilan berjalan.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-goals">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-forest shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 0 0 2.997-3.188l-.34-4.425a3 3 0 0 0-2.993-2.762H8.836a3 3 0 0 0-2.993 2.762l-.34 4.425A3 3 0 0 0 8.497 14.25" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal truncate">Target Tabungan & Impian</h2>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0" 
                        title="Sembunyikan / Tampilkan Target Impian"
                        aria-label="Sembunyikan atau tampilkan target impian"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('goals.index') }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                        Lihat &rarr;
                    </a>
                </div>
            </div>

            <div class="p-4 divide-y divide-line space-y-3">
                @forelse ($activeGoals as $goal)
                    @php
                        $prog = $goal->percentage();
                    @endphp
                    <div class="pt-2 first:pt-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5 text-xs mb-1">
                            <a href="{{ route('goals.show', $goal) }}" class="font-bold text-charcoal hover:text-forest truncate">
                                {{ $goal->name }}
                            </a>
                            <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                <span class="privacy-balance font-bold text-forest" data-raw-value="{{ 'Rp ' . number_format($goal->current_amount, 0, ',', '.') }}">
                                    Rp {{ number_format($goal->current_amount, 0, ',', '.') }}
                                </span>
                                <span class="text-muted">/</span>
                                <span class="text-muted privacy-balance" data-raw-value="{{ 'Rp ' . number_format($goal->target_amount, 0, ',', '.') }}">
                                    Rp {{ number_format($goal->target_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-cream rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-forest" style="width: {{ $prog }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-muted">
                        Belum ada target tabungan aktif.
                        <div class="mt-2">
                            <a href="{{ route('goals.index') }}" class="text-forest font-bold hover:underline">
                                + Buat Target Tabungan Baru
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-short-plans">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-forest shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal truncate">Short Plan</h2>
                    @if ($shortPlanActiveCount > 0)
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-cream text-muted border border-line">
                            {{ $shortPlanActiveCount }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        onclick="toggleCardPrivacy(this)"
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -my-2 sm:my-0 rounded-lg sm:rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] shrink-0"
                        title="Sembunyikan / Tampilkan Nominal Short Plan"
                        aria-label="Sembunyikan atau tampilkan nominal short plan"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('short-plans.create') }}" class="min-h-[38px] sm:min-h-0 sm:h-6 px-3 sm:px-2 text-[11px] sm:text-[10.5px] font-bold rounded bg-forest text-white hover:bg-forest-hover transition inline-flex items-center">
                        + Rencana
                    </a>
                    <a href="{{ route('short-plans.index') }}" class="text-xs font-semibold text-forest hover:underline min-h-[40px] sm:min-h-0 inline-flex items-center">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

        @php
            $shortPlanCoverage = $totalBalance - $shortPlanActiveTotal;
        @endphp
        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-2.5 border-b border-line bg-cream/40">
            <div class="p-2.5 rounded-lg bg-panel border border-line flex items-center justify-between sm:block sm:text-center">
                <span class="text-[10px] uppercase font-bold text-muted block">Total Estimasi Aktif</span>
                <span class="text-xs font-bold font-mono text-charcoal block sm:mt-0.5 privacy-balance" data-raw-value="{{ 'Rp ' . number_format($shortPlanActiveTotal, 0, ',', '.') }}">
                    Rp {{ number_format($shortPlanActiveTotal, 0, ',', '.') }}
                </span>
            </div>
            <div class="p-2.5 rounded-lg bg-panel border border-line flex items-center justify-between sm:block sm:text-center">
                <span class="text-[10px] uppercase font-bold text-muted block">Mendesak / Dekat</span>
                <span class="text-xs font-bold {{ $shortPlanUrgentCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-charcoal' }} block sm:mt-0.5">
                    {{ $shortPlanUrgentCount }} Rencana
                </span>
            </div>
            <div class="p-2.5 rounded-lg bg-panel border border-line flex items-center justify-between sm:block sm:text-center">
                <span class="text-[10px] uppercase font-bold text-muted block">Kecukupan Saldo Cair</span>
                @if ($shortPlanCoverage >= 0)
                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 block sm:mt-0.5">
                        Cukup (+<span class="font-mono privacy-balance" data-raw-value="{{ 'Rp ' . number_format($shortPlanCoverage, 0, ',', '.') }}">Rp {{ number_format($shortPlanCoverage, 0, ',', '.') }}</span>)
                    </span>
                @else
                    <span class="text-xs font-bold text-rose-700 dark:text-rose-300 block sm:mt-0.5">
                        Kurang <span class="font-mono privacy-balance" data-raw-value="{{ 'Rp ' . number_format(abs($shortPlanCoverage), 0, ',', '.') }}">Rp {{ number_format(abs($shortPlanCoverage), 0, ',', '.') }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="divide-y divide-line">
            @forelse ($activeShortPlans as $plan)
                @php
                    $planItemsCount = $plan->items->count();
                    $planCheckedCount = $plan->items->where('is_checked', true)->count();
                @endphp
                <div class="px-4 sm:px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:bg-cream/40 transition">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center flex-wrap gap-2">
                            <a href="{{ route('short-plans.show', $plan) }}" class="text-xs font-bold text-charcoal hover:text-forest truncate">
                                {{ $plan->title }}
                            </a>
                            @include('short-plans.partials._badges', ['plan' => $plan, 'now' => $now])
                        </div>
                        <div class="text-[11px] text-muted flex items-center flex-wrap gap-x-2 gap-y-0.5 mt-1">
                            <span>{{ $plan->account?->name ?? 'Rekening belum dipilih' }}</span>
                            @if ($plan->category)
                                <span>&bull;</span>
                                <span>{{ $plan->category->name }}</span>
                            @endif
                            @if ($planItemsCount > 0)
                                <span>&bull;</span>
                                <span class="font-semibold text-forest dark:text-blue-300">{{ $planCheckedCount }}/{{ $planItemsCount }} item</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                        <span class="text-xs font-bold font-mono text-charcoal privacy-balance" data-raw-value="{{ 'Rp ' . number_format($plan->planned_amount, 0, ',', '.') }}">
                            Rp {{ number_format($plan->planned_amount, 0, ',', '.') }}
                        </span>
                        <a href="{{ route('short-plans.show', $plan) }}" class="text-[11px] font-bold text-forest hover:underline">
                            Detail &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-xs text-muted">
                    Belum ada rencana pengeluaran jangka pendek yang aktif.
                    <div class="mt-2">
                        <a href="{{ route('short-plans.create') }}" class="text-forest font-bold hover:underline">
                            + Buat Short Plan Pertama
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
    <script>
        let timelineExpanded = false;
        function toggleTimelineExpand() {
            timelineExpanded = !timelineExpanded;
            const currentFlow = window.currentTimelineFlow || 'all';
            document.querySelectorAll('.timeline-event-overflow').forEach(el => {
                if (timelineExpanded) {
                    if (currentFlow === 'all' || 
                        (currentFlow === 'overdue' && el.getAttribute('data-is-overdue') === 'true') || 
                        el.getAttribute('data-flow') === currentFlow) {
                        el.classList.remove('hidden');
                    }
                } else {
                    el.classList.add('hidden');
                }
            });
            const btn = document.getElementById('timelineExpandBtn');
            if (btn) {
                btn.innerHTML = timelineExpanded
                    ? '<span>Sembunyikan Sebagian</span> <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>'
                    : '<span>Tampilkan Selengkapnya</span> <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>';
            }
        }

        window.currentTimelineFlow = 'all';
        function filterTimelineEvents(flow, btn) {
            window.currentTimelineFlow = flow;
            document.querySelectorAll('.timeline-filter-btn').forEach(b => {
                b.classList.remove('bg-forest', 'text-white', 'bg-rose-600');
                b.classList.add('text-muted');
            });
            if (btn) {
                if (flow === 'overdue') {
                    btn.classList.add('bg-rose-600', 'text-white');
                } else {
                    btn.classList.add('bg-forest', 'text-white');
                }
                btn.classList.remove('text-muted');
            }

            const allRows = document.querySelectorAll('.timeline-event-row');
            let matchCount = 0;
            allRows.forEach((row) => {
                let matches = false;
                if (flow === 'all') {
                    matches = true;
                } else if (flow === 'overdue') {
                    matches = row.getAttribute('data-is-overdue') === 'true';
                } else {
                    matches = row.getAttribute('data-flow') === flow;
                }

                const isOverflow = row.classList.contains('timeline-event-overflow');
                if (matches) {
                    matchCount++;
                    if (!isOverflow || timelineExpanded || flow === 'overdue') {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                } else {
                    row.classList.add('hidden');
                }
            });

            const emptyNotice = document.getElementById('timelineEmptyFilter');
            if (emptyNotice) {
                if (matchCount === 0 && allRows.length > 0) {
                    emptyNotice.classList.remove('hidden');
                } else {
                    emptyNotice.classList.add('hidden');
                }
            }
        }
    </script>
    @endpush
</x-layouts.app>
