<div
    id="realizePlanModal"
    class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="realizeModalTitle"
    onclick="if (event.target === this) closeRealizeModal()"
>
    <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto sm:my-8 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
            <div class="min-w-0">
                <span class="text-[10.5px] font-bold tracking-[1.2px] text-muted uppercase block">Realisasi Rencana</span>
                <h3 id="realizeModalTitle" class="font-serif text-xl text-forest font-normal mt-0.5 truncate">
                    Realisasikan Short Plan
                </h3>
                <p id="realizePlanNameSubtitle" class="text-xs text-muted mt-0.5 truncate"></p>
            </div>
            <button
                type="button"
                onclick="closeRealizeModal()"
                class="min-w-[44px] min-h-[44px] sm:min-w-[36px] sm:min-h-[36px] rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-base shrink-0 focus-visible:outline-2 focus-visible:outline-forest"
                aria-label="Tutup modal realisasi"
            >
                &times;
            </button>
        </div>

        <form method="POST" action="" id="realizePlanForm" class="space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? 'show' }}">
            <input type="hidden" name="request_key" id="realizeRequestKey" value="{{ $requestKey }}">

            <label class="flex items-start gap-3 p-3 rounded-xl bg-cream/60 border border-line cursor-pointer">
                <input
                    type="checkbox"
                    name="record_transaction"
                    id="realizeRecordTxCheckbox"
                    value="1"
                    checked
                    onchange="toggleRealizeTransactionFields(this.checked)"
                    class="field-checkbox mt-0.5"
                >
                <div class="min-w-0">
                    <span class="block text-xs font-bold text-charcoal">Catat sebagai transaksi pengeluaran ke buku kas</span>
                    <span class="block text-[11px] text-muted mt-0.5">
                        Saldo rekening sumber akan berkurang dan tercatat di daftar Transaksi. Matikan jika hanya ingin menandai selesai.
                    </span>
                </div>
            </label>

            <div>
                <label for="realizeActualAmount" class="field-label">
                    Nominal Realisasi (Rp) <span class="text-red">*</span>
                </label>
                <input
                    type="text"
                    inputmode="numeric"
                    name="actual_amount"
                    id="realizeActualAmount"
                    required
                    placeholder="Contoh: 250.000"
                    class="field-input font-mono font-bold text-sm"
                    oninput="formatShortPlanMonetary(this)"
                >
                <p id="realizePlannedHint" class="field-hint"></p>
            </div>

            <div id="realizeTransactionFields" class="space-y-3.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="realizeAccountId" class="field-label">
                            Rekening Sumber <span class="text-red">*</span>
                        </label>
                        <select
                            name="account_id"
                            id="realizeAccountId"
                            class="field-select text-xs"
                            data-searchable-picker="true"
                            data-picker-kind="account"
                            data-recent-ids='@json($recentAccountIds ?? [])'
                            data-search-placeholder="Cari rekening..."
                        >
                            <option value="">Pilih Rekening...</option>
                            @foreach ($liquidAccounts as $acc)
                                <option value="{{ $acc->id }}" data-name="{{ $acc->name }}" data-chip-label="{{ $acc->name }}">
                                    {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="realizeCategoryId" class="field-label">
                            Kategori Pengeluaran <span class="text-red">*</span>
                        </label>
                        <select
                            name="category_id"
                            id="realizeCategoryId"
                            class="field-select text-xs"
                            data-category-picker="true"
                            data-recent-ids='@json($recentExpenseCategoryIds ?? [])'
                            data-search-placeholder="Cari kategori..."
                        >
                            <option value="">Pilih Kategori...</option>
                            @foreach ($expenseCategories as $cat)
                                <option value="{{ $cat->id }}" data-name="{{ $cat->name }}">
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="realizeTransactionDate" class="field-label">
                            Tanggal Transaksi <span class="text-red">*</span>
                        </label>
                        <input
                            type="date"
                            name="transaction_date"
                            id="realizeTransactionDate"
                            value="{{ $today }}"
                            class="field-input text-xs"
                        >
                    </div>

                    @if ($members->isNotEmpty())
                        <div>
                            <label for="realizeMemberId" class="field-label">Member (Opsional)</label>
                            <select name="member_id" id="realizeMemberId" class="field-select text-xs">
                                <option value="">Tanpa Member</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div>
                    <label for="realizeDescription" class="field-label">Keterangan Transaksi</label>
                    <input
                        type="text"
                        name="description"
                        id="realizeDescription"
                        maxlength="255"
                        placeholder="Keterangan yang tampil di daftar transaksi"
                        class="field-input text-xs"
                    >
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-line">
                <button
                    type="button"
                    onclick="closeRealizeModal()"
                    class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    id="realizeSubmitBtn"
                    class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer"
                >
                    Konfirmasi Selesai
                </button>
            </div>
        </form>
    </div>
</div>

@pushOnce('scripts')
<script>
    function formatRupiahDigits(digits) {
        if (!digits) return '';
        return String(digits).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatShortPlanMonetary(input) {
        if (!input) return;
        const clean = input.value.replace(/[^\d]/g, '');
        input.value = clean ? formatRupiahDigits(clean) : '';
    }

    function toggleRealizeTransactionFields(enabled) {
        const block = document.getElementById('realizeTransactionFields');
        const accountEl = document.getElementById('realizeAccountId');
        const categoryEl = document.getElementById('realizeCategoryId');
        const dateEl = document.getElementById('realizeTransactionDate');
        if (block) {
            block.classList.toggle('hidden', !enabled);
        }
        if (accountEl) accountEl.required = Boolean(enabled);
        if (categoryEl) categoryEl.required = Boolean(enabled);
        if (dateEl) dateEl.required = Boolean(enabled);
    }

    function openRealizeModalFromButton(btn) {
        if (!btn) return;
        const raw = btn.getAttribute('data-realize-config');
        if (!raw) return;
        try {
            const config = JSON.parse(raw);
            openRealizeModal(config);
        } catch (e) {}
    }

    function openRealizeModal(config) {
        const modal = document.getElementById('realizePlanModal');
        const form = document.getElementById('realizePlanForm');
        if (!modal || !form) return;

        form.action = config.action;

        const subtitle = document.getElementById('realizePlanNameSubtitle');
        if (subtitle) {
            subtitle.textContent = config.title || '';
        }

        const amountInput = document.getElementById('realizeActualAmount');
        if (amountInput) {
            const rawInt = Math.round(Number(config.plannedAmount || 0));
            amountInput.value = rawInt > 0 ? formatRupiahDigits(rawInt) : '';
        }

        const hint = document.getElementById('realizePlannedHint');
        if (hint) {
            const formattedPlan = formatRupiahDigits(Math.round(Number(config.plannedAmount || 0)));
            hint.textContent = formattedPlan ? `Estimasi awal rencana: Rp ${formattedPlan}` : '';
        }

        const descEl = document.getElementById('realizeDescription');
        if (descEl) {
            descEl.value = config.title || '';
        }

        const accountEl = document.getElementById('realizeAccountId');
        if (accountEl) {
            accountEl.value = config.accountId ? String(config.accountId) : (config.defaultAccountId ? String(config.defaultAccountId) : '');
        }

        const categoryEl = document.getElementById('realizeCategoryId');
        if (categoryEl) {
            categoryEl.value = config.categoryId ? String(config.categoryId) : '';
        }

        const memberEl = document.getElementById('realizeMemberId');
        if (memberEl) {
            memberEl.value = config.memberId ? String(config.memberId) : '';
        }

        const dateEl = document.getElementById('realizeTransactionDate');
        if (dateEl && config.today) {
            dateEl.value = config.today;
        }

        const checkbox = document.getElementById('realizeRecordTxCheckbox');
        if (checkbox) {
            checkbox.checked = true;
            toggleRealizeTransactionFields(true);
        }

        const submitBtn = document.getElementById('realizeSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Konfirmasi Selesai';
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        }, 40);
    }

    function closeRealizeModal() {
        const modal = document.getElementById('realizePlanModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            closeRealizeModal();
        }
    });

    document.getElementById('realizePlanForm')?.addEventListener('submit', function () {
        const submitBtn = document.getElementById('realizeSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }
    });
</script>
@endPushOnce
