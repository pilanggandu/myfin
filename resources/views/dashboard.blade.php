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
                    <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                        <span class="text-sm font-bold text-forest">Transaksi Terkini</span>
                        <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-forest hover:underline">
                            Lihat semua &rarr;
                        </a>
                    </div>

                    @if ($recentTransactions->isEmpty())
                        <div class="p-8 text-center text-xs text-muted">
                            Belum ada transaksi pada ruang ini.
                            <div class="mt-3">
                                <a href="{{ route('transactions.create') }}" class="btn-arus-primary inline-flex text-xs">
                                    Catat Transaksi Pertama
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                        <th class="py-2.5 px-5">Tanggal</th>
                                        <th class="py-2.5 px-4">Keterangan</th>
                                        <th class="py-2.5 px-4">Jenis</th>
                                        <th class="py-2.5 px-5 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($recentTransactions as $tx)
                                        @php
                                            $isInc = $tx->isIncome();
                                            $isExp = $tx->isExpense();
                                            $amt = $tx->totalAmount();
                                        @endphp
                                        <tr class="hover:bg-cream/20 transition-colors {{ $tx->isVoid() ? 'opacity-50 line-through' : '' }}">
                                            <td class="py-3 px-5 whitespace-nowrap text-muted">
                                                <div class="font-medium text-charcoal">{{ $tx->transaction_date->format('d M') }}</div>
                                                @if ($tx->formattedTime())
                                                    <div class="text-[10px] text-muted/70 font-mono">{{ $tx->formattedTime() }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-charcoal">
                                                    <a href="{{ route('transactions.show', $tx) }}" class="hover:text-forest hover:underline">
                                                        {{ $tx->description }}
                                                    </a>
                                                </div>
                                                <div class="text-[11px] text-muted">
                                                    {{ $tx->category?->name ?? 'Transfer' }}
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <x-badge variant="default">
                                                    {{ strtoupper($tx->type) }}
                                                </x-badge>
                                            </td>
                                            <td class="py-3 px-5 whitespace-nowrap text-right font-bold {{ $isInc ? 'text-forest' : ($isExp ? 'text-red' : 'text-charcoal') }}">
                                                <span class="privacy-balance" data-raw-value="{{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($amt, 0, ',', '.') }}">
                                                    {{ $isInc ? '+' : ($isExp ? '-' : '') }} Rp {{ number_format($amt, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-cream/20 border-t border-line text-right">
                    <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-forest hover:underline">
                        Buka Riwayat Catatan Transaksi &rarr;
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
