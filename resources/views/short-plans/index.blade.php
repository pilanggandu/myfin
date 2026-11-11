<x-layouts.app title="Short Plan">
    @php
        $hasActiveFilters = ($status !== 'planned') || ($priority !== 'all') || ($search !== '');
        $hasAnyPlans = $allCount > 0;
        $isCovered = $coverageDiff >= 0;
        $showQuickAdd = $errors->any() || request()->boolean('quick_add');
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mb-1">
                Short Plan
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                Daftar rencana belanja dan pengeluaran jangka pendek di ruang <strong class="text-charcoal">{{ $space?->name }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button
                type="button"
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'short-plans-metrics\']')"
                class="privacy-card-toggle inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-semibold text-charcoal transition-colors cursor-pointer focus-visible:outline-2 focus-visible:outline-forest"
                title="Sembunyikan / Tampilkan Nominal Ringkasan Short Plan"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Ringkasan</span>
            </button>

            <button
                type="button"
                onclick="toggleQuickShortPlanForm()"
                class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer focus-visible:outline-2 focus-visible:outline-forest"
                aria-expanded="{{ $showQuickAdd ? 'true' : 'false' }}"
                aria-controls="quickShortPlanPanel"
            >
                Tambah Cepat
            </button>

            <a
                href="{{ route('short-plans.create') }}"
                class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs focus-visible:outline-2 focus-visible:outline-forest"
            >
                + Buat Rencana Lengkap
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1" role="alert">
            <div class="font-bold">Gagal menyimpan perubahan:</div>
            <ul class="list-disc list-inside space-y-0.5 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div data-privacy-scope="short-plans-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card
            label="TOTAL RENCANA AKTIF"
            :value="'Rp ' . number_format($totalPlannedActive, 0, ',', '.')"
            :dark="true"
            :sub="$plannedCount . ' rencana belum terealisasi'"
        />

        <x-metric-card
            label="KECUKUPAN SALDO CAIR"
            :value="$isCovered ? 'Cukup' : '-Rp ' . number_format(abs($coverageDiff), 0, ',', '.')"
            :sub="'Saldo cair: Rp ' . number_format($liquidBalance, 0, ',', '.')"
        />

        <x-metric-card
            label="MENDESAK & TENGGAT DEKAT"
            :value="$urgentOrSoonCount . ' Rencana'"
            sub="Prioritas tinggi atau tempo <= 7 hari"
        />

        <x-metric-card
            label="TEREALISASI BULAN INI"
            :value="'Rp ' . number_format($doneThisMonthTotal, 0, ',', '.')"
            :sub="$doneThisMonthCount . ' rencana selesai periode ini'"
        />
    </div>

    <div id="quickShortPlanPanel" class="panel-card p-5 mb-6 {{ $showQuickAdd ? '' : 'hidden' }}">
        <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-line">
            <div>
                <h2 class="text-sm font-bold text-forest">Tambah Rencana Cepat</h2>
                <p class="text-xs text-muted mt-0.5">Catat rencana pengeluaran singkat dalam satu langkah.</p>
            </div>
            <button
                type="button"
                onclick="toggleQuickShortPlanForm(false)"
                class="min-w-[44px] min-h-[44px] sm:min-w-[34px] sm:min-h-[34px] rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream flex items-center justify-center cursor-pointer"
                aria-label="Tutup form tambah cepat"
            >
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('short-plans.store') }}" id="quickShortPlanForm" class="space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="index">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-5">
                    <label for="quickTitle" class="field-label">
                        Nama Rencana <span class="text-red">*</span>
                    </label>
                    <input
                        type="text"
                        name="title"
                        id="quickTitle"
                        value="{{ old('title') }}"
                        required
                        maxlength="255"
                        placeholder="Contoh: Servis motor rutin, Belanja dapur"
                        class="field-input @error('title') has-error @enderror"
                    >
                    @error('title')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="md:col-span-3">
                    <label for="quickPlannedAmount" class="field-label">
                        Estimasi Biaya (Rp) <span class="text-red">*</span>
                    </label>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="planned_amount"
                        id="quickPlannedAmount"
                        value="{{ old('planned_amount') }}"
                        required
                        placeholder="250.000"
                        class="field-input font-mono font-bold @error('planned_amount') has-error @enderror"
                        oninput="formatShortPlanMonetary(this)"
                    >
                    @error('planned_amount')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="quickPriority" class="field-label">Prioritas</label>
                    <select name="priority" id="quickPriority" class="field-select text-xs">
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Mendesak</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Normal</option>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Santai</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="quickTargetDate" class="field-label">Target Tanggal</label>
                    <input
                        type="date"
                        name="target_date"
                        id="quickTargetDate"
                        value="{{ old('target_date') }}"
                        class="field-input text-xs"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-4">
                    <label for="quickAccountId" class="field-label">Rekening Rencana (Opsional)</label>
                    <select
                        name="account_id"
                        id="quickAccountId"
                        class="field-select text-xs"
                        data-searchable-picker="true"
                        data-picker-kind="account"
                        data-allow-empty="true"
                        data-recent-ids='@json($recentAccountIds ?? [])'
                        data-search-placeholder="Cari rekening..."
                    >
                        <option value="">Pilih nanti saat realisasi</option>
                        @foreach ($liquidAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ (string) old('account_id', $defaultAccount?->id) === (string) $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4">
                    <label for="quickCategoryId" class="field-label">Kategori Pengeluaran (Opsional)</label>
                    <select
                        name="category_id"
                        id="quickCategoryId"
                        class="field-select text-xs"
                        data-category-picker="true"
                        data-allow-empty="true"
                        data-recent-ids='@json($recentExpenseCategoryIds ?? [])'
                        data-search-placeholder="Cari kategori..."
                    >
                        <option value="">Pilih Kategori...</option>
                        @foreach ($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('category_id') === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4">
                    <label for="quickNotes" class="field-label">Catatan (Opsional)</label>
                    <input
                        type="text"
                        name="notes"
                        id="quickNotes"
                        value="{{ old('notes') }}"
                        maxlength="1000"
                        placeholder="Catatan toko, detail, atau keperluan"
                        class="field-input text-xs"
                    >
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button
                    type="button"
                    onclick="toggleQuickShortPlanForm(false)"
                    class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer"
                >
                    Tutup
                </button>
                <button
                    type="submit"
                    id="quickShortPlanSubmitBtn"
                    class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer"
                >
                    Simpan Rencana
                </button>
            </div>
        </form>
    </div>

    <div class="panel-card p-4 mb-5 space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
                <a
                    href="{{ route('short-plans.index', ['status' => 'planned', 'priority' => $priority, 'search' => $search]) }}"
                    class="min-h-[44px] sm:min-h-[34px] inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $status === 'planned' ? 'bg-forest text-white' : 'bg-cream text-charcoal border border-line hover:border-sage' }}"
                >
                    Direncanakan ({{ $plannedCount }})
                </a>
                <a
                    href="{{ route('short-plans.index', ['status' => 'done', 'priority' => $priority, 'search' => $search]) }}"
                    class="min-h-[44px] sm:min-h-[34px] inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $status === 'done' ? 'bg-forest text-white' : 'bg-cream text-charcoal border border-line hover:border-sage' }}"
                >
                    Selesai ({{ $doneCount }})
                </a>
                <a
                    href="{{ route('short-plans.index', ['status' => 'cancelled', 'priority' => $priority, 'search' => $search]) }}"
                    class="min-h-[44px] sm:min-h-[34px] inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $status === 'cancelled' ? 'bg-forest text-white' : 'bg-cream text-charcoal border border-line hover:border-sage' }}"
                >
                    Dibatalkan ({{ $cancelledCount }})
                </a>
                <a
                    href="{{ route('short-plans.index', ['status' => 'all', 'priority' => $priority, 'search' => $search]) }}"
                    class="min-h-[44px] sm:min-h-[34px] inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $status === 'all' ? 'bg-forest text-white' : 'bg-cream text-charcoal border border-line hover:border-sage' }}"
                >
                    Semua ({{ $allCount }})
                </a>
            </div>

            <form method="GET" action="{{ route('short-plans.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">

                <select name="priority" onchange="this.form.submit()" aria-label="Filter prioritas" class="field-select text-xs h-10 sm:h-9 sm:w-40">
                    <option value="all" {{ $priority === 'all' ? 'selected' : '' }}>Semua Prioritas</option>
                    <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>Mendesak</option>
                    <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>Normal</option>
                    <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Santai</option>
                </select>

                <div class="flex items-center gap-2">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari nama atau catatan..."
                        aria-label="Cari short plan"
                        class="field-input text-xs h-10 sm:h-9 sm:w-56"
                    >
                    <button type="submit" class="btn-arus-secondary min-h-[44px] sm:min-h-[36px] text-xs px-3 cursor-pointer shrink-0">
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($shortPlans->isEmpty())
        <div class="panel-card p-10 text-center space-y-3">
            @if (! $hasAnyPlans)
                <div class="w-12 h-12 mx-auto rounded-xl bg-forest-light text-forest flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                </div>
                <h2 class="text-base font-bold text-charcoal">Belum Ada Short Plan</h2>
                <p class="text-xs text-muted max-w-md mx-auto leading-relaxed">
                    Catat rencana belanja atau pengeluaran dekat sebelum uangnya terpakai. Saat sudah dibeli, Anda bisa merealisasikannya langsung menjadi Transaksi.
                </p>
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2">
                    <a href="{{ route('short-plans.create') }}" class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs">
                        + Buat Rencana Pertama
                    </a>
                </div>
            @else
                <h2 class="text-base font-bold text-charcoal">Tidak Ada Rencana yang Cocok</h2>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Filter status, prioritas, atau kata kunci pencarian saat ini belum menemukan data yang sesuai.
                </p>
                @if ($hasActiveFilters)
                    <div class="pt-1">
                        <a href="{{ route('short-plans.index') }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs">
                            Reset Filter
                        </a>
                    </div>
                @endif
            @endif
        </div>
    @else
        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="short-plans-list">
            <div class="px-4 sm:px-5 py-3 border-b border-line flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-charcoal">
                    Menampilkan {{ $shortPlans->count() }} rencana
                </span>
                <button
                    type="button"
                    onclick="toggleCardPrivacy(this)"
                    class="privacy-card-toggle min-h-[34px] px-2.5 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center gap-1.5 cursor-pointer text-[11px]"
                    title="Sembunyikan / Tampilkan Nominal Daftar Rencana"
                >
                    <span class="privacy-toggle-icon"></span>
                    <span class="privacy-toggle-text">Sembunyikan</span>
                </button>
            </div>

            <div class="divide-y divide-line">
                @foreach ($shortPlans as $plan)
                    @php
                        $itemCount = $plan->items->count();
                        $checkedCount = $plan->items->where('is_checked', true)->count();
                        $variance = $plan->varianceAmount();
                    @endphp
                    <div class="p-4 sm:px-5 hover:bg-cream/40 transition flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex items-start sm:items-center flex-wrap gap-2">
                                <a
                                    href="{{ route('short-plans.show', $plan) }}"
                                    class="text-sm font-bold text-charcoal hover:text-forest transition break-words {{ $plan->isCancelled() ? 'line-through text-muted' : '' }}"
                                >
                                    {{ $plan->title }}
                                </a>
                                @include('short-plans.partials._badges', ['plan' => $plan, 'now' => $now])
                            </div>

                            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-muted">
                                @if ($plan->account)
                                    <span>Rekening: <strong class="text-charcoal font-semibold">{{ $plan->account->name }}</strong></span>
                                @endif
                                @if ($plan->category)
                                    <span>Kategori: <strong class="text-charcoal font-semibold">{{ $plan->category->name }}</strong></span>
                                @endif
                                @if ($plan->member)
                                    <span>Member: <strong class="text-charcoal font-semibold">{{ $plan->member->name }}</strong></span>
                                @endif
                                @if ($itemCount > 0)
                                    <span class="font-semibold text-forest dark:text-blue-300">
                                        Rincian: {{ $checkedCount }}/{{ $itemCount }} item selesai
                                    </span>
                                @endif
                                @if ($plan->isDone() && $plan->realized_at)
                                    <span>Selesai: {{ $plan->realized_at->translatedFormat('d M Y') }}</span>
                                @endif
                            </div>

                            @if ($plan->notes)
                                <p class="text-xs text-muted line-clamp-2 m-0">{{ $plan->notes }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between lg:justify-end gap-3 shrink-0 border-t lg:border-t-0 border-line pt-3 lg:pt-0">
                            <div class="sm:text-right">
                                <div class="text-[10.5px] uppercase font-bold tracking-wider text-muted">
                                    {{ $plan->isDone() && $plan->actual_amount !== null ? 'Realisasi' : 'Estimasi' }}
                                </div>
                                @php
                                    $displayAmount = ($plan->isDone() && $plan->actual_amount !== null)
                                        ? $plan->actual_amount
                                        : $plan->planned_amount;
                                @endphp
                                <div class="font-mono text-base font-bold text-charcoal">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($displayAmount, 0, ',', '.') }}">
                                        Rp {{ number_format($displayAmount, 0, ',', '.') }}
                                    </span>
                                </div>
                                @if ($plan->isDone() && $variance !== null && abs($variance) >= 1)
                                    <div class="text-[11px] font-mono {{ $variance > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                        {{ $variance > 0 ? 'Hemat Rp ' . number_format($variance, 0, ',', '.') : 'Lebih Rp ' . number_format(abs($variance), 0, ',', '.') }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center flex-wrap gap-2">
                                @if ($plan->isPlanned())
                                    <button
                                        type="button"
                                        data-realize-config="{{ json_encode([
                                            'action' => route('short-plans.realize', $plan),
                                            'title' => $plan->title,
                                            'plannedAmount' => $plan->planned_amount,
                                            'accountId' => $plan->account_id,
                                            'defaultAccountId' => $defaultAccount?->id,
                                            'categoryId' => $plan->category_id,
                                            'memberId' => $plan->member_id,
                                            'today' => $today,
                                        ]) }}"
                                        onclick="openRealizeModalFromButton(this)"
                                        class="btn-arus-primary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs cursor-pointer"
                                    >
                                        Realisasikan
                                    </button>
                                @elseif ($plan->isDone() && $plan->transaction)
                                    <a
                                        href="{{ route('transactions.show', $plan->transaction) }}"
                                        class="btn-arus-secondary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs"
                                    >
                                        Lihat Transaksi
                                    </a>
                                @endif

                                <a
                                    href="{{ route('short-plans.show', $plan) }}"
                                    class="btn-arus-secondary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs"
                                >
                                    Detail
                                </a>

                                @if ($plan->isCancelled())
                                    <form method="POST" action="{{ route('short-plans.reopen', $plan) }}">
                                        @csrf
                                        <button type="submit" class="btn-arus-secondary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs cursor-pointer">
                                            Aktifkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @include('short-plans.partials._realize-modal', ['redirectTo' => 'index'])

    @push('scripts')
    <script>
        function toggleQuickShortPlanForm(forceState = null) {
            const panel = document.getElementById('quickShortPlanPanel');
            if (!panel) return;
            const shouldOpen = forceState !== null ? Boolean(forceState) : panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !shouldOpen);
            const trigger = document.querySelector('[aria-controls="quickShortPlanPanel"]');
            if (trigger) {
                trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }
            if (shouldOpen) {
                const titleInput = document.getElementById('quickTitle');
                if (titleInput) {
                    setTimeout(() => titleInput.focus(), 30);
                }
            }
        }

        document.getElementById('quickShortPlanForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('quickShortPlanSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            }
        });
    </script>
    @endpush
</x-layouts.app>
