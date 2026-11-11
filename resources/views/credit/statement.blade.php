<x-layouts.app :title="'Lembar Tagihan: ' . $creditAccount->name . ' - ' . $statement['month_name']">
    <!-- Back Link & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('credit.index', ['tab' => 'accounts']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-charcoal transition mb-2">
                <span>←</span>
                <span>Kembali ke Daftar Akun Kredit & Kartu</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider {{ $statement['is_fully_paid'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($statement['total_pending'] > 0 ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400' : 'bg-muted/10 text-muted') }}">
                    {{ $statement['is_fully_paid'] ? 'Tagihan Lunas' : ($statement['total_pending'] > 0 ? 'Menunggu Pembayaran' : 'Tidak Ada Tagihan') }}
                </span>
                <span class="text-xs text-muted">{{ $creditAccount->name }}</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl text-charcoal font-medium mt-1">
                Lembar Tagihan: {{ $statement['month_name'] }}
            </h1>
        </div>

        <!-- Month Switcher Dropdown -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <form method="GET" action="{{ route('credit.accounts.statement', $creditAccount) }}" class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
                <label for="month" class="text-xs text-muted whitespace-nowrap font-semibold">Periode Tagihan:</label>
                <select 
                    name="month" 
                    id="month" 
                    onchange="this.form.submit()" 
                    class="w-full sm:w-auto min-h-[44px] sm:min-h-0 px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal font-semibold focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer shadow-xs"
                >
                    @foreach ($availableMonths as $m)
                        <option value="{{ $m['key'] }}" {{ $month === $m['key'] ? 'selected' : '' }}>
                            {{ $m['label'] }} {{ $m['is_current'] ? '(Bulan Ini)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl border border-emerald-500/30 bg-emerald-50/60 dark:bg-emerald-950/20 text-xs text-emerald-800 dark:text-emerald-300 font-medium">
            ✅ {{ session('status') }}
        </div>
    @endif

    <!-- Card Lembar Tagihan Bank (Billing Statement Summary) -->
    <div class="panel-card p-6 mb-6 relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-line">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">💳</span>
                    <h2 class="font-serif text-xl text-charcoal font-semibold">{{ $creditAccount->name }}</h2>
                    <span class="text-[11px] px-2 py-0.5 rounded font-mono font-bold bg-cream text-muted uppercase">
                        {{ str_replace('_', ' ', $creditAccount->type) }}
                    </span>
                </div>
                <p class="text-xs text-muted">
                    Semua transaksi ritel dan cicilan berjalan pada siklus ini dikonsolidasi menjadi satu lembar tagihan.
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                <div class="p-3 rounded-lg border border-line/60 bg-cream/30">
                    <span class="text-muted block text-[10px] uppercase font-bold">Cetak Tagihan</span>
                    <span class="font-semibold text-charcoal font-mono mt-0.5 block">
                        {{ $statement['cycle_date'] ? $statement['cycle_date']->format('d M Y') : 'Tgl ' . ($creditAccount->billing_cycle_day ?? '-') }}
                    </span>
                </div>
                <div class="p-3 rounded-lg border border-line/60 bg-cream/30">
                    <span class="text-muted block text-[10px] uppercase font-bold">Jatuh Tempo</span>
                    <span class="font-semibold text-charcoal font-mono mt-0.5 block">
                        {{ $statement['due_date'] ? $statement['due_date']->format('d M Y') : 'Tgl ' . ($creditAccount->due_day ?? '-') }}
                    </span>
                </div>
                <div class="p-3 rounded-lg border border-line/60 bg-cream/30 col-span-2 sm:col-span-1">
                    <span class="text-muted block text-[10px] uppercase font-bold">Status Pembayaran</span>
                    @if ($statement['is_fully_paid'])
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 block">LUNAS</span>
                    @elseif ($statement['total_pending'] > 0)
                        <span class="font-bold text-amber-600 dark:text-amber-400 mt-0.5 block">BELUM LUNAS</span>
                    @else
                        <span class="font-semibold text-muted mt-0.5 block">NIHIL</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4 Kolom Angka Ringkasan -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6">
            <div>
                <span class="text-xs text-muted block">Total Tagihan Tercetak</span>
                <span class="text-xl font-serif font-bold text-charcoal mt-1 block">
                    <span class="privacy-balance" data-raw-value="{{ $statement['formatted_total_billed'] }}">{{ $statement['formatted_total_billed'] }}</span>
                </span>
                <span class="text-[11px] text-muted">{{ $statement['item_count'] }} transaksi / cicilan</span>
            </div>

            <div>
                <span class="text-xs text-muted block">Sudah Dibayar</span>
                <span class="text-xl font-serif font-bold text-forest mt-1 block">
                    <span class="privacy-balance" data-raw-value="{{ $statement['formatted_total_paid'] }}">{{ $statement['formatted_total_paid'] }}</span>
                </span>
                <span class="text-[11px] text-muted">{{ $statement['item_count'] - $statement['pending_count'] }} item lunas</span>
            </div>

            <div>
                <span class="text-xs text-muted block">Sisa Belum Dibayar</span>
                <span class="text-xl font-serif font-bold {{ $statement['total_pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-charcoal' }} mt-1 block">
                    <span class="privacy-balance" data-raw-value="{{ $statement['formatted_total_pending'] }}">{{ $statement['formatted_total_pending'] }}</span>
                </span>
                <span class="text-[11px] text-muted">{{ $statement['pending_count'] }} item menunggu pelunasan</span>
            </div>

            <div>
                <span class="text-xs text-muted block">Sisa Limit Kartu</span>
                <span class="text-xl font-serif font-bold text-forest mt-1 block">
                    <span class="privacy-balance" data-raw-value="{{ $creditAccount->formatted_available_limit }}">{{ $creditAccount->formatted_available_limit }}</span>
                </span>
                <span class="text-[11px] text-muted">Dari limit <span class="privacy-balance" data-raw-value="{{ $creditAccount->formatted_limit }}">{{ $creditAccount->formatted_limit }}</span></span>
            </div>
        </div>
    </div>

    @if ($statement['total_pending'] > 0)
        <!-- Fast Payment Box: Bayar Seluruh Tagihan Sekaligus (1x Klik) -->
        <div class="panel-card p-6 mb-6 border-forest/30 bg-forest/5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-forest/15 mb-4">
                <div>
                    <h3 class="font-serif text-lg text-charcoal font-medium flex items-center gap-2">
                        <span>⚡</span>
                        <span>Bayar Tagihan Bulan Ini Sekaligus</span>
                    </h3>
                    <p class="text-xs text-muted">
                        Membayar total tagihan dalam <strong>1 kali pemotongan kas/bank</strong> (sesuai mutasi m-banking), dan otomatis menandai seluruh <strong>{{ $statement['pending_count'] }} item cicilan</strong> di bawah sebagai lunas.
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-muted block">Total Yang Perlu Dibayar:</span>
                    <span class="font-serif text-2xl font-bold text-forest"><span class="privacy-balance" data-raw-value="{{ $statement['formatted_total_pending'] }}">{{ $statement['formatted_total_pending'] }}</span></span>
                </div>
            </div>

            <form method="POST" action="{{ route('credit.accounts.payBill', $creditAccount) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="redirect_to" value="statement">
                <input type="hidden" name="month" value="{{ $month }}">

                @foreach ($statement['installments']->where('status', 'pending') as $pendingInst)
                    <input type="hidden" name="installment_ids[]" value="{{ $pendingInst->id }}">
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="source_account_id" class="block text-xs font-semibold text-charcoal mb-1">
                            Rekening Kas / Bank Pembayar <span class="text-red">*</span>
                        </label>
                        <select 
                            name="source_account_id" 
                            id="source_account_id" 
                            required 
                            class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                        >
                            <option value="">-- Pilih Rekening Kas / Bank --</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (Saldo: {{ $acc->formatted_balance }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="amount" class="block text-xs font-semibold text-charcoal mb-1">
                            Nominal Pembayaran (Rp) <span class="text-red">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="amount" 
                            required 
                            min="0.01"
                            step="any"
                            value="{{ $statement['total_pending'] }}"
                            class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono font-bold text-base focus:outline-none focus:ring-1 focus:ring-forest"
                        >
                        <span class="text-[10px] text-muted">Sesuaikan jika ada selisih pembulatan/biaya m-banking (otomatis dicatat sebagai nota penyesuaian).</span>
                    </div>

                    <div>
                        <label for="payment_date" class="block text-xs font-semibold text-charcoal mb-1">
                            Tanggal Pembayaran <span class="text-red">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="payment_date" 
                            id="payment_date" 
                            required 
                            value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->toDateString() }}"
                            class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                        >
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-semibold text-charcoal mb-1">Catatan Pembayaran (Opsional)</label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="notes" 
                        placeholder="Contoh: Bayar tagihan via BCA Mobile / Autodebet..."
                        value="Pelunasan Lembar Tagihan {{ $creditAccount->name }} Periode {{ $statement['month_name'] }}"
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        type="submit" 
                        class="min-h-[44px] px-6 py-2.5 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs inline-flex items-center gap-2"
                    >
                        <span>✓</span>
                        <span>Konfirmasi Bayar & Lunasi Tagihan Bulan Ini</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Rincian Transaksi & Cicilan dalam Lembar Tagihan -->
    <div class="panel-card p-6">
        <div class="flex items-center justify-between pb-4 border-b border-line mb-4">
            <div>
                <h3 class="font-serif text-lg text-charcoal font-medium">Rincian Item dalam Lembar Tagihan</h3>
                <p class="text-xs text-muted">
                    Daftar seluruh pokok cicilan dan pemakaian kartu kredit yang ditagihkan pada periode ini.
                </p>
            </div>
            <span class="text-xs font-bold text-muted font-mono">{{ $statement['item_count'] }} Transaksi Terdata</span>
        </div>

        @if ($statement['installments']->isEmpty())
            <div class="py-12 text-center text-muted">
                <span class="text-3xl block mb-2">📑</span>
                <p class="text-xs">Tidak ada tagihan cicilan yang jatuh tempo pada periode {{ $statement['month_name'] }}.</p>
            </div>
        @else
            <div class="md:hidden space-y-3">
                @foreach ($statement['installments'] as $idx => $inst)
                    @php
                        $extra = (float) $inst->interest_amount + (float) $inst->fee_amount;
                        $extraDec = fmod($extra, 1.0) == 0.0 ? 0 : 2;
                        $formattedExtra = 'Rp ' . number_format($extra, $extraDec, ',', '.');
                    @endphp
                    <div class="p-3.5 rounded-xl border border-line bg-sidebar/30 space-y-2.5 text-xs {{ $inst->isPaid() ? 'opacity-80' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-charcoal truncate">{{ $inst->installmentPlan->name }}</div>
                                <div class="text-[11px] text-muted mt-0.5">
                                    #{{ $idx + 1 }} &bull; Cicilan {{ $inst->installment_number }}/{{ $inst->installmentPlan->tenor }} &bull; {{ $inst->installmentPlan->category->name ?? '-' }}
                                </div>
                                <div class="text-[11px] font-mono mt-0.5 {{ $inst->isOverdue() && $inst->isPending() ? 'text-red font-bold' : 'text-muted' }}">
                                    Jatuh Tempo: {{ $inst->due_date->format('d M Y') }} {{ $inst->isOverdue() && $inst->isPending() ? '(Terlambat)' : '' }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-mono font-bold text-sm text-charcoal">
                                    <span class="privacy-balance" data-raw-value="{{ $inst->formatted_total }}">{{ $inst->formatted_total }}</span>
                                </div>
                                <div class="mt-1">
                                    @if ($inst->isPaid())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">✓ Lunas</span>
                                    @elseif ($inst->isOverdue())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red/10 text-red">⚠ Terlambat</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">⏳ Pending</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-line/60 text-[11px]">
                            <span class="text-muted">Pokok: <strong class="font-mono text-charcoal privacy-balance" data-raw-value="{{ $inst->formatted_principal }}">{{ $inst->formatted_principal }}</strong> &bull; Bunga/Admin: <strong class="font-mono text-charcoal privacy-balance" data-raw-value="{{ $formattedExtra }}">{{ $formattedExtra }}</strong></span>
                            <a href="{{ route('credit.plans.show', $inst->installmentPlan) }}" class="min-h-[36px] inline-flex items-center font-bold text-forest hover:underline">Detail Rencana &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line text-muted">
                            <th class="py-3 px-3 font-semibold">No</th>
                            <th class="py-3 px-3 font-semibold">Jatuh Tempo</th>
                            <th class="py-3 px-3 font-semibold">Nama Belanja & Tenor</th>
                            <th class="py-3 px-3 font-semibold">Kategori</th>
                            <th class="py-3 px-3 font-semibold text-right">Pokok</th>
                            <th class="py-3 px-3 font-semibold text-right">Bunga/Admin</th>
                            <th class="py-3 px-3 font-semibold text-right">Total Tagihan</th>
                            <th class="py-3 px-3 font-semibold text-center">Status</th>
                            <th class="py-3 px-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/60">
                        @foreach ($statement['installments'] as $idx => $inst)
                            <tr class="hover:bg-cream/40 transition {{ $inst->isPaid() ? 'opacity-75' : '' }}">
                                <td class="py-3 px-3 font-mono text-muted">#{{ $idx + 1 }}</td>
                                <td class="py-3 px-3 whitespace-nowrap font-mono">
                                    <span class="{{ $inst->isOverdue() && $inst->isPending() ? 'text-red font-bold' : 'text-charcoal' }}">
                                        {{ $inst->due_date->format('d M Y') }}
                                    </span>
                                    @if ($inst->isOverdue() && $inst->isPending())
                                        <span class="block text-[10px] text-red font-bold">Terlambat</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-charcoal">
                                        {{ $inst->installmentPlan->name }}
                                    </div>
                                    <span class="text-[11px] text-muted">
                                        Cicilan ke-{{ $inst->installment_number }} dari {{ $inst->installmentPlan->tenor }} bulan
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-muted">
                                    {{ $inst->installmentPlan->category->name ?? '-' }}
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-right font-mono text-charcoal">
                                    <span class="privacy-balance" data-raw-value="{{ $inst->formatted_principal }}">{{ $inst->formatted_principal }}</span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-right font-mono text-muted">
                                    @php
                                        $extra = (float) $inst->interest_amount + (float) $inst->fee_amount;
                                        $extraDec = fmod($extra, 1.0) == 0.0 ? 0 : 2;
                                        $formattedExtra = 'Rp ' . number_format($extra, $extraDec, ',', '.');
                                    @endphp
                                    <span class="privacy-balance" data-raw-value="{{ $formattedExtra }}">{{ $formattedExtra }}</span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-right font-mono font-bold text-charcoal">
                                    <span class="privacy-balance" data-raw-value="{{ $inst->formatted_total }}">{{ $inst->formatted_total }}</span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-center">
                                    @if ($inst->isPaid())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            ✓ Lunas
                                        </span>
                                    @elseif ($inst->isOverdue())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red/10 text-red">
                                            ⚠ Terlambat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-right">
                                    <a 
                                        href="{{ route('credit.plans.show', $inst->installmentPlan) }}" 
                                        class="text-xs text-forest hover:text-forest-hover font-semibold hover:underline"
                                    >
                                        Detail Rencana →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
