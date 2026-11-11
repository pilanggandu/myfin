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
                </div>
                <p class="text-xs text-muted mt-1 leading-normal">
                    Berikut ringkasan kondisi keuangan ruang <strong class="text-charcoal font-semibold">{{ $space?->name }}</strong> ({{ $space?->isPersonal() ? 'Personal' : 'Bersama' }}) yang perlu diperhatikan hari ini.
                </p>
            </div>
        </div>

        <div class="lg:col-span-5 grid grid-cols-2 gap-2.5">
            <a href="{{ route('transactions.index') }}" class="panel-card p-3 flex items-center gap-2.5 hover:bg-cream hover:border-sage transition cursor-pointer text-decoration-none group">
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
                class="privacy-card-toggle btn-arus-secondary inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold h-8.5 py-1" 
                title="Sembunyikan / Tampilkan Saldo Ringkasan"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Saldo Ringkasan</span>
            </button>

            <button 
                type="button" 
                onclick="openQuickTransactionModal()" 
                class="btn-arus-primary cursor-pointer text-xs font-bold h-8.5 py-1"
            >
                ⚡ Catat Cepat
            </button>

            <a 
                href="{{ route('transactions.create') }}" 
                class="btn-arus-secondary text-xs font-semibold h-8.5 py-1"
            >
                + Transaksi Baru
            </a>
        </div>
    </div>

    <div class="panel-card p-4 sm:p-5">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-line">
            <div class="flex items-center gap-2">
                <span class="text-forest">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </span>
                <h2 class="text-sm font-bold text-charcoal">Ringkasan Keuangan</h2>
            </div>
            <div class="inline-flex rounded-lg p-0.5 bg-cream border border-line text-xs font-semibold">
                <span class="px-2.5 py-1 rounded-md bg-forest text-white">Bulan Ini</span>
                <a href="{{ route('reports.index') }}" class="px-2.5 py-1 text-muted hover:text-charcoal transition text-decoration-none">Semua Periode</a>
            </div>
        </div>

        <div data-privacy-scope="dashboard-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            <x-metric-card 
                notch="blue"
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
                notch="pink"
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
                    <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-forest">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <h2 class="text-xs font-bold text-charcoal">Transaksi Terkini</h2>
                            @if ($recentTransactions->isNotEmpty())
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-cream text-muted border border-line">
                                    {{ $recentTransactions->count() }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                onclick="toggleCardPrivacy(this)" 
                                class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] flex-shrink-0" 
                                title="Sembunyikan / Tampilkan Nominal Transaksi Terkini"
                                aria-label="Sembunyikan atau tampilkan nominal transaksi terkini"
                            >
                                <span class="privacy-toggle-icon"></span>
                            </button>
                            <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-forest hover:underline">
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
                            <div class="px-4 sm:px-5 py-3 flex items-center justify-between hover:bg-cream/40 transition gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('transactions.show', $tx) }}" class="text-xs font-bold text-charcoal hover:text-forest truncate">
                                            {{ $tx->description ?: ($tx->category?->name ?? 'Transaksi Kas') }}
                                        </a>
                                        @if ($tx->isVoid())
                                            <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-semibold uppercase">
                                                Batal
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-muted flex items-center gap-1.5 mt-0.5 truncate">
                                        <span>{{ $tx->transaction_date->format('d M Y') }}</span>
                                        <span>&bull;</span>
                                        <span class="truncate">{{ $accName }}</span>
                                        @if ($tx->category)
                                            <span>&bull;</span>
                                            <span class="truncate font-medium text-charcoal/80">{{ $tx->category->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5 flex-shrink-0">
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
                    <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-forest">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                </svg>
                            </span>
                            <h2 class="text-xs font-bold text-charcoal">Rekening & Wallet</h2>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                onclick="toggleCardPrivacy(this)" 
                                class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] flex-shrink-0" 
                                title="Sembunyikan / Tampilkan Saldo Rekening & Dompet"
                                aria-label="Sembunyikan atau tampilkan saldo rekening"
                            >
                                <span class="privacy-toggle-icon"></span>
                            </button>
                            <a href="{{ route('accounts.create') }}" class="h-6 px-2 text-[10.5px] font-bold rounded bg-forest text-white hover:bg-forest-hover transition inline-flex items-center">
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
                            <div class="px-4 sm:px-5 py-3 flex items-center justify-between hover:bg-cream/40 transition gap-3">
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

                                <div class="text-right flex-shrink-0">
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
                                        <span class="text-xs font-bold font-mono text-charcoal block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($accBal, 0, ',', '.') }}">
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
                    <strong class="font-mono text-forest dark:text-blue-400 privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalBalance, 0, ',', '.') }}">
                        Rp {{ number_format($totalBalance, 0, ',', '.') }}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="dashboard-recurring">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-forest">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal">Jadwal &amp; Tagihan Berulang</h2>
                    <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 uppercase">Rutin &amp; Langganan</span>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] flex-shrink-0" 
                        title="Sembunyikan / Tampilkan Nominal Tagihan Berulang"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('recurring.index') }}" class="text-xs font-semibold text-forest hover:underline">
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
            <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-forest">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal">Kontrol Anggaran Bulan Ini</h2>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] flex-shrink-0" 
                        title="Sembunyikan / Tampilkan Nominal Anggaran"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('budgets.index') }}" class="text-xs font-semibold text-forest hover:underline">
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
                        <div class="flex items-center justify-between text-xs mb-1">
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
            <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-forest">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal">Kewajiban Kredit &amp; Cicilan</h2>
                    <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 uppercase">Kartu Kredit</span>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] flex-shrink-0" 
                        title="Sembunyikan / Tampilkan Kewajiban Kredit"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('credit.index') }}" class="text-xs font-semibold text-forest hover:underline">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

            <div class="p-4 grid grid-cols-3 gap-2 text-center border-b border-line bg-cream/40">
                <div class="p-2 rounded bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Limit Gabungan</span>
                    <span class="text-xs font-bold font-mono text-charcoal block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalCreditLimit, 0, ',', '.') }}">
                        Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-2 rounded bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Kewajiban Terpakai</span>
                    <span class="text-xs font-bold font-mono text-amber-600 block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalCreditLiability, 0, ',', '.') }}">
                        Rp {{ number_format($totalCreditLiability, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-2 rounded bg-panel border border-line">
                    <span class="block text-[10px] uppercase font-bold text-muted">Limit Tersedia</span>
                    <span class="text-xs font-bold font-mono text-forest block privacy-balance" data-raw-value="{{ 'Rp ' . number_format($totalAvailableCredit, 0, ',', '.') }}">
                        Rp {{ number_format($totalAvailableCredit, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="divide-y divide-line">
                @forelse ($upcomingInstallments as $inst)
                    @php
                        $dueStatus = $inst->dueStatus();
                    @endphp
                    <div class="px-4 sm:px-5 py-2.5 flex items-center justify-between text-xs hover:bg-cream/40 transition gap-3">
                        <div class="min-w-0 flex-1">
                            @if ($inst->installmentPlan)
                                <a href="{{ route('credit.plans.show', $inst->installmentPlan) }}" class="font-bold text-charcoal hover:text-forest block truncate">
                                    {{ $inst->installmentPlan->name }}
                                </a>
                            @else
                                <span class="font-bold text-charcoal block truncate">Cicilan</span>
                            @endif
                            <div class="text-[10.5px] text-muted flex items-center gap-1.5 mt-0.5 truncate">
                                <span>{{ $inst->installmentPlan?->creditAccount?->name }}</span>
                                <span>&bull;</span>
                                <span>Jatuh Tempo: {{ $inst->due_date?->format('d M Y') }}</span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
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
            <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-forest">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 0 0 2.997-3.188l-.34-4.425a3 3 0 0 0-2.993-2.762H8.836a3 3 0 0 0-2.993 2.762l-.34 4.425A3 3 0 0 0 8.497 14.25" />
                        </svg>
                    </span>
                    <h2 class="text-xs font-bold text-charcoal">Target Tabungan & Impian</h2>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10.5px] flex-shrink-0" 
                        title="Sembunyikan / Tampilkan Target Impian"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                    <a href="{{ route('goals.index') }}" class="text-xs font-semibold text-forest hover:underline">
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
                        <div class="flex items-center justify-between text-xs mb-1">
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
</x-layouts.app>
