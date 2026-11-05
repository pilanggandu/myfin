<x-layouts.app title="Dashboard">
    <!-- Page Heading -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / RINGKASAN KEUANGAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Dashboard Keuangan
            </h1>
            <p class="text-xs text-muted">
                Ruang aktif: <strong class="text-charcoal">{{ $space?->name }}</strong> ({{ $space?->isPersonal() ? 'Ruang Personal' : 'Ruang Bersama' }}).
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <button 
                type="button" 
                onclick="toggleBalanceVisibility()" 
                class="privacy-toggle-btn btn-arus-secondary inline-flex items-center gap-1.5 cursor-pointer text-xs font-bold" 
                title="Sembunyikan / Tampilkan Saldo"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Saldo</span>
            </button>
            <button type="button" onclick="openQuickTransactionModal()" class="btn-arus-primary cursor-pointer">
                ⚡ Catat Cepat
            </button>
            <a href="{{ route('transactions.create') }}" class="btn-arus-secondary">
                + Form Lengkap
            </a>
            <a href="{{ route('accounts.create') }}" class="btn-arus-secondary">
                + Rekening Baru
            </a>
        </div>
    </div>

    <!-- Financial Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="TOTAL SALDO KAS & BANK" 
            :value="'Rp ' . number_format($totalBalance, 0, ',', '.')" 
            :dark="true" 
            :sub="$accounts->count() . ' rekening aktif terdaftar'" 
        />

        <x-metric-card 
            label="PEMASUKAN BULAN INI" 
            :value="'Rp ' . number_format($monthlyIncome, 0, ',', '.')" 
            :sub="'Periode: ' . now()->translatedFormat('F Y')" 
        />

        <x-metric-card 
            label="PENGELUARAN BULAN INI" 
            :value="'Rp ' . number_format($monthlyExpense, 0, ',', '.')" 
            :sub="'Periode: ' . now()->translatedFormat('F Y')" 
        />

        <x-metric-card 
            label="ARUS KAS BERSIH" 
            :value="($netCashFlow >= 0 ? '+' : '') . 'Rp ' . number_format($netCashFlow, 0, ',', '.')" 
            :sub="'Selisih kas masuk vs keluar'" 
        />
    </div>

    <!-- Main Grid: Recent Transactions + Account Balances -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Recent Transactions (2 cols on desktop) -->
        <div class="lg:col-span-2">
            <div class="panel-card p-0 overflow-hidden h-full flex flex-col justify-between">
                <div>
                    <!-- Widget Header -->
                    <div class="px-5 sm:px-6 py-3.5 border-b border-line flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-sm font-bold text-forest dark:text-emerald-400 leading-tight">Transaksi Terkini</h2>
                                    @if ($recentTransactions->isNotEmpty())
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-cream dark:bg-line/50 text-muted">
                                            {{ $recentTransactions->count() }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-muted leading-tight">Aktivitas arus kas & pencatatan terbaru</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                onclick="openQuickTransactionModal()" 
                                class="px-2.5 py-1 text-xs font-semibold text-forest dark:text-emerald-400 bg-forest-light/60 dark:bg-forest-light/20 hover:bg-forest hover:text-white dark:hover:bg-forest rounded-md transition cursor-pointer"
                                title="Catat Transaksi Cepat"
                            >
                                + Catat
                            </button>
                            <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-muted hover:text-forest dark:hover:text-emerald-400 transition inline-flex items-center gap-0.5">
                                <span>Semua</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    @if ($recentTransactions->isEmpty())
                        <div class="p-10 text-center space-y-3">
                            <div class="w-12 h-12 mx-auto rounded-full bg-cream dark:bg-line/30 flex items-center justify-center text-xl">
                                📝
                            </div>
                            <div class="font-bold text-charcoal text-sm">Belum Ada Transaksi</div>
                            <p class="text-xs text-muted max-w-sm mx-auto">
                                Belum ada catatan arus kas atau mutasi pada ruang ini. Mulai dengan mencatat transaksi harian Anda.
                            </p>
                            <div class="pt-1">
                                <a href="{{ route('transactions.create') }}" class="btn-arus-primary inline-flex text-xs">
                                    + Catat Transaksi Pertama
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- List Feed Aktivitas Transaksi Modern -->
                        <div class="divide-y divide-line">
                            @php
                                $spaceNow = $space ? $space->now() : now();
                                $todayDate = $spaceNow->toDateString();
                                $yesterdayDate = $spaceNow->copy()->subDay()->toDateString();
                            @endphp

                            @foreach ($recentTransactions as $tx)
                                @php
                                    $isInc = $tx->isIncome();
                                    $isExp = $tx->isExpense();
                                    $isTrans = $tx->isTransfer();
                                    $amt = $tx->totalAmount();
                                    $txDate = $tx->transaction_date;
                                    $txDateString = $txDate->toDateString();
                                    $isFuture = $txDateString > $todayDate;
                                    $isToday = $txDateString === $todayDate;
                                    $isYesterday = $txDateString === $yesterdayDate;
                                    $isDiffYear = $txDate->year !== $spaceNow->year;
                                    $isVoid = $tx->isVoid();

                                    $sourceAcc = $tx->sourceAccount();
                                    $destAcc = $tx->destinationAccount();
                                @endphp

                                <div class="px-5 sm:px-6 py-3.5 hover:bg-cream/30 dark:hover:bg-line/25 transition-colors group flex items-center justify-between gap-3 sm:gap-4 {{ $isVoid ? 'opacity-50 line-through' : '' }}">
                                    <!-- Sisi Kiri: Icon Tipe & Info Transaksi -->
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <!-- Avatar Icon Tipe Transaksi -->
                                        @if ($isInc)
                                            <div class="w-10 h-10 rounded-xl bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform" title="Pemasukan Kas">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                                </svg>
                                            </div>
                                        @elseif ($isExp)
                                            <div class="w-10 h-10 rounded-xl bg-red/10 dark:bg-red/20 text-red dark:text-rose-400 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform" title="Pengeluaran Kas">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-xl bg-cream dark:bg-line/50 text-charcoal flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform" title="Transfer Antar-Rekening">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                                </svg>
                                            </div>
                                        @endif

                                        <!-- Detail Teks -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <a href="{{ route('transactions.show', $tx) }}" class="font-bold text-sm text-charcoal group-hover:text-forest dark:group-hover:text-emerald-400 transition truncate hover:underline" title="{{ $tx->description }}">
                                                    {{ $tx->description }}
                                                </a>

                                                @if ($tx->installmentPlan)
                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 border border-forest/20" title="Pembelian Cicilan">
                                                        🛡️ Cicilan {{ $tx->installmentPlan->tenor }}x
                                                    </span>
                                                @endif

                                                @if ($tx->member)
                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-bold text-white shadow-2xs" style="background-color: {{ $tx->member->color ?: '#0f4c3a' }};">
                                                        {{ $tx->member->name }}
                                                    </span>
                                                @endif

                                                @if ($isVoid)
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-muted/20 text-muted">
                                                        VOID
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Metadata: Kategori & Rekening -->
                                            <div class="flex items-center gap-1.5 text-xs text-muted mt-0.5 flex-wrap">
                                                @if ($tx->category)
                                                    <span class="font-medium text-charcoal/80 dark:text-muted">{{ $tx->category->name }}</span>
                                                @else
                                                    <span class="font-medium text-charcoal/80 dark:text-muted">Transfer</span>
                                                @endif

                                                <span class="text-line">&bull;</span>

                                                @if ($isTrans)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono text-muted">
                                                        <span>{{ $sourceAcc?->name ?? 'Kas' }}</span>
                                                        <span class="text-line">&rarr;</span>
                                                        <span>{{ $destAcc?->name ?? 'Kas' }}</span>
                                                    </span>
                                                @elseif ($isExp)
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-muted">
                                                        <span>💳 {{ $sourceAcc?->name ?? 'Rekening' }}</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-muted">
                                                        <span>💳 {{ $destAcc?->name ?? 'Rekening' }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sisi Kanan: Tanggal & Nominal -->
                                    <div class="text-right flex-shrink-0 flex flex-col items-end justify-center">
                                        <!-- Nominal -->
                                        <div class="font-mono text-sm sm:text-base font-bold {{ $isInc ? 'text-forest dark:text-emerald-400' : ($isExp ? 'text-red dark:text-rose-400' : 'text-charcoal') }}">
                                            <span class="privacy-balance" data-raw-value="{{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($amt, 0, ',', '.') }}">
                                                {{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($amt, 0, ',', '.') }}
                                            </span>
                                        </div>

                                        <!-- Indikator Tanggal yang Jelas & Informatif -->
                                        <div class="mt-0.5">
                                            @if ($isFuture)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30" title="Tanggal transaksi tercatat di masa depan (tahun {{ $txDate->year }})">
                                                    <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                    </svg>
                                                    <span>{{ $txDate->translatedFormat('d M Y') }}</span>
                                                    <span>&bull; Mendatang</span>
                                                </span>
                                            @elseif ($isToday)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span>Hari Ini</span>
                                                    @if ($tx->formattedTime())
                                                        <span class="text-muted/70 font-mono text-[10px]">({{ $tx->formattedTime() }})</span>
                                                    @endif
                                                </span>
                                            @elseif ($isYesterday)
                                                <span class="text-[11px] font-medium text-muted">
                                                    <span>Kemarin</span>
                                                    @if ($tx->formattedTime())
                                                        <span class="text-muted/70 font-mono text-[10px]">({{ $tx->formattedTime() }})</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-[11px] font-medium text-muted">
                                                    <span>{{ $txDate->translatedFormat($isDiffYear ? 'd M Y' : 'd M') }}</span>
                                                    @if ($tx->formattedTime())
                                                        <span class="text-muted/70 font-mono text-[10px]">({{ $tx->formattedTime() }})</span>
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Widget Footer -->
                <div class="p-3 bg-cream/20 border-t border-line flex items-center justify-between text-xs">
                    <span class="text-[11px] text-muted">
                        Menampilkan {{ $recentTransactions->count() }} mutasi keuangan terkini
                    </span>
                    <a href="{{ route('transactions.index') }}" class="font-bold text-forest dark:text-emerald-400 hover:underline inline-flex items-center gap-1">
                        <span>Buka Catatan Transaksi Lengkap</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Accounts Overview (1 col on desktop) -->
        <div>
            <div class="panel-card p-0 overflow-hidden h-full flex flex-col justify-between">
                <div>
                    <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-forest">Rekening & Dompet</span>
                            <button 
                                type="button" 
                                onclick="toggleBalanceVisibility()" 
                                class="privacy-toggle-btn w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs" 
                                title="Sembunyikan / Tampilkan Saldo"
                            >
                                <span class="privacy-toggle-icon"></span>
                            </button>
                        </div>
                        <a href="{{ route('accounts.index') }}" class="text-xs font-bold text-forest hover:underline">
                            Kelola &rarr;
                        </a>
                    </div>

                    @if ($accounts->isEmpty())
                        <div class="p-8 text-center text-xs text-muted">
                            Belum ada rekening aktif.
                            <div class="mt-3">
                                <a href="{{ route('accounts.create') }}" class="btn-arus-primary inline-flex text-xs">
                                    Tambah Rekening
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="divide-y divide-line">
                            @foreach ($accounts as $acc)
                                <a href="{{ route('accounts.show', $acc) }}" class="px-5 py-3 flex items-center justify-between hover:bg-cream/20 transition-colors block">
                                    <div>
                                        <div class="font-bold text-xs text-charcoal">
                                            {{ $acc->name }}
                                        </div>
                                        <div class="text-[10px] text-muted uppercase font-bold tracking-wider">
                                            {{ $acc->type }}
                                        </div>
                                    </div>
                                    <div class="text-right font-mono text-xs font-bold text-forest">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }}">
                                            Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-cream/20 border-t border-line text-right">
                    <a href="{{ route('accounts.create') }}" class="text-xs font-bold text-forest hover:underline">
                        + Tambah Rekening Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Jadwal & Tagihan Berulang -->
    <div class="panel-card p-0 overflow-hidden mb-6">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-forest/10 text-forest flex items-center justify-center text-xs font-bold">🔄</span>
                <span class="text-sm font-bold text-forest">Jadwal & Tagihan Berulang</span>
                @if (($recurringSummary['total_due_count'] ?? 0) > 0)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                        {{ $recurringSummary['total_due_count'] }} Perlu Konfirmasi
                    </span>
                @endif
            </div>
            <a href="{{ route('recurring.index') }}" class="text-xs font-bold text-forest hover:underline">
                Kelola Semua Jadwal &rarr;
            </a>
        </div>

        @if (empty($recurringSummary['items']))
            <div class="p-6 text-center text-xs text-muted">
                Belum ada jadwal berulang aktif. Otomatisasi pengingat tagihan bulanan atau transfer rutin.
                <div class="mt-3">
                    <a href="{{ route('recurring.create') }}" class="btn-arus-primary inline-flex text-xs">
                        + Buat Jadwal Berulang
                    </a>
                </div>
            </div>
        @else
            <div class="divide-y divide-line">
                @foreach ($recurringSummary['items'] as $rec)
                    @php
                        $recDueStatus = $rec->dueStatus($space?->timezone ?? 'Asia/Jakarta');
                    @endphp
                    <div class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-cream/40 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold {{ $rec->isExpense() ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-slate-500/10 text-charcoal' }}">
                                {{ $rec->isExpense() ? '🏷️' : '⇄' }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('recurring.show', $rec) }}" class="font-bold text-xs text-charcoal hover:text-forest transition">
                                        {{ $rec->name }}
                                    </a>
                                    @if ($rec->member)
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full text-white" style="background-color: {{ $rec->member->color ?: '#0f4c3a' }};">
                                            {{ $rec->member->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-muted flex items-center gap-1.5 mt-0.5">
                                    <span>Jatuh tempo: <strong class="text-charcoal">{{ $rec->next_due_date->format('d M Y') }}</strong></span>
                                    <span>&bull;</span>
                                    <span>{{ $rec->sourceAccount?->name }}</span>
                                    @if ($rec->isTransfer())
                                        <span>&rarr; {{ $rec->destinationAccount?->name }}</span>
                                    @else
                                        <span>({{ $rec->category?->name }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3">
                            <div class="text-right">
                                <div class="font-mono font-bold text-xs text-charcoal">
                                    @if ($rec->is_variable_amount)
                                        <span class="text-[11px] text-muted italic font-normal">Fleksibel</span>
                                    @else
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($rec->amount, 0, ',', '.') }}">
                                            Rp {{ number_format($rec->amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    @if ($recDueStatus === 'overdue')
                                        <span class="text-[9px] font-bold text-rose-500 uppercase tracking-wider">Terlambat</span>
                                    @elseif ($recDueStatus === 'due')
                                        <span class="text-[9px] font-bold text-amber-500 uppercase tracking-wider">Jatuh Tempo Hari Ini</span>
                                    @else
                                        <span class="text-[9px] text-muted uppercase tracking-wider">Mendatang</span>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('recurring.show', $rec) }}" class="btn-arus-primary text-xs font-bold px-3 py-1.5 whitespace-nowrap">
                                Rincian & Catat
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Ringkasan Anggaran Bulan Ini -->
    <div class="panel-card p-0 overflow-hidden mb-6">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-forest">Kontrol Anggaran Bulan Ini</span>
                <span class="text-xs text-muted">({{ now()->translatedFormat('F Y') }})</span>
            </div>
            <a href="{{ route('budgets.index') }}" class="text-xs font-bold text-forest hover:underline">
                Kelola Anggaran &rarr;
            </a>
        </div>

        @if ($currentBudgets->isEmpty())
            <div class="p-6 text-center text-xs text-muted">
                Belum ada anggaran pengeluaran yang dipasang untuk bulan ini. Pasang anggaran untuk mengontrol pengeluaran kategori.
                <div class="mt-3">
                    <a href="{{ route('budgets.index') }}" class="btn-arus-primary inline-flex text-xs">
                        Pasang Anggaran Bulan Ini
                    </a>
                </div>
            </div>
        @else
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 pb-4 border-b border-line text-xs">
                    <div>
                        <span class="text-muted block text-[11px] uppercase font-bold">Total Kuota Anggaran</span>
                        <span class="font-mono font-bold text-sm text-charcoal">
                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalBudgetLimit, 0, ',', '.') }}">Rp {{ number_format($totalBudgetLimit, 0, ',', '.') }}</span>
                        </span>
                        <span class="text-[10px] text-muted block mt-0.5">{{ $currentBudgets->count() }} Kategori Beranggaran</span>
                    </div>
                    <div>
                        <span class="text-muted block text-[11px] uppercase font-bold">Realisasi Pos Anggaran</span>
                        <span class="font-mono font-bold text-sm text-charcoal">
                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalBudgetSpent, 0, ',', '.') }}">Rp {{ number_format($totalBudgetSpent, 0, ',', '.') }}</span>
                        </span>
                        <span class="text-[10px] text-muted block mt-0.5" title="Total seluruh beban kas bulan ini: Rp {{ number_format($monthlyExpense, 0, ',', '.') }}">
                            @if ($unbudgetedExpense > 0)
                                +<span class="privacy-balance" data-raw-value="Rp {{ number_format($unbudgetedExpense, 0, ',', '.') }}">Rp {{ number_format($unbudgetedExpense, 0, ',', '.') }}</span> di luar pos anggaran
                            @else
                                Seluruh pengeluaran masuk pos anggaran
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-muted block text-[11px] uppercase font-bold">Sisa Kuota Anggaran</span>
                        <span class="font-mono font-bold text-sm {{ $budgetRemaining < 0 ? 'text-red' : 'text-forest' }}">
                            <span class="privacy-balance" data-raw-value="{{ $budgetRemaining < 0 ? '-' : '' }}Rp {{ number_format(abs($budgetRemaining), 0, ',', '.') }}">{{ $budgetRemaining < 0 ? '-' : '' }}Rp {{ number_format(abs($budgetRemaining), 0, ',', '.') }}</span>
                        </span>
                        <span class="text-[10px] text-muted block mt-0.5">
                            {{ $budgetRemaining >= 0 ? 'Sisa batas aman belanja' : 'Defisit melebihi limit' }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($currentBudgets->take(6) as $bg)
                        @php
                            $pct = $bg->calculated_percentage;
                            $over = $bg->calculated_is_over;
                            $barCl = $over ? 'bg-red' : ($pct >= 80 ? 'bg-amber-500' : 'bg-forest');
                        @endphp
                        <div class="p-3 rounded-lg border border-line bg-sidebar/40 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-charcoal">{{ $bg->category->name }}</span>
                                <span class="text-[11px] font-bold {{ $over ? 'text-red' : 'text-muted' }}">
                                    {{ $pct }}%
                                </span>
                            </div>
                            <div class="w-full bg-cream/60 dark:bg-sidebar/80 rounded-full h-1.5 overflow-hidden border border-line/40">
                                <div class="{{ $barCl }} h-1.5 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-muted">
                                <span>Terpakai: <span class="privacy-balance" data-raw-value="Rp {{ number_format($bg->calculated_spent, 0, ',', '.') }}">Rp {{ number_format($bg->calculated_spent, 0, ',', '.') }}</span></span>
                                <span>Limit: <span class="privacy-balance" data-raw-value="Rp {{ number_format($bg->limit_amount, 0, ',', '.') }}">Rp {{ number_format($bg->limit_amount, 0, ',', '.') }}</span></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Ringkasan Kewajiban Kredit & Cicilan -->
    <div class="panel-card p-0 overflow-hidden mb-6">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-forest">Kewajiban Kredit & Cicilan</span>
                @if ($totalCreditLiability > 0)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                        Kewajiban: Rp {{ number_format($totalCreditLiability, 0, ',', '.') }}
                    </span>
                @endif
            </div>
            <a href="{{ route('credit.index') }}" class="text-xs font-bold text-forest hover:underline">
                Kelola Kredit &rarr;
            </a>
        </div>

        <div class="p-5 sm:px-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div class="p-3.5 rounded-lg border border-line bg-sidebar/40">
                    <span class="text-muted block text-[11px] uppercase font-bold">Total Limit Kredit</span>
                    <span class="font-mono font-bold text-sm text-charcoal">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}">Rp {{ number_format($totalCreditLimit, 0, ',', '.') }}</span>
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">{{ $creditAccounts->count() }} akun terdaftar</span>
                </div>
                <div class="p-3.5 rounded-lg border border-line bg-sidebar/40">
                    <span class="text-muted block text-[11px] uppercase font-bold">Kewajiban Terpakai</span>
                    <span class="font-mono font-bold text-sm text-amber-600 dark:text-amber-400">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalCreditLiability, 0, ',', '.') }}">Rp {{ number_format($totalCreditLiability, 0, ',', '.') }}</span>
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">Pokok hutang belum lunas</span>
                </div>
                <div class="p-3.5 rounded-lg border border-line bg-sidebar/40">
                    <span class="text-muted block text-[11px] uppercase font-bold">Sisa Limit Tersedia</span>
                    <span class="font-mono font-bold text-sm text-forest">
                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($totalAvailableCredit, 0, ',', '.') }}">Rp {{ number_format($totalAvailableCredit, 0, ',', '.') }}</span>
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">Dapat digunakan belanja</span>
                </div>
            </div>

            @if ($upcomingInstallments->isNotEmpty())
                <div class="border-t border-line/60 pt-3">
                    <span class="text-xs font-bold text-charcoal block mb-2">Tagihan Cicilan Mendatang:</span>
                    <div class="space-y-2">
                        @foreach ($upcomingInstallments as $inst)
                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-cream/30 dark:bg-sidebar/50 border border-line/40">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-forest">#{{ $inst->installment_number }}</span>
                                    <span class="text-charcoal font-medium">{{ $inst->installmentPlan->name }}</span>
                                    <span class="text-muted">({{ $inst->installmentPlan->creditAccount->name }})</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-muted">Jatuh tempo: <strong>{{ $inst->due_date->format('d M Y') }}</strong></span>
                                    <span class="font-mono font-bold text-charcoal">{{ $inst->formatted_principal }}</span>
                                    <a href="{{ route('credit.plans.show', $inst->installmentPlan) }}" class="text-xs text-forest hover:underline font-bold">
                                        Bayar &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-xs text-muted text-center py-2">
                    Tidak ada jadwal tagihan cicilan yang pending saat ini.
                </div>
            @endif
        </div>
    </div>

    <!-- Navigation Shortcuts -->
    <x-panel eyebrow="PINTASAN FITUR ARUS">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <a href="{{ route('transactions.index') }}" class="p-4 rounded-xl border border-line bg-cream/40 hover:bg-forest-light hover:border-sage transition block group">
                <div class="font-bold text-sm text-forest group-hover:underline">Buku Transaksi</div>
                <p class="text-xs text-muted mt-1 leading-normal m-0">Filter, cari, serta telusuri catatan kas masuk, keluar, dan mutasi saldo.</p>
            </a>

            <a href="{{ route('credit.index') }}" class="p-4 rounded-xl border border-line bg-cream/40 hover:bg-forest-light hover:border-sage transition block group">
                <div class="font-bold text-sm text-forest group-hover:underline">Kredit & Cicilan</div>
                <p class="text-xs text-muted mt-1 leading-normal m-0">Kelola kartu kredit, paylater, limit, dan cicilan terjadwal.</p>
            </a>

            <a href="{{ route('budgets.index') }}" class="p-4 rounded-xl border border-line bg-cream/40 hover:bg-forest-light hover:border-sage transition block group">
                <div class="font-bold text-sm text-forest group-hover:underline">Anggaran Bulanan</div>
                <p class="text-xs text-muted mt-1 leading-normal m-0">Atur batasan pengeluaran kategori dan pantau realisasi belanja.</p>
            </a>

            <a href="{{ route('reports.index') }}" class="p-4 rounded-xl border border-line bg-cream/40 hover:bg-forest-light hover:border-sage transition block group">
                <div class="font-bold text-sm text-forest group-hover:underline">Laporan Keuangan</div>
                <p class="text-xs text-muted mt-1 leading-normal m-0">Analisis arus kas berkala dan sebaran pengeluaran per kategori.</p>
            </a>

            <a href="{{ route('categories.index') }}" class="p-4 rounded-xl border border-line bg-cream/40 hover:bg-forest-light hover:border-sage transition block group">
                <div class="font-bold text-sm text-forest group-hover:underline">Kategori Keuangan</div>
                <p class="text-xs text-muted mt-1 leading-normal m-0">Atur kategori pemasukan dan pengeluaran agar pembukuan rapi.</p>
            </a>
        </div>
    </x-panel>
</x-layouts.app>
