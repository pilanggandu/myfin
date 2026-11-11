<x-layouts.app title="Detail Jadwal Berulang">
    @php
        $dueStatus = $recurring->dueStatus($tz);
    @endphp

    <div class="mb-6">
        <a href="{{ route('recurring.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar jadwal berulang
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / RECURRING / DETAIL</div>
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-1">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold {{ $recurring->isCreditPayment() ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : ($recurring->isExpense() ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-slate-500/10 text-charcoal') }}">
                    {{ $recurring->isCreditPayment() ? '💳' : ($recurring->isExpense() ? '🏷️' : '⇄') }}
                </span>
                <div>
                    <h1 class="font-serif text-2xl sm:text-3xl text-forest font-normal tracking-tight">
                        {{ $recurring->name }}
                    </h1>
                    <div class="flex items-center gap-2 mt-0.5 text-xs text-muted flex-wrap">
                        <span class="font-bold text-charcoal uppercase text-[10px] px-2 py-0.5 rounded bg-sidebar border border-line">
                            {{ $recurring->isCreditPayment() ? 'Pembayaran Tagihan Kredit' : ($recurring->isExpense() ? 'Tagihan Pengeluaran' : 'Transfer Berulang') }}
                        </span>
                        @if ($recurring->isPaused())
                            <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                Dijeda
                            </span>
                        @elseif ($recurring->isCancelled())
                            <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                Dibatalkan
                            </span>
                        @elseif ($recurring->isCompleted())
                            <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded bg-forest/15 text-forest border border-forest/30">
                                Selesai
                            </span>
                        @elseif ($recurring->isArchived())
                            <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded bg-muted/15 text-muted border border-line">
                                Diarsipkan
                            </span>
                        @endif
                        @if ($recurring->member)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background-color: {{ $recurring->member->color ?: '#0f4c3a' }};">
                                {{ $recurring->member->name }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2 flex-wrap">
                @if ($recurring->isActive())
                    <button 
                        type="button" 
                        onclick="openConfirmModal('{{ $recurring->next_due_date->toDateString() }}', {{ $recurring->amount ?: 0 }})"
                        class="btn-arus-primary text-xs font-bold px-4 py-2 inline-flex items-center gap-1.5 shadow-xs cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>Konfirmasi Catat Periode Ini</span>
                    </button>

                    <form method="POST" action="{{ route('recurring.skip', $recurring) }}" class="inline" onsubmit="return confirm('Lewati periode jatuh tempo {{ $recurring->next_due_date->format('d/m/Y') }} tanpa mencatat transaksi keuangan?')">
                        @csrf
                        <input type="hidden" name="due_date" value="{{ $recurring->next_due_date->toDateString() }}">
                        <button type="submit" class="btn-arus-secondary text-xs font-bold px-3 py-2 text-muted hover:text-charcoal cursor-pointer" title="Lewati periode ini">
                            Lewati
                        </button>
                    </form>
                @endif

                <a href="{{ route('recurring.edit', $recurring) }}" class="btn-arus-secondary text-xs font-bold px-3 py-2 text-forest border-forest/30 hover:bg-forest-light">
                    Ubah
                </a>

                <form method="POST" action="{{ route('recurring.destroy', $recurring) }}" class="inline" onsubmit="return confirm('{{ $recurring->isArchived() ? 'Pulihkan jadwal ini?' : 'Arsipkan jadwal ini?' }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-arus-secondary text-xs font-bold px-3 py-2 text-rose-600 hover:bg-rose-500/10 border-rose-500/30 cursor-pointer">
                        {{ $recurring->isArchived() ? 'Pulihkan' : 'Arsipkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1.5" role="alert">
            <div class="font-bold flex items-center gap-1.5">
                <span>⚠️</span>
                <span>Konfirmasi gagal dicatat:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($recurring->sourceAccount?->isLiability() && ! $recurring->sourceAccount?->creditAccount)
        <div class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-950 dark:text-amber-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="font-bold">Rekening Sumber Terhubung ke Akun Kredit</div>
                <p class="text-[11px] opacity-85 mt-0.5">
                    Jadwal berulang ini masih memakai akun <strong>{{ $recurring->sourceAccount->name }}</strong>. Agar terintegrasi dengan siklus limit &amp; tagihan kartu kredit, sebaiknya pindahkan pencatatan ke menu Kredit &amp; Cicilan dan arsipkan jadwal ini (atau ubah rekening sumber ke Kas/Bank).
                </p>
            </div>
            <a href="{{ route('credit.index') }}" class="btn-arus-secondary text-xs py-1.5 px-3 font-bold shrink-0">
                Buka Kredit &amp; Cicilan &rarr;
            </a>
        </div>
    @endif

    <!-- Parameter Card & Status Card Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mb-6">
        <!-- Kolom Kiri: Rincian Parameter -->
        <div class="lg:col-span-7 space-y-4">
            <div class="panel-card p-5 rounded-2xl border border-line bg-panel shadow-xs space-y-4">
                <div class="text-[11px] font-bold text-muted uppercase tracking-wider border-b border-line pb-2.5">
                    Parameter Jadwal
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-muted block text-[11px] mb-0.5">Frekuensi:</span>
                        <span class="font-bold text-charcoal">
                            @if ($recurring->frequency === 'weekly')
                                Mingguan
                            @elseif ($recurring->frequency === 'yearly')
                                Tahunan
                            @else
                                Bulanan (Setiap tanggal {{ $recurring->anchor_day }})
                            @endif
                        </span>
                    </div>

                    <div>
                        <span class="text-muted block text-[11px] mb-0.5">Nominal:</span>
                        <span class="font-mono font-bold text-sm text-charcoal">
                            @if ($recurring->is_variable_amount)
                                <span class="text-xs text-muted font-normal italic">Fleksibel (diisi saat konfirmasi)</span>
                            @else
                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($recurring->amount, 0, ',', '.') }}">
                                    Rp {{ number_format($recurring->amount, 0, ',', '.') }}
                                </span>
                            @endif
                        </span>
                    </div>

                    @if ($recurring->isExpense())
                        <div>
                            <span class="text-muted block text-[11px] mb-0.5">Rekening Sumber:</span>
                            @if ($recurring->creditAccount)
                                <div class="font-bold text-charcoal">💳 {{ $recurring->creditAccount->name }}</div>
                                <a href="{{ route('credit.accounts.statement', $recurring->creditAccount) }}" class="text-[11px] font-semibold text-forest hover:underline">
                                    Lihat Lembar Tagihan &rarr;
                                </a>
                            @else
                                <span class="font-bold text-charcoal">{{ $recurring->sourceAccount?->name ?: 'Pilih saat bayar (Fleksibel)' }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-muted block text-[11px] mb-0.5">Pos Pengeluaran:</span>
                            <span class="font-bold text-charcoal">{{ $recurring->category?->name }}</span>
                        </div>
                    @elseif ($recurring->isCreditPayment())
                        <div>
                            <span class="text-muted block text-[11px] mb-0.5">Rekening Pembayar:</span>
                            <span class="font-bold text-charcoal">{{ $recurring->sourceAccount?->name ?: 'Pilih saat bayar (Fleksibel)' }}</span>
                        </div>
                        <div>
                            <span class="text-muted block text-[11px] mb-0.5">Kartu / Akun Kredit Tujuan:</span>
                            @if ($recurring->creditAccount)
                                <div class="font-bold text-charcoal">💳 {{ $recurring->creditAccount->name }}</div>
                                <div class="text-[11px] text-muted mt-0.5">
                                    Kewajiban saat ini: <strong class="text-charcoal font-mono privacy-balance" data-raw-value="Rp {{ number_format($creditLiability, 0, ',', '.') }}">Rp {{ number_format($creditLiability, 0, ',', '.') }}</strong>
                                    &bull;
                                    <a href="{{ route('credit.accounts.statement', $recurring->creditAccount) }}" class="font-semibold text-forest hover:underline">
                                        Tagihan &rarr;
                                    </a>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    @else
                        <div>
                            <span class="text-muted block text-[11px] mb-0.5">Dari Rekening:</span>
                            <span class="font-bold text-charcoal">{{ $recurring->sourceAccount?->name }}</span>
                        </div>
                        <div>
                            <span class="text-muted block text-[11px] mb-0.5">Ke Rekening Tujuan:</span>
                            <span class="font-bold text-charcoal">{{ $recurring->destinationAccount?->name }}</span>
                        </div>
                    @endif

                    <div>
                        <span class="text-muted block text-[11px] mb-0.5">Tanggal Mulai:</span>
                        <span class="font-bold text-charcoal">{{ $recurring->start_date->format('d F Y') }}</span>
                    </div>

                    <div>
                        <span class="text-muted block text-[11px] mb-0.5">Batas Berakhir:</span>
                        <span class="font-bold text-charcoal">
                            {{ $recurring->end_date ? $recurring->end_date->format('d F Y') : 'Tanpa batas akhir' }}
                        </span>
                    </div>
                </div>

                @if ($recurring->description)
                    <div class="pt-2 border-t border-line text-xs">
                        <span class="text-muted block text-[11px] mb-0.5">Catatan:</span>
                        <p class="text-charcoal">{{ $recurring->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Status Periode Berjalan -->
        <div class="lg:col-span-5 space-y-4">
            <div class="panel-card p-5 rounded-2xl border border-line bg-panel shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-line pb-2.5">
                    <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Periode Aktif Berikutnya</span>
                    @if ($dueStatus === 'overdue')
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 uppercase tracking-wider">
                            Terlambat
                        </span>
                    @elseif ($dueStatus === 'due')
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 uppercase tracking-wider">
                            Jatuh Tempo Hari Ini
                        </span>
                    @else
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-sidebar text-muted border border-line uppercase tracking-wider">
                            Mendatang
                        </span>
                    @endif
                </div>

                <div class="py-2 text-center bg-sidebar/50 rounded-xl border border-line/60">
                    <div class="text-[10px] uppercase font-bold text-muted tracking-wider mb-0.5">Jatuh Tempo</div>
                    <div class="font-mono text-xl sm:text-2xl font-bold text-charcoal">
                        {{ $recurring->next_due_date->format('d F Y') }}
                    </div>
                    <div class="text-[11px] text-muted mt-1">
                        Zona waktu ruang: {{ $tz }}
                    </div>
                </div>

                <p class="text-[11px] text-muted text-center pt-1">
                    Saat Anda mengonfirmasi pencatatan, transaksi riil akan dibuat dan tanggal jatuh tempo akan otomatis bergeser ke siklus berikutnya.
                </p>
            </div>
        </div>
    </div>

    <!-- Riwayat Kejadian (Occurrences History) -->
    <div class="panel-card rounded-2xl border border-line bg-panel shadow-xs overflow-hidden">
        <div class="p-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-forest/10 text-forest flex items-center justify-center text-xs font-bold">📜</span>
                <span class="text-xs font-bold text-charcoal">
                    Riwayat Kejadian & Pencatatan ({{ $recurring->occurrences->count() }})
                </span>
            </div>
            <span class="text-[11px] text-muted">Pelacakan dobel-entri</span>
        </div>

        @if ($recurring->occurrences->isEmpty())
            <div class="p-8 text-center text-xs text-muted">
                Belum ada riwayat kejadian untuk jadwal ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-line bg-sidebar/50 text-[10px] uppercase font-bold text-muted tracking-wider">
                            <th class="py-3 px-4">Periode Jatuh Tempo</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-4">Transaksi Terkait</th>
                            <th class="py-3 px-4">Dikonfirmasi</th>
                            <th class="py-3 px-4">Catatan</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($recurring->occurrences as $occ)
                            <tr class="hover:bg-cream/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-charcoal">
                                    {{ $occ->due_date->format('d M Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($occ->isConfirmed())
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            ✓ Tercatat
                                        </span>
                                    @elseif ($occ->isSkipped())
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-500/15 text-muted border border-line">
                                            Dilewati
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            Menunggu
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold">
                                    @if ($occ->amount)
                                        <span class="privacy-balance">
                                            Rp {{ number_format($occ->amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    @if ($occ->transaction)
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('transactions.show', $occ->transaction) }}" class="font-bold text-forest hover:underline inline-flex items-center gap-1">
                                                <span>TX #{{ $occ->transaction->id }}</span>
                                                <svg class="w-3.5 h-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                            </a>
                                            @if ($occ->transaction->isVoid())
                                                <span class="text-[9px] font-bold px-1 rounded bg-rose-500/15 text-rose-500">VOID</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-muted text-[11px]">
                                    @if ($occ->confirmed_at)
                                        <div>{{ $occ->confirmed_at->format('d/m/Y H:i') }}</div>
                                        <div class="text-[10px]">{{ $occ->confirmedBy?->name }}</div>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-muted max-w-xs truncate">
                                    {{ $occ->notes ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    @if ($occ->isPending() && $recurring->isActive())
                                        <form method="POST" action="{{ route('recurring.skip', $recurring) }}" class="inline" onsubmit="return confirm('Lewati periode {{ $occ->due_date->format('d/m/Y') }} tanpa mencatat transaksi?')">
                                            @csrf
                                            <input type="hidden" name="due_date" value="{{ $occ->due_date->toDateString() }}">
                                            <button type="submit" class="px-2 py-1 rounded border border-line text-[11px] font-semibold text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer">
                                                Lewati
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Occurrence -->
    <div id="confirmOccurrenceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
        <div class="panel-card w-full max-w-md p-6 rounded-2xl border border-line bg-panel shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center text-xs font-bold">✓</span>
                    <h3 class="text-sm font-bold text-charcoal">Konfirmasi & Catat Transaksi</h3>
                </div>
                <button type="button" onclick="closeConfirmModal()" class="text-muted hover:text-charcoal font-bold text-base cursor-pointer">&times;</button>
            </div>

            <form method="POST" id="confirmOccurrenceForm" action="{{ route('recurring.confirm', $recurring) }}" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="due_date" id="modalDueDate" value="{{ $recurring->next_due_date->toDateString() }}">

                <div class="p-3 rounded-xl bg-sidebar border border-line space-y-1">
                    <div class="text-[10px] uppercase font-bold text-muted tracking-wider">Jadwal: {{ $recurring->name }}</div>
                    <div class="text-xs text-muted flex items-center gap-1">
                        <span>Periode Jatuh Tempo:</span>
                        <span id="modalDueDateLabel" class="font-bold text-charcoal">{{ $recurring->next_due_date->format('d F Y') }}</span>
                    </div>
                </div>

                <div>
                    <label for="modalShowSourceAccount" class="block text-xs font-bold text-charcoal mb-1">
                        {{ $recurring->isCreditPayment() ? 'Rekening Kas / Bank Pembayar' : 'Rekening Pembayaran' }} <span class="text-rose-500">*</span>
                    </label>
                    @php
                        $showLiquidAccounts = $accounts->filter(fn ($acc) => ! $acc->isLiability());
                        $showCreditAccounts = $accounts->filter(fn ($acc) => $acc->isCreditSpendAccount() && $acc->creditAccount);
                    @endphp
                    <select name="source_account_id" id="modalShowSourceAccount" class="field-select w-full text-xs" required>
                        @if ($showCreditAccounts->isNotEmpty())
                            @if ($showLiquidAccounts->isNotEmpty())
                                <optgroup label="Kas, Bank, E-Wallet &amp; Investasi">
                                    @foreach ($showLiquidAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ (string)$recurring->source_account_id === (string)$acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="Kartu Kredit &amp; PayLater">
                                @foreach ($showCreditAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (string)$recurring->source_account_id === (string)$acc->id ? 'selected' : '' }}>
                                        💳 {{ $acc->creditAccount->name }} (Sisa Limit: {{ $acc->creditAccount->formatted_available_limit }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @else
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ (string)$recurring->source_account_id === (string)$acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div class="text-[11px] text-muted mt-1">
                        {{ $recurring->isCreditPayment() ? 'Saldo rekening ini akan dipotong untuk melunasi kewajiban kartu kredit.' : 'Pilih rekening atau kartu yang digunakan untuk transaksi ini.' }}
                    </div>
                </div>

                @php
                    $defaultConfirmAmount = $recurring->amount ?: ($recurring->isCreditPayment() && $suggestedCreditAmount > 0 ? $suggestedCreditAmount : '');
                @endphp
                <div>
                    <label for="modalAmount" class="block text-xs font-bold text-charcoal mb-1">
                        Nominal Transaksi (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="modalAmount" 
                        min="1" 
                        value="{{ $defaultConfirmAmount }}"
                        class="field-input w-full text-xs font-mono font-bold" 
                        required 
                        onfocus="this.select()"
                        onclick="this.select()"
                    />
                    @if ($recurring->isCreditPayment() && $recurring->is_variable_amount && $suggestedCreditAmount > 0)
                        <div class="text-[11px] text-muted mt-1">
                            Terisi otomatis dari tagihan/kewajiban kartu saat ini (Rp {{ number_format($suggestedCreditAmount, 0, ',', '.') }}).
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="modalTxDate" class="block text-xs font-bold text-charcoal mb-1">
                            Tanggal Catat
                        </label>
                        <input 
                            type="date" 
                            name="transaction_date" 
                            id="modalTxDate" 
                            value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->format('Y-m-d') }}" 
                            class="field-input w-full text-xs" 
                            required 
                        />
                    </div>
                    <div>
                        <label for="modalTxTime" class="block text-xs font-bold text-charcoal mb-1">
                            Jam
                        </label>
                        <input 
                            type="time" 
                            name="transaction_time" 
                            id="modalTxTime" 
                            value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->format('H:i') }}" 
                            class="field-input w-full text-xs" 
                        />
                    </div>
                </div>

                <div>
                    <label for="modalNotes" class="block text-xs font-bold text-charcoal mb-1">
                        Catatan Transaksi (Opsional)
                    </label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="modalNotes" 
                        placeholder="Contoh: Bukti bayar #12345" 
                        class="field-input w-full text-xs" 
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                    <button type="button" onclick="closeConfirmModal()" class="btn-arus-secondary px-4 py-2 text-xs font-bold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btnConfirmShowSubmit" class="btn-arus-primary px-5 py-2 text-xs font-bold cursor-pointer inline-flex items-center gap-1.5">
                        <svg id="confirmShowIcon" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <svg id="confirmShowSpinner" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="confirmShowLabel">Konfirmasi Catat</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openConfirmModal(dueDate, amount) {
            const modal = document.getElementById('confirmOccurrenceModal');
            document.getElementById('modalDueDate').value = dueDate;
            document.getElementById('modalDueDateLabel').textContent = dueDate;
            if (amount > 0) {
                document.getElementById('modalAmount').value = amount;
            }
            modal.classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirmOccurrenceModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeConfirmModal();
            }
        });

        document.getElementById('confirmOccurrenceForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnConfirmShowSubmit');
            const icon = document.getElementById('confirmShowIcon');
            const spinner = document.getElementById('confirmShowSpinner');
            const label = document.getElementById('confirmShowLabel');
            if (icon) icon.classList.add('hidden');
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.textContent = 'Mencatat...';
            if (btn) btn.classList.add('opacity-75', 'pointer-events-none');
        });
    </script>
</x-layouts.app>
