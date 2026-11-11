<x-layouts.app title="{{ $shortPlan->title }} - Short Plan">
    @php
        $itemsCount = $shortPlan->items->count();
        $checkedCount = $shortPlan->checkedItemsCount();
        $itemsTotal = $shortPlan->itemsTotalAmount();
        $checkedAmount = round((float) $shortPlan->items->where('is_checked', true)->sum('estimated_amount'), 2);
        $checklistProgress = $itemsCount > 0 ? (int) round(($checkedCount / $itemsCount) * 100) : 0;
        $variance = $shortPlan->varianceAmount();
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-start sm:items-center gap-3 min-w-0">
            <a
                href="{{ route('short-plans.index') }}"
                class="min-w-[44px] min-h-[44px] sm:min-w-[36px] sm:min-h-[36px] rounded-lg border border-line bg-panel hover:bg-cream flex items-center justify-center text-muted hover:text-charcoal transition shrink-0"
                title="Kembali ke Daftar Short Plan"
                aria-label="Kembali ke daftar short plan"
            >
                &larr;
            </a>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase block">ARUS / SHORT PLAN</span>
                <div class="flex items-center flex-wrap gap-2 mt-0.5">
                    <h1 class="font-serif text-2xl sm:text-3xl text-forest font-normal tracking-tight break-words">
                        {{ $shortPlan->title }}
                    </h1>
                    @include('short-plans.partials._badges', ['plan' => $shortPlan, 'now' => $now])
                </div>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @if ($shortPlan->isPlanned())
                <button
                    type="button"
                    data-realize-config="{{ json_encode([
                        'action' => route('short-plans.realize', $shortPlan),
                        'title' => $shortPlan->title,
                        'plannedAmount' => $shortPlan->planned_amount,
                        'accountId' => $shortPlan->account_id,
                        'defaultAccountId' => $defaultAccount?->id,
                        'categoryId' => $shortPlan->category_id,
                        'memberId' => $shortPlan->member_id,
                        'today' => $today,
                    ]) }}"
                    onclick="openRealizeModalFromButton(this)"
                    class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer"
                >
                    Realisasikan
                </button>
            @else
                <form method="POST" action="{{ route('short-plans.reopen', $shortPlan) }}">
                    @csrf
                    <button type="submit" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer">
                        Aktifkan Kembali
                    </button>
                </form>
            @endif

            <a href="{{ route('short-plans.edit', $shortPlan) }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs">
                Edit Rencana
            </a>

            @if ($shortPlan->isPlanned())
                <form method="POST" action="{{ route('short-plans.cancel', $shortPlan) }}">
                    @csrf
                    <button type="submit" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer">
                        Batalkan
                    </button>
                </form>
            @endif

            <form
                method="POST"
                action="{{ route('short-plans.destroy', $shortPlan) }}"
                onsubmit="return confirm('Hapus rencana ini beserta seluruh rincian itemnya?')"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-arus-danger min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1" role="alert">
            <div class="font-bold">Gagal memproses permintaan:</div>
            <ul class="list-disc list-inside space-y-0.5 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-7 space-y-5">
            <div class="panel-card p-5 sm:p-6 space-y-5" data-privacy-scope="short-plan-detail">
                <div class="flex items-start justify-between gap-3 border-b border-line pb-4">
                    <div>
                        <span class="text-[11px] font-bold tracking-wider uppercase text-muted block">Estimasi Biaya Rencana</span>
                        <div class="font-mono text-3xl sm:text-4xl font-bold text-forest mt-1">
                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($shortPlan->planned_amount, 0, ',', '.') }}">
                                Rp {{ number_format($shortPlan->planned_amount, 0, ',', '.') }}
                            </span>
                        </div>
                        @if ($shortPlan->isDone() && $shortPlan->actual_amount !== null)
                            <div class="mt-2 flex items-center flex-wrap gap-3 text-xs">
                                <span class="text-muted">
                                    Realisasi:
                                    <strong class="font-mono text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($shortPlan->actual_amount, 0, ',', '.') }}">
                                        Rp {{ number_format($shortPlan->actual_amount, 0, ',', '.') }}
                                    </strong>
                                </span>
                                @if ($variance !== null && abs($variance) >= 1)
                                    <span class="font-mono font-bold {{ $variance > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                        {{ $variance > 0 ? 'Hemat Rp ' . number_format($variance, 0, ',', '.') : 'Melebihi Rp ' . number_format(abs($variance), 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <button
                        type="button"
                        onclick="toggleCardPrivacy(this)"
                        class="privacy-card-toggle min-w-[44px] min-h-[44px] sm:min-w-[34px] sm:min-h-[34px] rounded-lg border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer"
                        title="Sembunyikan / Tampilkan Nominal"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-cream/40 border border-line">
                        <span class="text-[10.5px] uppercase font-bold text-muted block">Target Tanggal</span>
                        <strong class="text-charcoal block mt-1">
                            @if ($shortPlan->target_date)
                                {{ $shortPlan->target_date->translatedFormat('d M Y') }}
                                @if ($shortPlan->isPlanned())
                                    <span class="font-normal text-muted">({{ $shortPlan->dueStatusLabel($now) }})</span>
                                @endif
                            @else
                                Fleksibel
                            @endif
                        </strong>
                    </div>

                    <div class="p-3 rounded-xl bg-cream/40 border border-line">
                        <span class="text-[10.5px] uppercase font-bold text-muted block">Rekening Sumber</span>
                        <strong class="text-charcoal block mt-1">
                            {{ $shortPlan->account?->name ?? 'Belum ditentukan' }}
                        </strong>
                    </div>

                    <div class="p-3 rounded-xl bg-cream/40 border border-line">
                        <span class="text-[10.5px] uppercase font-bold text-muted block">Kategori Pengeluaran</span>
                        <strong class="text-charcoal block mt-1">
                            {{ $shortPlan->category?->name ?? 'Belum ditentukan' }}
                        </strong>
                    </div>

                    <div class="p-3 rounded-xl bg-cream/40 border border-line">
                        <span class="text-[10.5px] uppercase font-bold text-muted block">Member Terkait</span>
                        <strong class="text-charcoal block mt-1">
                            {{ $shortPlan->member?->name ?? 'Tidak ada' }}
                        </strong>
                    </div>
                </div>

                @if ($shortPlan->notes)
                    <div class="p-3.5 rounded-xl bg-cream/30 border border-line text-xs text-charcoal leading-relaxed">
                        <span class="text-[10.5px] uppercase font-bold text-muted block mb-1">Catatan</span>
                        {{ $shortPlan->notes }}
                    </div>
                @endif

                @if ($shortPlan->transaction)
                    <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs">
                            <span class="font-bold text-emerald-800 dark:text-emerald-200 block">
                                Terhubung ke Transaksi Pengeluaran
                            </span>
                            <span class="text-emerald-700 dark:text-emerald-300 block mt-0.5">
                                {{ $shortPlan->transaction->description }} ({{ $shortPlan->transaction->transaction_date->translatedFormat('d M Y') }})
                            </span>
                        </div>
                        <a href="{{ route('transactions.show', $shortPlan->transaction) }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs shrink-0">
                            Lihat Transaksi
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-5 space-y-5">
            <div class="panel-card p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between gap-2 border-b border-line pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-forest">Rincian Item Checklist</h2>
                        <p class="text-[11px] text-muted mt-0.5" id="checklistSummaryText">
                            @if ($itemsCount > 0)
                                {{ $checkedCount }} dari {{ $itemsCount }} item selesai
                            @else
                                Pecah kebutuhan belanja menjadi item checklist
                            @endif
                        </p>
                    </div>
                    @if ($itemsCount > 0)
                        <span class="font-mono text-xs font-bold text-forest" id="checklistProgressPercent">{{ $checklistProgress }}%</span>
                    @endif
                </div>

                @if ($itemsCount > 0)
                    <div class="w-full h-2 rounded-full bg-cream overflow-hidden">
                        <div
                            id="checklistProgressBar"
                            class="h-full rounded-full bg-forest transition-all duration-200"
                            style="width: {{ $checklistProgress }}%"
                        ></div>
                    </div>
                @endif

                <div class="divide-y divide-line">
                    @forelse ($shortPlan->items as $item)
                        <div class="py-2.5 flex items-center justify-between gap-3">
                            <form
                                method="POST"
                                action="{{ route('short-plans.items.toggle', [$shortPlan, $item]) }}"
                                class="flex items-center gap-2.5 min-w-0 flex-1"
                            >
                                @csrf
                                <button
                                    type="submit"
                                    class="min-w-[44px] min-h-[44px] sm:min-w-[28px] sm:min-h-[28px] rounded-md border flex items-center justify-center transition cursor-pointer shrink-0 focus-visible:outline-2 focus-visible:outline-forest {{ $item->is_checked ? 'bg-forest border-forest text-white' : 'bg-field-bg border-field-border hover:border-forest text-transparent' }}"
                                    aria-pressed="{{ $item->is_checked ? 'true' : 'false' }}"
                                    aria-label="Tandai item {{ $item->title }}"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </button>
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-semibold block break-words {{ $item->is_checked ? 'line-through text-muted' : 'text-charcoal' }}">
                                        {{ $item->title }}
                                    </span>
                                    @if ($item->estimated_amount > 0)
                                        <span class="text-[11px] font-mono text-muted block">
                                            Rp {{ number_format($item->estimated_amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </form>

                            <form method="POST" action="{{ route('short-plans.items.destroy', [$shortPlan, $item]) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="min-w-[44px] min-h-[44px] sm:min-w-[30px] sm:min-h-[30px] rounded-lg text-muted hover:text-red hover:bg-red/10 transition flex items-center justify-center cursor-pointer"
                                    aria-label="Hapus item {{ $item->title }}"
                                >
                                    &times;
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-muted py-2">
                            Belum ada item rincian. Tambahkan item di bawah jika perlu mencatat daftar belanja rinci.
                        </p>
                    @endforelse
                </div>

                @if ($itemsCount > 0)
                    <div class="pt-3 border-t border-line space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-muted">Total Nominal Rincian</span>
                            <strong class="font-mono text-charcoal">Rp {{ number_format($itemsTotal, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted">Sudah Dicentang</span>
                            <strong class="font-mono text-forest">Rp {{ number_format($checkedAmount, 0, ',', '.') }}</strong>
                        </div>
                        @if ($shortPlan->isPlanned() && $itemsTotal >= 1 && abs($itemsTotal - $shortPlan->planned_amount) >= 1)
                            <form method="POST" action="{{ route('short-plans.sync-amount', $shortPlan) }}" class="pt-1">
                                @csrf
                                <button type="submit" class="btn-arus-secondary w-full min-h-[44px] sm:min-h-[34px] text-xs cursor-pointer">
                                    Sesuaikan Estimasi Rencana ke Rp {{ number_format($itemsTotal, 0, ',', '.') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('short-plans.items.store', $shortPlan) }}" class="pt-3 border-t border-line space-y-2.5">
                    @csrf
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Tambah Item Rincian</div>
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                        <div class="sm:col-span-7">
                            <input
                                type="text"
                                name="title"
                                required
                                maxlength="255"
                                placeholder="Nama item"
                                aria-label="Nama item rincian baru"
                                class="field-input text-xs h-10 sm:h-9"
                            >
                        </div>
                        <div class="sm:col-span-5">
                            <input
                                type="text"
                                inputmode="numeric"
                                name="estimated_amount"
                                placeholder="Harga Rp"
                                aria-label="Estimasi harga item baru"
                                class="field-input text-xs h-10 sm:h-9 font-mono"
                                oninput="formatShortPlanMonetary(this)"
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn-arus-secondary w-full min-h-[44px] sm:min-h-[36px] text-xs cursor-pointer">
                        + Tambah ke Daftar
                    </button>
                </form>
            </div>
        </div>
    </div>

    @include('short-plans.partials._realize-modal', ['redirectTo' => 'show'])
</x-layouts.app>
