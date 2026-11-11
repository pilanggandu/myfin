<x-layouts.app title="Buat Short Plan Baru">
    <div class="w-full">
        <div class="mb-6">
            <a href="{{ route('short-plans.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Short Plan
            </a>
            <h1 class="font-serif text-3xl text-forest font-normal tracking-tight mt-2 mb-0.5">
                Buat Short Plan Baru
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                Catat rencana belanja atau pengeluaran dekat untuk ruang <strong class="text-charcoal">{{ $space?->name }}</strong>.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1" role="alert">
                <div class="font-bold">Periksa kembali isian form:</div>
                <ul class="list-disc list-inside space-y-0.5 pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('short-plans.store') }}" id="createShortPlanForm" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            @csrf

            <div class="lg:col-span-2 space-y-6">
                <div class="panel-card p-5 sm:p-6 space-y-4">
                    <h2 class="text-sm font-bold text-forest border-b border-line pb-2">
                        1. Informasi Rencana
                    </h2>

                    <div>
                        <label for="planTitleInput" class="field-label">
                            Nama Rencana <span class="text-red">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="planTitleInput"
                            value="{{ old('title') }}"
                            required
                            maxlength="255"
                            placeholder="Contoh: Belanja Mingguan Dapur, Servis Motor, Persiapan Liburan"
                            class="field-input @error('title') has-error @enderror"
                            oninput="updatePlanPreview()"
                        >
                        @error('title')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-1">
                            <label for="planAmountInput" class="field-label">
                                Estimasi Biaya (Rp) <span class="text-red">*</span>
                            </label>
                            <input
                                type="text"
                                inputmode="numeric"
                                name="planned_amount"
                                id="planAmountInput"
                                value="{{ old('planned_amount') }}"
                                required
                                placeholder="500.000"
                                class="field-input font-mono font-bold @error('planned_amount') has-error @enderror"
                                oninput="formatPlanAmountInput(this)"
                            >
                            @error('planned_amount')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="planPriorityInput" class="field-label">
                                Prioritas <span class="text-red">*</span>
                            </label>
                            <select
                                name="priority"
                                id="planPriorityInput"
                                class="field-select text-xs"
                                onchange="updatePlanPreview()"
                            >
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Mendesak</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Normal</option>
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Santai</option>
                            </select>
                        </div>

                        <div>
                            <label for="planTargetDateInput" class="field-label">
                                Target Tanggal (Opsional)
                            </label>
                            <input
                                type="date"
                                name="target_date"
                                id="planTargetDateInput"
                                value="{{ old('target_date') }}"
                                class="field-input text-xs"
                                onchange="updatePlanPreview()"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="planAccountSelect" class="field-label">
                                Rekening Rencana (Opsional)
                            </label>
                            <select
                                name="account_id"
                                id="planAccountSelect"
                                class="field-select text-xs"
                                data-searchable-picker="true"
                                data-picker-kind="account"
                                data-allow-empty="true"
                                data-recent-ids='@json($recentAccountIds ?? [])'
                                data-search-placeholder="Cari rekening..."
                                onchange="updatePlanPreview()"
                            >
                                <option value="">Pilih nanti saat realisasi</option>
                                @foreach ($liquidAccounts as $acc)
                                    <option value="{{ $acc->id }}" data-name="{{ $acc->name }}" {{ (string) old('account_id', $defaultAccount?->id) === (string) $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="planCategorySelect" class="field-label">
                                Kategori Pengeluaran (Opsional)
                            </label>
                            <select
                                name="category_id"
                                id="planCategorySelect"
                                class="field-select text-xs"
                                data-category-picker="true"
                                data-allow-empty="true"
                                data-recent-ids='@json($recentExpenseCategoryIds ?? [])'
                                data-search-placeholder="Cari kategori..."
                                onchange="updatePlanPreview()"
                            >
                                <option value="">Pilih Kategori...</option>
                                @foreach ($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}" data-name="{{ $cat->name }}" {{ (string) old('category_id') === (string) $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($members->isNotEmpty())
                        <div>
                            <label for="planMemberSelect" class="field-label">Member Terkait (Opsional)</label>
                            <select name="member_id" id="planMemberSelect" class="field-select text-xs">
                                <option value="">Tidak ditentukan</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" {{ (string) old('member_id') === (string) $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="planNotesInput" class="field-label">Catatan (Opsional)</label>
                        <textarea
                            name="notes"
                            id="planNotesInput"
                            rows="3"
                            maxlength="1000"
                            placeholder="Detail barang, toko, atau catatan tambahan"
                            class="field-input h-auto py-2.5 text-xs"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="panel-card p-5 sm:p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-line pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-forest">
                                2. Rincian Item Checklist (Opsional)
                            </h2>
                            <p class="text-xs text-muted mt-0.5">
                                Pecah rencana menjadi daftar item agar mudah diceklis satu per satu.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                type="button"
                                onclick="applyItemsTotalToPlan()"
                                class="btn-arus-secondary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs cursor-pointer"
                            >
                                Gunakan Total Item
                            </button>
                            <button
                                type="button"
                                onclick="addShortPlanItemRow()"
                                class="btn-arus-secondary min-h-[44px] sm:min-h-[34px] px-3 py-1.5 text-xs font-bold text-forest cursor-pointer"
                            >
                                + Tambah Item
                            </button>
                        </div>
                    </div>

                    <div id="planItemsContainer" class="space-y-2.5">
                        @php
                            $oldItems = old('items', []);
                        @endphp
                        @foreach ($oldItems as $idx => $oldItem)
                            <div class="short-plan-item-row grid grid-cols-1 sm:grid-cols-12 gap-2 items-center p-2.5 rounded-lg bg-cream/40 border border-line">
                                <div class="sm:col-span-7">
                                    <input
                                        type="text"
                                        name="items[{{ $idx }}][title]"
                                        value="{{ $oldItem['title'] ?? '' }}"
                                        placeholder="Nama item atau kebutuhan"
                                        aria-label="Nama item rincian"
                                        class="field-input text-xs h-9"
                                    >
                                </div>
                                <div class="sm:col-span-4">
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        name="items[{{ $idx }}][estimated_amount]"
                                        value="{{ ! empty($oldItem['estimated_amount']) ? number_format((float) $oldItem['estimated_amount'], 0, ',', '.') : '' }}"
                                        placeholder="Estimasi Rp (opsional)"
                                        aria-label="Estimasi harga item"
                                        class="field-input text-xs h-9 font-mono item-amount-input"
                                        oninput="formatPlanItemAmount(this)"
                                    >
                                </div>
                                <div class="sm:col-span-1 flex justify-end">
                                    <button
                                        type="button"
                                        onclick="removeShortPlanItemRow(this)"
                                        class="min-w-[44px] min-h-[44px] sm:min-w-[36px] sm:min-h-[36px] rounded-lg border border-line text-muted hover:text-red hover:border-red/40 flex items-center justify-center cursor-pointer"
                                        aria-label="Hapus baris item"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="planItemsEmptyHint" class="text-xs text-muted {{ count($oldItems) > 0 ? 'hidden' : '' }}">
                        Belum ada item rincian. Klik "+ Tambah Item" jika ingin membuat daftar belanja terperinci.
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-5">
                <div class="panel-card p-5 space-y-4">
                    <h2 class="text-sm font-bold text-forest border-b border-line pb-2">
                        Ringkasan Rencana
                    </h2>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-muted block">Nama Rencana</span>
                            <strong id="previewPlanTitle" class="text-sm text-charcoal block mt-0.5 break-words">Rencana Baru</strong>
                        </div>

                        <div class="pt-2 border-t border-line flex items-center justify-between gap-2">
                            <span class="text-muted">Estimasi Biaya</span>
                            <strong id="previewPlanAmount" class="font-mono text-base text-forest">Rp 0</strong>
                        </div>

                        <div class="pt-2 border-t border-line flex items-center justify-between gap-2">
                            <span class="text-muted">Total Item Rincian</span>
                            <strong id="previewItemsTotal" class="font-mono text-charcoal">Rp 0 (0 item)</strong>
                        </div>

                        <div class="pt-2 border-t border-line flex items-center justify-between gap-2">
                            <span class="text-muted">Prioritas</span>
                            <strong id="previewPlanPriority" class="text-charcoal">Normal</strong>
                        </div>

                        <div class="pt-2 border-t border-line flex items-center justify-between gap-2">
                            <span class="text-muted">Target Tanggal</span>
                            <strong id="previewPlanDate" class="text-charcoal">Fleksibel</strong>
                        </div>

                        <div class="pt-2 border-t border-line flex items-center justify-between gap-2">
                            <span class="text-muted">Rekening Rencana</span>
                            <strong id="previewPlanAccount" class="text-charcoal truncate max-w-[160px]">Belum dipilih</strong>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-line flex flex-col gap-2">
                        <button
                            type="submit"
                            id="createPlanSubmitBtn"
                            class="btn-arus-primary w-full min-h-[44px] text-xs cursor-pointer"
                        >
                            Simpan Short Plan
                        </button>
                        <a
                            href="{{ route('short-plans.index') }}"
                            class="btn-arus-secondary w-full min-h-[44px] text-xs"
                        >
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        let nextPlanItemIndex = {{ count(old('items', [])) }};

        function formatDigitsId(digits) {
            if (!digits) return '';
            return String(digits).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function parseDigits(val) {
            const clean = String(val || '').replace(/[^\d]/g, '');
            return clean ? parseInt(clean, 10) : 0;
        }

        function formatPlanAmountInput(input) {
            const clean = input.value.replace(/[^\d]/g, '');
            input.value = clean ? formatDigitsId(clean) : '';
            updatePlanPreview();
        }

        function formatPlanItemAmount(input) {
            const clean = input.value.replace(/[^\d]/g, '');
            input.value = clean ? formatDigitsId(clean) : '';
            updatePlanPreview();
        }

        function addShortPlanItemRow() {
            const container = document.getElementById('planItemsContainer');
            const hint = document.getElementById('planItemsEmptyHint');
            if (!container) return;

            const idx = nextPlanItemIndex++;
            const row = document.createElement('div');
            row.className = 'short-plan-item-row grid grid-cols-1 sm:grid-cols-12 gap-2 items-center p-2.5 rounded-lg bg-cream/40 border border-line';
            row.innerHTML = `
                <div class="sm:col-span-7">
                    <input
                        type="text"
                        name="items[${idx}][title]"
                        placeholder="Nama item atau kebutuhan"
                        aria-label="Nama item rincian"
                        class="field-input text-xs h-9"
                    >
                </div>
                <div class="sm:col-span-4">
                    <input
                        type="text"
                        inputmode="numeric"
                        name="items[${idx}][estimated_amount]"
                        placeholder="Estimasi Rp (opsional)"
                        aria-label="Estimasi harga item"
                        class="field-input text-xs h-9 font-mono item-amount-input"
                        oninput="formatPlanItemAmount(this)"
                    >
                </div>
                <div class="sm:col-span-1 flex justify-end">
                    <button
                        type="button"
                        onclick="removeShortPlanItemRow(this)"
                        class="min-w-[44px] min-h-[44px] sm:min-w-[36px] sm:min-h-[36px] rounded-lg border border-line text-muted hover:text-red hover:border-red/40 flex items-center justify-center cursor-pointer"
                        aria-label="Hapus baris item"
                    >
                        &times;
                    </button>
                </div>
            `;

            container.appendChild(row);
            if (hint) hint.classList.add('hidden');

            const firstInput = row.querySelector('input[type="text"]');
            if (firstInput) firstInput.focus();
            updatePlanPreview();
        }

        function removeShortPlanItemRow(btn) {
            const row = btn.closest('.short-plan-item-row');
            if (row) row.remove();
            const container = document.getElementById('planItemsContainer');
            const hint = document.getElementById('planItemsEmptyHint');
            if (container && hint) {
                hint.classList.toggle('hidden', container.children.length > 0);
            }
            updatePlanPreview();
        }

        function calculateItemsSummary() {
            const rows = document.querySelectorAll('#planItemsContainer .short-plan-item-row');
            let total = 0;
            rows.forEach(row => {
                const amtInput = row.querySelector('.item-amount-input');
                total += parseDigits(amtInput?.value);
            });
            return { count: rows.length, total };
        }

        function applyItemsTotalToPlan() {
            const { total } = calculateItemsSummary();
            const amountInput = document.getElementById('planAmountInput');
            if (amountInput && total > 0) {
                amountInput.value = formatDigitsId(total);
                updatePlanPreview();
            }
        }

        function updatePlanPreview() {
            const titleEl = document.getElementById('planTitleInput');
            const amountEl = document.getElementById('planAmountInput');
            const priorityEl = document.getElementById('planPriorityInput');
            const dateEl = document.getElementById('planTargetDateInput');
            const accountEl = document.getElementById('planAccountSelect');

            const previewTitle = document.getElementById('previewPlanTitle');
            const previewAmount = document.getElementById('previewPlanAmount');
            const previewItemsTotal = document.getElementById('previewItemsTotal');
            const previewPriority = document.getElementById('previewPlanPriority');
            const previewDate = document.getElementById('previewPlanDate');
            const previewAccount = document.getElementById('previewPlanAccount');

            if (previewTitle) {
                previewTitle.textContent = titleEl?.value.trim() || 'Rencana Baru';
            }

            if (previewAmount) {
                const amt = parseDigits(amountEl?.value);
                previewAmount.textContent = amt > 0 ? `Rp ${formatDigitsId(amt)}` : 'Rp 0';
            }

            if (previewItemsTotal) {
                const summary = calculateItemsSummary();
                previewItemsTotal.textContent = `Rp ${formatDigitsId(summary.total)} (${summary.count} item)`;
            }

            if (previewPriority && priorityEl) {
                const labels = { high: 'Mendesak', medium: 'Normal', low: 'Santai' };
                previewPriority.textContent = labels[priorityEl.value] || 'Normal';
            }

            if (previewDate && dateEl) {
                previewDate.textContent = dateEl.value || 'Fleksibel';
            }

            if (previewAccount && accountEl) {
                const opt = accountEl.options[accountEl.selectedIndex];
                previewAccount.textContent = (accountEl.value && opt) ? (opt.getAttribute('data-name') || opt.textContent) : 'Belum dipilih';
            }
        }

        document.addEventListener('DOMContentLoaded', updatePlanPreview);
        document.getElementById('createShortPlanForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('createPlanSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            }
        });
    </script>
    @endpush
</x-layouts.app>
