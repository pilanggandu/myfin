<x-layouts.app title="Rincian Transaksi #{{ $transaction->id }}">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1">
                &larr; Kembali ke daftar transaksi
            </a>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / TRANSAKSI</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Rincian Transaksi #{{ $transaction->id }}
            </h1>
            <p class="text-xs text-muted">
                {{ $transaction->description }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button 
                type="button" 
                onclick="toggleBalanceVisibility()" 
                class="privacy-toggle-btn inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Nominal</span>
            </button>
            @if (! $transaction->isVoid())
                <a href="{{ route('transactions.edit', $transaction) }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px]">
                    <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                    <span>Koreksi Transaksi</span>
                </a>
                <button type="button" onclick="document.getElementById('void-modal').classList.remove('hidden')" class="btn-arus-danger min-h-[44px] sm:min-h-[38px]">
                    <svg class="w-4 h-4 text-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <span>Batalkan (Void)</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Alert status jika void -->
    @if ($transaction->isVoid())
        <div class="p-4 mb-6 rounded-xl border border-red/30 bg-red/5 text-xs text-red flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold uppercase tracking-wider text-[11px] px-2 py-0.5 rounded bg-red/10 border border-red/20 shrink-0">Status: Void</span>
                <span>Transaksi ini telah dibatalkan dan tidak lagi mempengaruhi saldo rekening mana pun.</span>
            </div>
            <span class="text-muted text-[11px] shrink-0">Versi terakhir: v{{ $transaction->version }}</span>
        </div>
    @endif

    <!-- Metric Cards Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @php
            $isIncome = $transaction->isIncome();
            $isExpense = $transaction->isExpense();
            $isTransfer = $transaction->isTransfer();
            $amount = $transaction->totalAmount();
        @endphp

        <x-metric-card 
            label="NOMINAL TRANSAKSI" 
            :value="'Rp ' . number_format($amount, 0, ',', '.')" 
            :dark="$isIncome ? 'green' : ($isExpense ? 'red' : 'blue')" 
            :sub="'Tipe: ' . strtoupper($transaction->type)" 
        />

        <x-metric-card 
            label="TANGGAL & WAKTU BUKU" 
            :value="$transaction->transaction_date->format('d M Y') . ($transaction->formattedTime() ? ' • ' . $transaction->formattedTime() : '')" 
            :sub="'Periode: ' . $transaction->transaction_date->translatedFormat('F Y')" 
        />

        <x-metric-card 
            label="STATUS & VERSI" 
            :value="strtoupper($transaction->status)" 
            :sub="'Versi Optimistic Lock: v' . $transaction->version" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Informasi Transaksi -->
        <div class="lg:col-span-2 space-y-6">
            <x-panel eyebrow="RINCIAN">
                <div class="divide-y divide-line text-xs">
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Jenis Transaksi</span>
                        @if ($transaction->isIncome())
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900">
                                PEMASUKAN
                            </span>
                        @elseif ($transaction->isExpense())
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900">
                                PENGELUARAN
                            </span>
                        @else
                            <x-badge variant="default">
                                {{ strtoupper($transaction->type) }}
                            </x-badge>
                        @endif
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Kategori</span>
                        <span class="font-bold text-charcoal">
                            {{ $transaction->category?->name ?? '(Transfer antar-rekening)' }}
                        </span>
                    </div>

                    @if ($transaction->isTransfer() && (float)($transaction->fee_amount ?? 0) > 0)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Biaya Admin Transfer</span>
                            <span class="font-bold text-red privacy-balance" data-raw-value="Rp {{ number_format($transaction->fee_amount, 0, ',', '.') }}">
                                Rp {{ number_format($transaction->fee_amount, 0, ',', '.') }}
                            </span>
                        </div>
                        @if ($transaction->feeCategory)
                            <div class="py-3 flex justify-between items-center">
                                <span class="text-muted">Kategori Biaya Admin</span>
                                <span class="font-bold text-charcoal">
                                    {{ $transaction->feeCategory->name }}
                                </span>
                            </div>
                        @endif
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Total Pengurangan Rekening Asal</span>
                            <span class="font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($transaction->totalDeductedFromSource(), 0, ',', '.') }}">
                                Rp {{ number_format($transaction->totalDeductedFromSource(), 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    @if ($transaction->member)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Pihak / Member</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-2xs" style="background-color: {{ $transaction->member->color ?: '#0f4c3a' }};">
                                {{ $transaction->member->name }}
                            </span>
                        </div>
                    @endif

                    @if ($transaction->recurringOccurrence)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Jadwal Berulang</span>
                            <a href="{{ route('recurring.show', $transaction->recurringOccurrence->schedule) }}" class="font-bold text-forest hover:underline inline-flex items-center gap-1">
                                <span>{{ $transaction->recurringOccurrence->schedule?->name }}</span>
                                <span class="text-muted text-[11px] font-normal">(Periode {{ $transaction->recurringOccurrence->due_date->format('d/m/Y') }})</span>
                            </a>
                        </div>
                    @endif

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Keterangan</span>
                        <span class="font-semibold text-charcoal text-right max-w-xs sm:max-w-md">
                            {{ $transaction->description }}
                        </span>
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Status Mutasi</span>
                        @if ($transaction->isVoid())
                            <x-badge variant="inactive">VOID (DIBATALKAN)</x-badge>
                        @else
                            <x-badge variant="active">POSTED (TERCATAT)</x-badge>
                        @endif
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <span class="text-muted">Dicatat Oleh</span>
                        <span class="text-charcoal font-medium">
                            {{ $transaction->creator?->name ?? 'Sistem' }} ({{ $transaction->created_at->format('d M Y H:i') }})
                        </span>
                    </div>

                    @if ($transaction->updater)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Terakhir Diperbarui</span>
                            <span class="text-charcoal font-medium">
                                {{ $transaction->updater->name }} ({{ $transaction->updated_at->format('d M Y H:i') }})
                            </span>
                        </div>
                    @endif

                    @if ($transaction->request_key)
                        <div class="py-3 flex justify-between items-center">
                            <span class="text-muted">Kunci Idempoten (Request Key)</span>
                            <span class="font-mono text-[11px] text-muted">
                                {{ $transaction->request_key }}
                            </span>
                        </div>
                    @endif
                </div>
            </x-panel>

            <!-- Entri Pembukuan -->
            <x-panel eyebrow="ENTRI PEMBUKUAN (JURNAL MUTASI)">
                <div class="md:hidden divide-y divide-line">
                    @foreach ($transaction->entries as $entry)
                        @php
                            $isPositive = $entry->amount_signed > 0;
                        @endphp
                        <div class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('accounts.show', $entry->account) }}" class="font-bold text-charcoal hover:text-forest hover:underline block truncate">
                                    {{ $entry->account->name }}
                                </a>
                                <div class="mt-1 flex items-center flex-wrap gap-1.5 text-[11px]">
                                    <span class="uppercase text-muted">{{ $entry->account->type }}</span>
                                    <span class="text-line">&bull;</span>
                                    @if ($isPositive)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Kas Masuk (Debet)</span>
                                    @else
                                        <span class="text-red dark:text-rose-400 font-semibold">Kas Keluar (Kredit)</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right font-mono font-bold shrink-0 {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-400' }}">
                                <span class="privacy-balance" data-raw-value="{{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}">
                                    {{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                <th class="py-2.5 px-4">Rekening</th>
                                <th class="py-2.5 px-4">Tipe Rekening</th>
                                <th class="py-2.5 px-4">Arah Aliran</th>
                                <th class="py-2.5 px-4 text-right">Mutasi Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transaction->entries as $entry)
                                @php
                                    $isPositive = $entry->amount_signed > 0;
                                @endphp
                                <tr class="hover:bg-cream/20 transition-colors">
                                    <td class="py-3 px-4 font-bold text-charcoal">
                                        <a href="{{ route('accounts.show', $entry->account) }}" class="hover:text-forest hover:underline">
                                            {{ $entry->account->name }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 uppercase text-muted text-[11px]">
                                        {{ $entry->account->type }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($isPositive)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Kas Masuk (Debet)</span>
                                        @else
                                            <span class="text-red dark:text-rose-400 font-semibold">Kas Keluar (Kredit)</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-400' }}">
                                        <span class="privacy-balance" data-raw-value="{{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}">
                                            {{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        </div>

        <!-- Audit Trail / Histori Revisi -->
        <div class="space-y-6">
            <x-panel eyebrow="JEJAK AUDIT & REVISI">
                @if ($transaction->revisions->isEmpty())
                    <p class="text-xs text-muted leading-relaxed m-0">
                        Belum ada riwayat koreksi. Transaksi ini masih sesuai data aslinya sejak pertama kali dicatat.
                    </p>
                @else
                    <div class="space-y-4">
                        @foreach ($transaction->revisions->sortByDesc('created_at') as $revision)
                            <div class="p-3.5 rounded-lg border border-line bg-sidebar/40 text-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-forest">{{ $revision->actor?->name ?? 'Pengguna' }}</span>
                                    <span class="text-[11px] text-muted">{{ $revision->created_at->format('d M Y H:i') }}</span>
                                </div>
                                @if ($revision->reason)
                                    <div class="text-charcoal mb-2 font-medium">
                                        Alasan: "{{ $revision->reason }}"
                                    </div>
                                @endif
                                
                                <div class="mt-2 pt-2 border-t border-line text-[11px] text-muted space-y-1">
                                    @if (isset($revision->before_data['amount']) && isset($revision->after_data['amount']))
                                        @if ($revision->before_data['amount'] != $revision->after_data['amount'])
                                            <div>
                                                Nominal: <span class="privacy-balance" data-raw-value="Rp {{ number_format($revision->before_data['amount'], 0, ',', '.') }}">Rp {{ number_format($revision->before_data['amount'], 0, ',', '.') }}</span> &rarr; <span class="privacy-balance" data-raw-value="Rp {{ number_format($revision->after_data['amount'], 0, ',', '.') }}">Rp {{ number_format($revision->after_data['amount'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    @endif
                                    @if (isset($revision->before_data['description']) && isset($revision->after_data['description']))
                                        @if ($revision->before_data['description'] != $revision->after_data['description'])
                                            <div class="truncate">
                                                Keterangan: "{{ $revision->before_data['description'] }}" &rarr; "{{ $revision->after_data['description'] }}"
                                            </div>
                                        @endif
                                    @endif
                                    @if (isset($revision->after_data['status']) && $revision->after_data['status'] === 'void')
                                        <div class="text-red font-bold">
                                            Status diubah menjadi VOID (dibatalkan)
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-panel>
        </div>
    </div>

    <!-- Modal Konfirmasi Void Transaksi -->
    @if (! $transaction->isVoid())
        <div id="void-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-xl my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
                <h3 class="font-serif text-xl text-forest font-normal mb-2">Batalkan Transaksi?</h3>
                <p class="text-xs text-muted leading-relaxed mb-4">
                    Tindakan pembatalan (void) akan membatalkan pengaruh transaksi ini dari saldo rekening terkait. Catatan tidak dihapus dari basis data untuk mematuhi integritas audit pembukuan.
                </p>

                <form method="POST" action="{{ route('transactions.void', $transaction) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="void_reason" class="field-label text-xs">Alasan Pembatalan (Opsional)</label>
                        <input 
                            type="text" 
                            name="reason" 
                            id="void_reason" 
                            placeholder="Contoh: Salah input tanggal / transaksi duplikat" 
                            class="field-input text-xs" 
                            maxlength="255"
                        />
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('void-modal').classList.add('hidden')" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px]">
                            Batal
                        </button>
                        <button type="submit" class="btn-arus-danger-solid w-full sm:w-auto min-h-[44px] sm:min-h-[38px]">
                            Ya, Batalkan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-layouts.app>
