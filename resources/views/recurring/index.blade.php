<x-layouts.app title="Jadwal Berulang & Tagihan">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / RECURRING</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Jadwal Berulang & Tagihan
            </h1>
            <p class="text-xs text-muted">
                Kelola tagihan rutin (Kas, Bank, E-Wallet, atau Kartu Kredit/PayLater), transfer berkala, dan jadwal pelunasan tagihan kredit. Untuk belanja cicilan ber-tenor, gunakan <a href="{{ route('credit.index') }}" class="text-forest font-semibold hover:underline">Kredit &amp; Cicilan</a>.
            </p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('recurring.create') }}" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] text-xs font-bold px-4 py-2 inline-flex items-center justify-center gap-1.5 shadow-xs">
                <span>+ Buat Jadwal Baru</span>
            </a>
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

    <!-- Ringkasan Status Header Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="panel-card p-4 rounded-xl border border-line bg-panel shadow-xs">
            <div class="text-[10px] uppercase font-bold text-muted tracking-wider">Jatuh Tempo Hari Ini</div>
            <div class="font-mono text-2xl font-bold mt-1 {{ $dueTodayCount > 0 ? 'text-amber-500' : 'text-charcoal' }}">
                {{ $dueTodayCount }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">Siap dikonfirmasi</div>
        </div>

        <div class="panel-card p-4 rounded-xl border border-line bg-panel shadow-xs">
            <div class="text-[10px] uppercase font-bold text-muted tracking-wider">Terlambat (Overdue)</div>
            <div class="font-mono text-2xl font-bold mt-1 {{ $overdueCount > 0 ? 'text-rose-500' : 'text-charcoal' }}">
                {{ $overdueCount }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">Melewati tanggal jatuh tempo</div>
        </div>

        <div class="panel-card p-4 rounded-xl border border-line bg-panel shadow-xs">
            <div class="text-[10px] uppercase font-bold text-muted tracking-wider">Tagihan Pengeluaran</div>
            <div class="font-mono text-2xl font-bold text-forest mt-1">
                {{ $expenseCount }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">Kas, Bank &amp; Kartu Kredit</div>
        </div>

        <div class="panel-card p-4 rounded-xl border border-line bg-panel shadow-xs">
            <div class="text-[10px] uppercase font-bold text-muted tracking-wider">Transfer &amp; Bayar Kredit</div>
            <div class="font-mono text-2xl font-bold text-charcoal mt-1">
                {{ $transferCount + $creditPaymentCount }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">{{ $transferCount }} transfer, {{ $creditPaymentCount }} bayar kredit</div>
        </div>
    </div>

    <!-- Filter & Tabs -->
    <div class="panel-card rounded-2xl border border-line bg-panel shadow-xs overflow-hidden">
        <div class="p-4 border-b border-line flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 overflow-x-auto whitespace-nowrap pb-1 sm:pb-0 text-xs font-bold">
                <a href="{{ route('recurring.index', array_merge(request()->query(), ['status' => 'active', 'type' => 'all'])) }}" class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-1.5 rounded-lg transition inline-flex items-center shrink-0 {{ $status === 'active' && $type === 'all' ? 'bg-forest text-white' : 'text-muted hover:text-charcoal bg-sidebar' }}">
                    Semua Aktif ({{ $activeCount }})
                </a>
                <a href="{{ route('recurring.index', array_merge(request()->query(), ['status' => 'active', 'type' => 'expense'])) }}" class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-1.5 rounded-lg transition inline-flex items-center shrink-0 {{ $status === 'active' && $type === 'expense' ? 'bg-forest text-white' : 'text-muted hover:text-charcoal bg-sidebar' }}">
                    Tagihan ({{ $expenseCount }})
                </a>
                <a href="{{ route('recurring.index', array_merge(request()->query(), ['status' => 'active', 'type' => 'transfer'])) }}" class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-1.5 rounded-lg transition inline-flex items-center shrink-0 {{ $status === 'active' && $type === 'transfer' ? 'bg-forest text-white' : 'text-muted hover:text-charcoal bg-sidebar' }}">
                    Transfer ({{ $transferCount }})
                </a>
                <a href="{{ route('recurring.index', array_merge(request()->query(), ['status' => 'active', 'type' => 'credit_payment'])) }}" class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-1.5 rounded-lg transition inline-flex items-center shrink-0 {{ $status === 'active' && $type === 'credit_payment' ? 'bg-forest text-white' : 'text-muted hover:text-charcoal bg-sidebar' }}">
                    Bayar Kredit ({{ $creditPaymentCount }})
                </a>
                <a href="{{ route('recurring.index', array_merge(request()->query(), ['status' => 'paused', 'type' => 'all'])) }}" class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-1.5 rounded-lg transition inline-flex items-center shrink-0 {{ $status === 'paused' ? 'bg-forest text-white' : 'text-muted hover:text-charcoal bg-sidebar' }}">
                    Dijeda ({{ $pausedCount }})
                </a>
                <a href="{{ route('recurring.index', array_merge(request()->query(), ['status' => 'archived', 'type' => 'all'])) }}" class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-1.5 rounded-lg transition inline-flex items-center shrink-0 {{ $status === 'archived' ? 'bg-forest text-white' : 'text-muted hover:text-charcoal bg-sidebar' }}">
                    Arsip & Batal ({{ $archivedCount }})
                </a>
            </div>

            <!-- Member Filter -->
            @if ($members->isNotEmpty())
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-muted whitespace-nowrap">Filter Member:</span>
                    <form method="GET" action="{{ route('recurring.index') }}" class="flex-1 sm:flex-initial">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <select name="member_id" onchange="this.form.submit()" class="field-select text-xs py-1.5 sm:py-1 px-2.5 h-11 sm:h-8 w-full sm:w-auto">
                            <option value="">Semua Member</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}" {{ (string)$memberId === (string)$m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
        </div>

        @if ($schedules->isEmpty())
            <div class="p-10 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-sidebar border border-line flex items-center justify-center mx-auto text-2xl">
                    🔄
                </div>
                <div>
                    <h3 class="font-bold text-sm text-charcoal">Tidak Ada Jadwal Berulang</h3>
                    <p class="text-xs text-muted mt-1 max-w-sm mx-auto">
                        {{ $status === 'archived' ? 'Tidak ada jadwal berulang dalam arsip.' : 'Mulai buat jadwal tagihan rutin (seperti internet, sewa, asuransi) atau transfer bulanan.' }}
                    </p>
                </div>
                @if ($status !== 'archived')
                    <div class="pt-2">
                        <a href="{{ route('recurring.create') }}" class="btn-arus-primary text-xs font-bold px-4 py-2 inline-flex items-center gap-1.5">
                            + Buat Jadwal Baru
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="md:hidden divide-y divide-line">
                @foreach ($schedules as $item)
                    @php
                        $dueStatus = $item->dueStatus($tz);
                        $suggestedAmt = $item->isCreditPayment() && $item->is_variable_amount
                            ? ($creditSuggestions[$item->credit_account_id]['suggested'] ?? 0)
                            : 0;
                    @endphp
                    <div class="p-4 space-y-3 text-xs {{ $item->isArchived() ? 'opacity-65' : '' }}">
                        <div class="flex items-start justify-between gap-2.5">
                            <div class="flex items-start gap-2.5 min-w-0 flex-1">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 {{ $item->isCreditPayment() ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : ($item->isExpense() ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-slate-500/10 text-charcoal') }}">
                                    {{ $item->isCreditPayment() ? '💳' : ($item->isExpense() ? '🏷️' : '⇄') }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <a href="{{ route('recurring.show', $item) }}" class="font-bold text-sm text-charcoal hover:text-forest truncate">
                                            {{ $item->name }}
                                        </a>
                                        @if ($item->isPaused())
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 uppercase">Dijeda</span>
                                        @elseif ($item->isCancelled())
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 uppercase">Dibatalkan</span>
                                        @elseif ($item->isCompleted())
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-forest/15 text-forest border border-forest/30 uppercase">Selesai</span>
                                        @elseif ($item->isArchived())
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-muted/15 text-muted border border-line uppercase">Diarsipkan</span>
                                        @endif
                                        @if ($item->member)
                                            <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[9.5px] font-bold text-white" style="background-color: {{ $item->member->color ?: '#0f4c3a' }};">
                                                {{ $item->member->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-muted mt-0.5">
                                        @if ($item->frequency === 'weekly') Mingguan
                                        @elseif ($item->frequency === 'yearly') Tahunan
                                        @else Bulanan (Tgl {{ $item->anchor_day }})
                                        @endif
                                        &bull; Tempo: <strong class="font-mono text-charcoal">{{ $item->next_due_date->format('d M Y') }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                @if ($item->is_variable_amount)
                                    <span class="text-xs text-muted italic">Fleksibel</span>
                                @else
                                    <span class="font-mono font-bold text-sm text-charcoal privacy-balance block">
                                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                                    </span>
                                @endif
                                @if ($dueStatus === 'overdue')
                                    <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 uppercase mt-1">Terlambat</span>
                                @elseif ($dueStatus === 'due')
                                    <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 uppercase mt-1">Hari Ini</span>
                                @else
                                    <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-sidebar text-muted border border-line uppercase mt-1">Mendatang</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-2.5 rounded-lg bg-sidebar/50 border border-line text-[11px] flex flex-wrap items-center justify-between gap-1.5">
                            @if ($item->isExpense())
                                <span class="font-medium text-charcoal">
                                    {{ $item->creditAccount ? '💳 ' . $item->creditAccount->name : ($item->sourceAccount?->name ?? 'Rekening Fleksibel') }}
                                </span>
                                <span class="text-muted">Pos: <strong class="text-charcoal">{{ $item->category?->name }}</strong></span>
                            @elseif ($item->isCreditPayment())
                                <span class="font-medium text-charcoal">
                                    {{ $item->sourceAccount?->name ?: 'Rekening Fleksibel' }} &rarr; 💳 {{ $item->creditAccount?->name }}
                                </span>
                                <span class="text-muted">Bayar Kredit</span>
                            @else
                                <span class="font-medium text-charcoal">
                                    {{ $item->sourceAccount?->name }} &rarr; {{ $item->destinationAccount?->name }}
                                </span>
                                <span class="text-muted">Transfer</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-line/60">
                            @if ($item->isActive())
                                <button
                                    type="button"
                                    onclick="openConfirmModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->next_due_date->toDateString() }}', {{ $item->amount ?: 0 }}, {{ $item->is_variable_amount ? 'true' : 'false' }}, '{{ $item->type }}', {{ $item->source_account_id ?: 'null' }}, {{ $suggestedAmt }})"
                                    class="flex-1 min-w-[100px] min-h-[44px] px-3 py-2 rounded-lg text-xs font-bold bg-forest text-white hover:bg-forest/90 transition inline-flex items-center justify-center gap-1 cursor-pointer"
                                >
                                    ✓ Catat
                                </button>
                            @endif
                            <a href="{{ route('recurring.show', $item) }}" class="min-w-[44px] min-h-[44px] px-3.5 py-2 rounded-lg text-xs font-semibold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center">
                                Detail
                            </a>
                            <a href="{{ route('recurring.edit', $item) }}" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center" title="Ubah Jadwal" aria-label="Ubah Jadwal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('recurring.destroy', $item) }}" class="inline" onsubmit="return confirm('{{ $item->isArchived() ? 'Pulihkan jadwal ini?' : 'Arsipkan jadwal ini?' }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-rose-600 transition inline-flex items-center justify-center cursor-pointer" title="{{ $item->isArchived() ? 'Pulihkan Jadwal' : 'Arsipkan Jadwal' }}" aria-label="{{ $item->isArchived() ? 'Pulihkan Jadwal' : 'Arsipkan Jadwal' }}">
                                    @if ($item->isArchived())
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-line bg-sidebar/50 text-[10px] uppercase font-bold text-muted tracking-wider">
                            <th class="py-3 px-4">Nama Jadwal</th>
                            <th class="py-3 px-4">Member</th>
                            <th class="py-3 px-4">Alur Rekening / Kategori</th>
                            <th class="py-3 px-4">Frekuensi</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-4">Jatuh Tempo Berikutnya</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($schedules as $item)
                            @php
                                $dueStatus = $item->dueStatus($tz);
                            @endphp
                            <tr class="hover:bg-cream/40 transition {{ $item->isArchived() ? 'opacity-60' : '' }}">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold {{ $item->isCreditPayment() ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : ($item->isExpense() ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-slate-500/10 text-charcoal') }}">
                                            {{ $item->isCreditPayment() ? '💳' : ($item->isExpense() ? '🏷️' : '⇄') }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a href="{{ route('recurring.show', $item) }}" class="font-bold text-charcoal hover:text-forest transition">
                                                    {{ $item->name }}
                                                </a>
                                                @if ($item->isPaused())
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 uppercase">
                                                        Dijeda
                                                    </span>
                                                @elseif ($item->isCancelled())
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 uppercase">
                                                        Dibatalkan
                                                    </span>
                                                @elseif ($item->isCompleted())
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-forest/15 text-forest border border-forest/30 uppercase">
                                                        Selesai
                                                    </span>
                                                @elseif ($item->isArchived())
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-muted/15 text-muted border border-line uppercase">
                                                        Diarsipkan
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-muted">
                                                Mulai {{ $item->start_date->format('d/m/Y') }}
                                                @if ($item->end_date)
                                                    s.d. {{ $item->end_date->format('d/m/Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if ($item->member)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background-color: {{ $item->member->color ?: '#0f4c3a' }};">
                                            {{ $item->member->name }}
                                        </span>
                                    @else
                                        <span class="text-muted text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    @if ($item->isExpense())
                                        <div class="font-medium text-charcoal flex items-center gap-1.5 flex-wrap">
                                            @if ($item->creditAccount)
                                                <span>💳 {{ $item->creditAccount->name }}</span>
                                                <span class="text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 uppercase">Kredit</span>
                                            @elseif ($item->sourceAccount)
                                                <span>{{ $item->sourceAccount->name }}</span>
                                            @else
                                                <span class="text-xs text-muted font-normal italic">Rekening Fleksibel</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-muted flex items-center gap-1">
                                            <span>Pos:</span>
                                            <span class="font-bold text-charcoal">{{ $item->category?->name }}</span>
                                        </div>
                                    @elseif ($item->isCreditPayment())
                                        <div class="font-medium text-charcoal flex items-center gap-1 flex-wrap">
                                            <span>{{ $item->sourceAccount?->name ?: 'Rekening Fleksibel' }}</span>
                                            <span class="text-muted">&rarr;</span>
                                            <span>💳 {{ $item->creditAccount?->name }}</span>
                                        </div>
                                        <div class="text-[10px] text-muted">Pelunasan Tagihan Kredit</div>
                                    @else
                                        <div class="font-medium text-charcoal flex items-center gap-1">
                                            <span>{{ $item->sourceAccount?->name }}</span>
                                            <span class="text-muted">&rarr;</span>
                                            <span>{{ $item->destinationAccount?->name }}</span>
                                        </div>
                                        <div class="text-[10px] text-muted">Transfer Internal</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-medium text-charcoal">
                                    @if ($item->frequency === 'weekly')
                                        Mingguan
                                    @elseif ($item->frequency === 'yearly')
                                        Tahunan
                                    @else
                                        Bulanan (Tgl {{ $item->anchor_day }})
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold">
                                    @if ($item->is_variable_amount)
                                        <span class="text-xs text-muted font-normal italic">Fleksibel</span>
                                    @else
                                        <span class="privacy-balance text-sm text-charcoal">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-mono text-xs font-bold text-charcoal">
                                        {{ $item->next_due_date->format('d M Y') }}
                                    </div>
                                    <div>
                                        @if ($dueStatus === 'overdue')
                                            <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 uppercase tracking-wider">
                                                Terlambat
                                            </span>
                                        @elseif ($dueStatus === 'due')
                                            <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 uppercase tracking-wider">
                                                Jatuh Tempo Hari Ini
                                            </span>
                                        @else
                                            <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-sidebar text-muted border border-line uppercase tracking-wider">
                                                Mendatang
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($item->isActive())
                                            @php
                                                $suggestedAmt = $item->isCreditPayment() && $item->is_variable_amount
                                                    ? ($creditSuggestions[$item->credit_account_id]['suggested'] ?? 0)
                                                    : 0;
                                            @endphp
                                            <button 
                                                type="button" 
                                                onclick="openConfirmModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->next_due_date->toDateString() }}', {{ $item->amount ?: 0 }}, {{ $item->is_variable_amount ? 'true' : 'false' }}, '{{ $item->type }}', {{ $item->source_account_id ?: 'null' }}, {{ $suggestedAmt }})"
                                                class="min-h-[44px] lg:min-h-0 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-forest text-white hover:bg-forest/90 transition inline-flex items-center gap-1 shadow-2xs cursor-pointer"
                                                title="Konfirmasi & Catat Transaksi"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                <span>Catat</span>
                                            </button>
                                        @endif

                                        <a 
                                            href="{{ route('recurring.show', $item) }}" 
                                            class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                            title="Rincian Jadwal & Riwayat"
                                            aria-label="Rincian Jadwal & Riwayat"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>

                                        <a 
                                            href="{{ route('recurring.edit', $item) }}" 
                                            class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                            title="Ubah Jadwal"
                                            aria-label="Ubah Jadwal"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>

                                        <form method="POST" action="{{ route('recurring.destroy', $item) }}" class="inline" onsubmit="return confirm('{{ $item->isArchived() ? 'Pulihkan jadwal ini?' : 'Arsipkan jadwal ini?' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-rose-600 hover:border-rose-500/40 hover:bg-rose-500/10 transition inline-flex items-center justify-center cursor-pointer"
                                                title="{{ $item->isArchived() ? 'Pulihkan Jadwal' : 'Arsipkan Jadwal' }}"
                                                aria-label="{{ $item->isArchived() ? 'Pulihkan Jadwal' : 'Arsipkan Jadwal' }}"
                                            >
                                                @if ($item->isArchived())
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Occurrence -->
    <div id="confirmOccurrenceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-start sm:items-center justify-center p-3 sm:p-4">
        <div class="panel-card w-full max-w-md p-4 sm:p-6 rounded-2xl border border-line bg-panel shadow-xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center text-xs font-bold shrink-0">✓</span>
                    <h3 class="text-sm font-bold text-charcoal truncate">Konfirmasi & Catat Transaksi</h3>
                </div>
                <button type="button" onclick="closeConfirmModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg inline-flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream font-bold text-base cursor-pointer shrink-0" aria-label="Tutup modal">&times;</button>
            </div>

            <form method="POST" id="confirmOccurrenceForm" action="" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="due_date" id="modalDueDate">

                <div class="p-3 rounded-xl bg-sidebar border border-line space-y-1">
                    <div class="text-[10px] uppercase font-bold text-muted tracking-wider">Jadwal Terpilih</div>
                    <div id="modalScheduleName" class="text-sm font-bold text-charcoal"></div>
                    <div class="text-xs text-muted flex items-center gap-1">
                        <span>Periode Jatuh Tempo:</span>
                        <span id="modalDueDateLabel" class="font-bold text-charcoal"></span>
                    </div>
                </div>

                <div>
                    <label for="modalSourceAccountId" class="block text-xs font-bold text-charcoal mb-1">
                        Rekening Pembayaran <span class="text-rose-500">*</span>
                    </label>
                    @php
                        $modalLiquidAccounts = $accounts->filter(fn ($acc) => ! $acc->isLiability());
                        $modalCreditAccounts = $accounts->filter(fn ($acc) => $acc->isCreditSpendAccount() && $acc->creditAccount);
                    @endphp
                    <select name="source_account_id" id="modalSourceAccountId" class="field-select w-full text-xs" required>
                        @if ($modalLiquidAccounts->isNotEmpty())
                            <optgroup label="Kas, Bank, E-Wallet &amp; Investasi">
                                @foreach ($modalLiquidAccounts as $acc)
                                    <option value="{{ $acc->id }}" data-account-type="{{ $acc->type }}" data-is-default="{{ $acc->is_default ? '1' : '0' }}">
                                        {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if ($modalCreditAccounts->isNotEmpty())
                            <optgroup label="Kartu Kredit &amp; PayLater">
                                @foreach ($modalCreditAccounts as $acc)
                                    <option value="{{ $acc->id }}" data-account-type="{{ $acc->type }}" data-is-default="0">
                                        💳 {{ $acc->creditAccount->name }} (Sisa Limit: {{ $acc->creditAccount->formatted_available_limit }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <div class="text-[11px] text-muted mt-1" id="modalAccountHelp">
                        Pilih rekening/dompet yang digunakan untuk transaksi ini.
                    </div>
                </div>

                <div>
                    <label for="modalAmount" class="block text-xs font-bold text-charcoal mb-1">
                        Nominal Transaksi (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="modalAmount" 
                        min="1" 
                        class="field-input w-full text-xs font-mono font-bold" 
                        required 
                        onfocus="this.select()"
                        onclick="this.select()"
                    />
                    <div class="text-[11px] text-muted mt-1" id="modalAmountHelp">
                        Nominal rupiah yang akan dicatat dan mengubah saldo rekening.
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                        placeholder="Contoh: Bukti transfer #12345" 
                        class="field-input w-full text-xs" 
                    />
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2 border-t border-line">
                    <button type="button" onclick="closeConfirmModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 py-2 text-xs font-bold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btnConfirmSubmit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 py-2 text-xs font-bold cursor-pointer inline-flex items-center justify-center gap-1.5">
                        <svg id="confirmIcon" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <svg id="confirmSpinner" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="confirmLabel">Konfirmasi Catat</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openConfirmModal(id, name, dueDate, amount, isVariable, type, sourceAccountId = null, suggestedAmount = 0) {
            const modal = document.getElementById('confirmOccurrenceModal');
            const form = document.getElementById('confirmOccurrenceForm');
            form.action = '/recurring/' + id + '/confirm';

            document.getElementById('modalScheduleName').textContent = name;
            document.getElementById('modalDueDate').value = dueDate;
            document.getElementById('modalDueDateLabel').textContent = dueDate;

            const accountSelect = document.getElementById('modalSourceAccountId');
            const accountHelp = document.getElementById('modalAccountHelp');
            if (accountSelect) {
                const allowedTypes = type === 'credit_payment'
                    ? ['bank', 'cash', 'e-wallet']
                    : (type === 'transfer'
                        ? ['bank', 'e-wallet', 'cash', 'investment']
                        : ['bank', 'e-wallet', 'cash', 'investment', 'credit_card', 'paylater']);

                let firstEnabledVal = null;
                let defaultEnabledVal = null;
                let sourceEnabled = false;

                for (let i = 0; i < accountSelect.options.length; i++) {
                    const opt = accountSelect.options[i];
                    const accType = opt.dataset.accountType || '';
                    const isAllowed = allowedTypes.includes(accType);
                    opt.disabled = !isAllowed;
                    opt.hidden = !isAllowed;

                    if (isAllowed) {
                        if (firstEnabledVal === null) firstEnabledVal = opt.value;
                        if (opt.dataset.isDefault === '1' && defaultEnabledVal === null) defaultEnabledVal = opt.value;
                        if (sourceAccountId && String(opt.value) === String(sourceAccountId)) sourceEnabled = true;
                    }
                }

                if (sourceEnabled) {
                    accountSelect.value = String(sourceAccountId);
                } else if (defaultEnabledVal !== null) {
                    accountSelect.value = defaultEnabledVal;
                } else if (firstEnabledVal !== null) {
                    accountSelect.value = firstEnabledVal;
                }
            }

            if (accountHelp) {
                if (type === 'credit_payment') {
                    accountHelp.textContent = 'Pilih rekening kas, bank, atau e-wallet untuk membayar tagihan kartu kredit.';
                } else if (type === 'expense') {
                    accountHelp.textContent = 'Pilih rekening kas/bank atau Kartu Kredit/PayLater yang digunakan.';
                } else {
                    accountHelp.textContent = 'Pilih rekening asal transfer.';
                }
            }

            const amountInput = document.getElementById('modalAmount');
            if (amount > 0) {
                amountInput.value = amount;
            } else if (suggestedAmount > 0) {
                amountInput.value = suggestedAmount;
            } else {
                amountInput.value = '';
            }

            if (isVariable) {
                document.getElementById('modalAmountHelp').textContent = type === 'credit_payment' && suggestedAmount > 0
                    ? 'Terisi otomatis dari tagihan/kewajiban kartu saat ini (dapat Anda ubah sesuai pembayaran riil).'
                    : 'Jadwal ini bertipe nominal fleksibel. Masukkan nominal tagihan aktual periode ini.';
            } else {
                document.getElementById('modalAmountHelp').textContent = 'Nominal tetap sesuai jadwal (dapat disesuaikan jika terdapat selisih).';
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
            const btn = document.getElementById('btnConfirmSubmit');
            const icon = document.getElementById('confirmIcon');
            const spinner = document.getElementById('confirmSpinner');
            const label = document.getElementById('confirmLabel');
            if (icon) icon.classList.add('hidden');
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.textContent = 'Mencatat...';
            if (btn) btn.classList.add('opacity-75', 'pointer-events-none');
        });
    </script>
</x-layouts.app>
