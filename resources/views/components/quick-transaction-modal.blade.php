<div id="quickTransactionModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="quickModalTitle">
    <div class="panel-card bg-panel border border-line rounded-2xl max-w-3xl w-full p-5 sm:p-7 shadow-2xl space-y-4 sm:space-y-5 my-8" onclick="event.stopPropagation()">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between border-b border-line pb-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    ⚡
                </span>
                <div>
                    <h3 id="quickModalTitle" class="font-serif text-lg sm:text-xl text-forest font-normal">
                        Catat Cepat Transaksi
                    </h3>
                    <p class="text-[11px] text-muted">
                        Ketik satu kalimat ekspres atau gunakan templat instan.
                    </p>
                </div>
            </div>
            <button 
                type="button" 
                onclick="closeQuickTransactionModal()" 
                class="w-8 h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm"
                aria-label="Tutup modal"
            >
                ✕
            </button>
        </div>

        <!-- Flash Alert Feedback In Modal -->
        <div id="quickModalAlert" class="hidden transition-all duration-200"></div>

        <!-- 1. Smart Natural Input Bar -->
        <div class="p-3 rounded-xl bg-sidebar/70 border border-line space-y-2">
            <label for="smartQuickInput" class="block text-[11px] font-bold tracking-wider text-muted uppercase">
                Ketik Ekspres (Smart Parser)
            </label>
            <div class="flex gap-2">
                <input 
                    type="text" 
                    id="smartQuickInput" 
                    placeholder="Contoh: Kopi 25rb, Bensin 50rb, Gaji 5jt" 
                    class="field-input flex-1 text-xs"
                    onkeydown="if(event.key === 'Enter') { event.preventDefault(); applyQuickSmartText(); }"
                >
                <button 
                    type="button" 
                    onclick="applyQuickSmartText()" 
                    class="px-3 py-2 rounded-lg text-xs font-bold bg-forest text-white hover:bg-forest-hover transition cursor-pointer"
                >
                    Terapkan
                </button>
            </div>
            <span class="text-[10px] text-muted block">
                Format otomatis mengenali angka, singkatan (rb/jt), dan kategori kata kunci.
            </span>
        </div>

        <!-- 2. Templat Cepat 1-Klik -->
        <div>
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider block mb-2 select-none">
                Pilih Templat Cepat:
            </span>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                <button 
                    type="button" 
                    onclick="applyQuickPreset('Kopi & Sarapan', 25000, 'expense', 'makanan')"
                    class="p-2.5 sm:p-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group shadow-xs"
                >
                    <div class="text-xs font-bold text-charcoal group-hover:text-forest">☕ Kopi Pagi</div>
                    <div class="text-[11px] font-mono text-muted">Rp 25.000</div>
                </button>

                <button 
                    type="button" 
                    onclick="applyQuickPreset('Makan Siang', 35000, 'expense', 'makanan')"
                    class="p-2.5 sm:p-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group shadow-xs"
                >
                    <div class="text-xs font-bold text-charcoal group-hover:text-forest">🍱 Makan Siang</div>
                    <div class="text-[11px] font-mono text-muted">Rp 35.000</div>
                </button>

                <button 
                    type="button" 
                    onclick="applyQuickPreset('Bensin Motor/Mobil', 50000, 'expense', 'transportasi')"
                    class="p-2.5 sm:p-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group shadow-xs"
                >
                    <div class="text-xs font-bold text-charcoal group-hover:text-forest">⛽ Bensin</div>
                    <div class="text-[11px] font-mono text-muted">Rp 50.000</div>
                </button>

                <button 
                    type="button" 
                    onclick="applyQuickPreset('Belanja Minimarket', 100000, 'expense', 'belanja')"
                    class="p-2.5 sm:p-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group shadow-xs"
                >
                    <div class="text-xs font-bold text-charcoal group-hover:text-forest">🛒 Belanja</div>
                    <div class="text-[11px] font-mono text-muted">Rp 100.000</div>
                </button>

                <button 
                    type="button" 
                    onclick="applyQuickPreset('Pulsa & Paket Data', 50000, 'expense', 'utilitas')"
                    class="p-2.5 sm:p-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group shadow-xs"
                >
                    <div class="text-xs font-bold text-charcoal group-hover:text-forest">📱 Pulsa Data</div>
                    <div class="text-[11px] font-mono text-muted">Rp 50.000</div>
                </button>

                <button 
                    type="button" 
                    onclick="applyQuickPreset('Gaji Bulanan', 5000000, 'income', 'gaji')"
                    class="p-2.5 sm:p-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group shadow-xs"
                >
                    <div class="text-xs font-bold text-charcoal group-hover:text-forest">💼 Gaji Pokok</div>
                    <div class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-bold">+5 Juta</div>
                </button>
            </div>
        </div>

        <!-- 3. Form Input Eksekusi -->
        <form method="POST" action="{{ route('transactions.store') }}" id="quickModalForm" class="space-y-3 pt-2 border-t border-line" onsubmit="return validateQuickModalSubmit(event)" novalidate>
            @csrf
            <input type="hidden" name="request_key" id="quickModalRequestKey">

            <!-- Tipe Transaksi Switcher -->
            <div class="grid grid-cols-3 gap-1.5 p-1 rounded-xl bg-sidebar border border-line">
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="expense" class="sr-only peer" checked onchange="onQuickModalTypeChange('expense')">
                    <div class="py-1.5 text-center text-xs font-bold text-muted rounded-lg peer-checked:bg-panel peer-checked:text-rose-500 peer-checked:border-rose-500/30 border border-transparent transition">
                        Pengeluaran
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="income" class="sr-only peer" onchange="onQuickModalTypeChange('income')">
                    <div class="py-1.5 text-center text-xs font-bold text-muted rounded-lg peer-checked:bg-panel peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 peer-checked:border-emerald-500/30 border border-transparent transition">
                        Pemasukan
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="transfer" class="sr-only peer" onchange="onQuickModalTypeChange('transfer')">
                    <div class="py-1.5 text-center text-xs font-bold text-muted rounded-lg peer-checked:bg-panel peer-checked:text-charcoal peer-checked:border-line border border-transparent transition">
                        Transfer
                    </div>
                </label>
            </div>

            <!-- Nominal Input -->
            <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                <div class="flex items-center justify-between text-[11px] font-bold text-muted uppercase mb-1">
                    <span>Nominal Transaksi</span>
                    <span id="quickModalFormattedPreview" class="text-charcoal font-mono">Rp 0</span>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <span class="font-serif text-xl text-muted flex-shrink-0 select-none">Rp</span>
                    <input type="hidden" name="amount" id="quickModalAmount">
                    <input 
                        type="text" 
                        inputmode="numeric" 
                        id="quickModalAmountDisplay" 
                        placeholder="0" 
                        required 
                        class="w-full bg-transparent border-0 p-0 text-2xl font-mono font-bold text-charcoal text-right focus:ring-0 focus:outline-none focus:border-0 borderless-amount-input"
                        style="outline: none !important; border: none !important; box-shadow: none !important;"
                        oninput="onQuickModalAmountDisplayInput(this.value)"
                        onfocus="this.select()"
                        onclick="this.select()"
                    >
                </div>
            </div>

            <!-- Single Account & Category Block (Income & Expense) -->
            <div id="quickModalSingleBlock" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="quickModalAccountId" class="block text-xs font-bold text-charcoal mb-1" id="quickModalAccountLabel">
                        Rekening Sumber
                    </label>
                    <select
                        name="account_id"
                        id="quickModalAccountId"
                        class="field-select w-full text-xs"
                        data-searchable-picker="true"
                        data-picker-kind="account"
                        data-recent-ids='@json($quickRecentAccountIds ?? [])'
                        data-recent-scope="account"
                        data-show-recent-chips="true"
                        data-search-placeholder="Cari rekening..."
                        required
                    >
                        <option value="">Pilih Rekening...</option>
                        @foreach ($quickAccounts ?? [] as $acc)
                            @php
                                $icon = match($acc->type) {
                                    'bank' => '🏦',
                                    'e-wallet' => '📱',
                                    'cash' => '💵',
                                    'investment' => '📈',
                                    default => '💳',
                                };
                            @endphp
                            <option
                                value="{{ $acc->id }}"
                                data-name="{{ $acc->name }}"
                                data-chip-label="{{ $icon }} {{ $acc->name }}"
                                data-search-text="{{ $acc->name }} {{ $acc->type }}{{ $acc->is_default ? ' default' : '' }}"
                                {{ $acc->is_default ? 'selected' : '' }}
                            >
                                {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="quickModalExpenseCatWrap">
                    <label for="quickModalExpenseCatId" class="block text-xs font-bold text-charcoal mb-1">
                        Kategori Pengeluaran
                    </label>
                    <select
                        name="category_id"
                        id="quickModalExpenseCatId"
                        class="field-select w-full text-xs"
                        data-category-picker="true"
                        data-recent-ids='@json($quickRecentExpenseCategoryIds ?? [])'
                        data-recent-scope="expense"
                        data-show-recent-chips="true"
                        data-search-placeholder="Cari kategori pengeluaran..."
                        required
                    >
                        <option value="">Pilih Kategori...</option>
                        @foreach ($quickExpenseCategories ?? [] as $cat)
                            <option value="{{ $cat->id }}" data-name="{{ strtolower($cat->name) }}">
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="quickModalIncomeCatWrap" class="hidden">
                    <label for="quickModalIncomeCatId" class="block text-xs font-bold text-charcoal mb-1">
                        Kategori Pemasukan
                    </label>
                    <select
                        name="category_id"
                        id="quickModalIncomeCatId"
                        class="field-select w-full text-xs"
                        data-category-picker="true"
                        data-recent-ids='@json($quickRecentIncomeCategoryIds ?? [])'
                        data-recent-scope="income"
                        data-show-recent-chips="true"
                        data-search-placeholder="Cari kategori pemasukan..."
                        disabled
                    >
                        <option value="">Pilih Kategori...</option>
                        @foreach ($quickIncomeCategories ?? [] as $cat)
                            <option value="{{ $cat->id }}" data-name="{{ strtolower($cat->name) }}">
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Transfer Block -->
            <div id="quickModalTransferBlock" class="grid grid-cols-1 sm:grid-cols-2 gap-3 hidden">
                <div>
                    <label for="quickModalSourceId" class="block text-xs font-bold text-charcoal mb-1">
                        Rekening Asal
                    </label>
                    <select
                        name="source_account_id"
                        id="quickModalSourceId"
                        class="field-select w-full text-xs"
                        data-searchable-picker="true"
                        data-picker-kind="account"
                        data-recent-ids='@json($quickRecentAccountIds ?? [])'
                        data-recent-scope="account"
                        data-show-recent-chips="true"
                        data-search-placeholder="Cari rekening asal..."
                        onchange="syncQuickModalTransferAccounts()"
                        disabled
                    >
                        <option value="">Pilih Asal...</option>
                        @foreach ($quickAccounts ?? [] as $acc)
                            @php
                                $icon = match($acc->type) {
                                    'bank' => '🏦',
                                    'e-wallet' => '📱',
                                    'cash' => '💵',
                                    'investment' => '📈',
                                    default => '💳',
                                };
                            @endphp
                            <option
                                value="{{ $acc->id }}"
                                data-name="{{ $acc->name }}"
                                data-chip-label="{{ $icon }} {{ $acc->name }}"
                                data-search-text="{{ $acc->name }} {{ $acc->type }}{{ $acc->is_default ? ' default' : '' }}"
                                {{ $acc->is_default ? 'selected' : '' }}
                            >
                                {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="quickModalDestId" class="block text-xs font-bold text-charcoal mb-1">
                        Rekening Tujuan
                    </label>
                    <select
                        name="destination_account_id"
                        id="quickModalDestId"
                        class="field-select w-full text-xs"
                        data-searchable-picker="true"
                        data-picker-kind="account"
                        data-recent-ids='@json($quickRecentAccountIds ?? [])'
                        data-recent-scope="account"
                        data-show-recent-chips="true"
                        data-search-placeholder="Cari rekening tujuan..."
                        onchange="syncQuickModalTransferAccounts()"
                        disabled
                    >
                        <option value="">Pilih Tujuan...</option>
                        @foreach ($quickAccounts ?? [] as $acc)
                            @php
                                $icon = match($acc->type) {
                                    'bank' => '🏦',
                                    'e-wallet' => '📱',
                                    'cash' => '💵',
                                    'investment' => '📈',
                                    default => '💳',
                                };
                            @endphp
                            <option
                                value="{{ $acc->id }}"
                                data-name="{{ $acc->name }}"
                                data-chip-label="{{ $icon }} {{ $acc->name }}"
                                data-search-text="{{ $acc->name }} {{ $acc->type }}{{ $acc->is_default ? ' default' : '' }}"
                            >
                                {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="quickModalTransferSameAccountAlert" class="col-span-1 sm:col-span-2 hidden p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>Rekening tujuan transfer tidak boleh sama dengan rekening asal.</span>
                </div>
            </div>

            <!-- Keterangan & Tanggal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="quickModalDesc" class="block text-xs font-bold text-charcoal mb-1">
                        Keterangan
                    </label>
                    <input 
                        type="text" 
                        name="description" 
                        id="quickModalDesc" 
                        placeholder="Contoh: Makan siang" 
                        class="field-input w-full text-xs"
                        required
                    >
                </div>
                <div>
                    <div class="grid grid-cols-5 gap-1.5">
                        <div class="col-span-3">
                            <label for="quickModalDate" class="block text-xs font-bold text-charcoal mb-1">
                                Tanggal
                            </label>
                            <input 
                                type="date" 
                                name="transaction_date" 
                                id="quickModalDate" 
                                value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->format('Y-m-d') }}" 
                                class="field-input w-full text-xs"
                                required
                            >
                        </div>
                        <div class="col-span-2">
                            <label for="quickModalTime" class="block text-xs font-bold text-charcoal mb-1 flex items-center justify-between">
                                <span>Jam</span>
                                <span class="text-[9px] text-muted font-normal">Opsional</span>
                            </label>
                            <input 
                                type="time" 
                                name="transaction_time" 
                                id="quickModalTime" 
                                value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->format('H:i') }}" 
                                class="field-input w-full text-xs"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-3 border-t border-line">
                <button 
                    type="submit" 
                    id="quickModalBtnSaveClose"
                    name="action" 
                    value="save_and_close" 
                    class="btn-arus-primary flex-1 min-h-[42px] text-xs font-bold cursor-pointer inline-flex items-center justify-center gap-1.5"
                >
                    <svg id="quickSaveCloseSpinner" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="quickSaveCloseLabel">Simpan Transaksi</span>
                </button>
                <button 
                    type="button" 
                    id="quickModalBtnSaveAndAgain"
                    onclick="submitQuickModalAndAgain()" 
                    class="btn-arus-secondary min-h-[42px] px-4 text-xs font-bold inline-flex items-center justify-center gap-1.5 text-forest border-forest/30 hover:bg-forest-light cursor-pointer"
                    title="Simpan transaksi ini dan lanjut input transaksi baru langsung tanpa menutup modal"
                >
                    <svg class="w-4 h-4 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span>Simpan & Input Lagi</span>
                </button>
                <button type="button" onclick="closeQuickTransactionModal()" class="btn-arus-secondary min-h-[42px] px-4 text-xs font-bold cursor-pointer">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let quickModalCurrentType = 'expense';
    let quickModalSessionSavedCount = 0;

    function openQuickTransactionModal() {
        const modal = document.getElementById('quickTransactionModal');
        if (!modal) return;
        
        // Generate random UUID for request_key
        const uuid = 'req-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        document.getElementById('quickModalRequestKey').value = uuid;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Select first active account by default if not yet selected
        const accSelect = document.getElementById('quickModalAccountId');
        if (accSelect && accSelect.options.length > 1 && !accSelect.value) {
            accSelect.selectedIndex = 1;
        }

        setTimeout(() => {
            const smartInput = document.getElementById('smartQuickInput');
            if (smartInput) smartInput.focus();
        }, 100);
    }

    function closeQuickTransactionModal() {
        document.querySelectorAll('.arus-category-popover').forEach(p => p.dispatchEvent(new CustomEvent('arus:force-close')));
        const modal = document.getElementById('quickTransactionModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        if (quickModalSessionSavedCount > 0) {
            window.location.reload();
        }
    }

    function syncQuickModalTransferAccounts() {
        const srcSelect = document.getElementById('quickModalSourceId');
        const dstSelect = document.getElementById('quickModalDestId');
        const alertEl = document.getElementById('quickModalTransferSameAccountAlert');
        if (!srcSelect || !dstSelect) return;

        const srcVal = srcSelect.value;
        const dstVal = dstSelect.value;

        for (let i = 0; i < dstSelect.options.length; i++) {
            const opt = dstSelect.options[i];
            if (!opt.value) continue;
            if (srcVal && opt.value === srcVal) {
                opt.disabled = true;
                if (opt.selected) {
                    dstSelect.value = '';
                }
            } else {
                opt.disabled = false;
            }
        }

        for (let i = 0; i < srcSelect.options.length; i++) {
            const opt = srcSelect.options[i];
            if (!opt.value) continue;
            if (dstVal && opt.value === dstVal) {
                opt.disabled = true;
                if (opt.selected) {
                    srcSelect.value = '';
                }
            } else {
                opt.disabled = false;
            }
        }

        if (alertEl) {
            if (srcSelect.value && dstSelect.value && srcSelect.value === dstSelect.value) {
                alertEl.classList.remove('hidden');
            } else {
                alertEl.classList.add('hidden');
            }
        }
    }

    function validateQuickModalSubmit(e) {
        const amount = parseInt(document.getElementById('quickModalAmount').value, 10) || 0;
        if (amount <= 0) {
            if (e) e.preventDefault();
            showQuickModalAlert('Nominal transaksi wajib diisi dan lebih dari 0.', 'error');
            document.getElementById('quickModalAmountDisplay').focus();
            return false;
        }

        const desc = document.getElementById('quickModalDesc').value.trim();
        if (!desc) {
            if (e) e.preventDefault();
            showQuickModalAlert('Keterangan transaksi wajib diisi.', 'error');
            document.getElementById('quickModalDesc').focus();
            return false;
        }

        if (quickModalCurrentType === 'transfer') {
            const src = document.getElementById('quickModalSourceId').value;
            const dst = document.getElementById('quickModalDestId').value;
            if (!src) {
                if (e) e.preventDefault();
                showQuickModalAlert('Rekening asal transfer wajib dipilih.', 'error');
                document.getElementById('quickModalSourceId').focus();
                return false;
            }
            if (!dst) {
                if (e) e.preventDefault();
                showQuickModalAlert('Rekening tujuan transfer wajib dipilih.', 'error');
                document.getElementById('quickModalDestId').focus();
                return false;
            }
            if (src === dst) {
                if (e) e.preventDefault();
                showQuickModalAlert('Rekening tujuan transfer harus berbeda dengan rekening asal.', 'error');
                const alertEl = document.getElementById('quickModalTransferSameAccountAlert');
                if (alertEl) alertEl.classList.remove('hidden');
                document.getElementById('quickModalDestId').focus();
                return false;
            }
        } else {
            const acc = document.getElementById('quickModalAccountId').value;
            if (!acc) {
                if (e) e.preventDefault();
                showQuickModalAlert('Rekening transaksi wajib dipilih.', 'error');
                document.getElementById('quickModalAccountId').focus();
                return false;
            }

            const cat = quickModalCurrentType === 'income' 
                ? document.getElementById('quickModalIncomeCatId').value 
                : document.getElementById('quickModalExpenseCatId').value;
            if (!cat) {
                if (e) e.preventDefault();
                showQuickModalAlert('Kategori transaksi wajib dipilih.', 'error');
                if (quickModalCurrentType === 'income') {
                    document.getElementById('quickModalIncomeCatId').focus();
                } else {
                    document.getElementById('quickModalExpenseCatId').focus();
                }
                return false;
            }
        }

        if (e) {
            const btn = document.getElementById('quickModalBtnSaveClose');
            const spinner = document.getElementById('quickSaveCloseSpinner');
            const label = document.getElementById('quickSaveCloseLabel');
            const btnAgain = document.getElementById('quickModalBtnSaveAndAgain');
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.textContent = 'Menyimpan...';
            if (btn) btn.classList.add('opacity-75', 'pointer-events-none');
            if (btnAgain) btnAgain.classList.add('opacity-50', 'pointer-events-none');
        }
        return true;
    }

    async function submitQuickModalAndAgain() {
        const form = document.getElementById('quickModalForm');
        const alertBox = document.getElementById('quickModalAlert');
        const btnSaveAgain = document.getElementById('quickModalBtnSaveAndAgain');
        
        if (alertBox) {
            alertBox.classList.add('hidden');
            alertBox.innerHTML = '';
        }

        if (!validateQuickModalSubmit(null)) {
            return;
        }

        const amount = parseInt(document.getElementById('quickModalAmount').value, 10) || 0;
        const desc = document.getElementById('quickModalDesc').value.trim();

        const formData = new FormData(form);
        formData.append('action', 'save_and_again');

        const originalBtnHtml = btnSaveAgain.innerHTML;
        btnSaveAgain.disabled = true;
        btnSaveAgain.classList.add('opacity-70', 'cursor-not-allowed');
        btnSaveAgain.innerHTML = `
            <svg class="animate-spin w-4 h-4 text-forest" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span>Menyimpan...</span>
        `;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                quickModalSessionSavedCount++;
                if (data.recent_category_ids && typeof window.updateCategoryPickersRecentIds === 'function') {
                    window.updateCategoryPickersRecentIds(data.recent_category_ids);
                }
                if (data.recent_account_ids && typeof window.updateSearchablePickersRecentIds === 'function') {
                    window.updateSearchablePickersRecentIds({ account: data.recent_account_ids });
                }
                const formattedAmt = 'Rp ' + Number(amount).toLocaleString('id-ID');
                showQuickModalAlert(`✓ Transaksi "<strong>${escapeHtml(desc)}</strong>" (${formattedAmt}) tersimpan! Total batch sesi ini: ${quickModalSessionSavedCount}. Silakan input transaksi berikutnya.`, 'success');

                document.getElementById('quickModalDesc').value = '';
                document.getElementById('quickModalAmount').value = '';
                document.getElementById('quickModalAmountDisplay').value = '';
                document.getElementById('quickModalFormattedPreview').textContent = 'Rp 0';
                const smartInput = document.getElementById('smartQuickInput');
                if (smartInput) smartInput.value = '';

                const uuid = 'req-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                document.getElementById('quickModalRequestKey').value = uuid;

                setTimeout(() => {
                    if (smartInput) {
                        smartInput.focus();
                    } else {
                        document.getElementById('quickModalAmountDisplay').focus();
                    }
                }, 100);
            } else {
                let errorMsg = data.message || 'Gagal menyimpan transaksi. Periksa input Anda.';
                if (data.errors) {
                    const firstErr = Object.values(data.errors)[0];
                    if (Array.isArray(firstErr) && firstErr.length > 0) {
                        errorMsg = firstErr[0];
                    }
                }
                showQuickModalAlert(errorMsg, 'error');
            }
        } catch (err) {
            showQuickModalAlert('Terjadi kesalahan koneksi atau server. Silakan coba lagi.', 'error');
        } finally {
            btnSaveAgain.disabled = false;
            btnSaveAgain.classList.remove('opacity-70', 'cursor-not-allowed');
            btnSaveAgain.innerHTML = originalBtnHtml;
        }
    }

    function showQuickModalAlert(msg, type) {
        const box = document.getElementById('quickModalAlert');
        if (!box) return;
        box.classList.remove('hidden', 'bg-emerald-50', 'border-emerald-300', 'text-emerald-800', 'dark:bg-emerald-950/40', 'dark:border-emerald-800', 'dark:text-emerald-300', 'bg-rose-50', 'border-rose-300', 'text-rose-800', 'dark:bg-rose-950/40', 'dark:border-rose-800', 'dark:text-rose-300');
        
        if (type === 'success') {
            box.className = 'p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between transition-all duration-200';
        } else {
            box.className = 'p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between transition-all duration-200';
        }

        box.innerHTML = `
            <div class="flex-1">${msg}</div>
            <button type="button" onclick="this.parentElement.classList.add('hidden')" class="ml-2 text-sm font-bold opacity-60 hover:opacity-100 cursor-pointer">&times;</button>
        `;
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function onQuickModalTypeChange(type) {
        quickModalCurrentType = type;
        const singleBlock = document.getElementById('quickModalSingleBlock');
        const transferBlock = document.getElementById('quickModalTransferBlock');
        const accSelect = document.getElementById('quickModalAccountId');
        const accLabel = document.getElementById('quickModalAccountLabel');
        const expWrap = document.getElementById('quickModalExpenseCatWrap');
        const incWrap = document.getElementById('quickModalIncomeCatWrap');
        const expSelect = document.getElementById('quickModalExpenseCatId');
        const incSelect = document.getElementById('quickModalIncomeCatId');
        const srcSelect = document.getElementById('quickModalSourceId');
        const dstSelect = document.getElementById('quickModalDestId');

        if (type === 'transfer') {
            singleBlock.classList.add('hidden');
            transferBlock.classList.remove('hidden');
            accSelect.disabled = true;
            expSelect.disabled = true;
            incSelect.disabled = true;
            srcSelect.disabled = false;
            dstSelect.disabled = false;
            syncQuickModalTransferAccounts();
        } else {
            singleBlock.classList.remove('hidden');
            transferBlock.classList.add('hidden');
            accSelect.disabled = false;
            srcSelect.disabled = true;
            dstSelect.disabled = true;

            if (type === 'income') {
                accLabel.textContent = 'Rekening Tujuan';
                expWrap.classList.add('hidden');
                incWrap.classList.remove('hidden');
                expSelect.disabled = true;
                incSelect.disabled = false;
            } else {
                accLabel.textContent = 'Rekening Sumber';
                expWrap.classList.remove('hidden');
                incWrap.classList.add('hidden');
                expSelect.disabled = false;
                incSelect.disabled = true;
            }
        }
    }

    function onQuickModalAmountDisplayInput(val) {
        const raw = val.replace(/\D/g, '');
        const num = parseInt(raw, 10) || 0;
        document.getElementById('quickModalAmount').value = num > 0 ? num : '';
        document.getElementById('quickModalAmountDisplay').value = num > 0 ? Number(num).toLocaleString('id-ID') : '';
        updateQuickModalAmountText();
    }

    function updateQuickModalAmountText() {
        const amount = parseInt(document.getElementById('quickModalAmount').value, 10) || 0;
        const preview = document.getElementById('quickModalFormattedPreview');
        preview.textContent = 'Rp ' + Number(amount).toLocaleString('id-ID');
    }

    function applyQuickPreset(desc, amount, type, categoryKeyword) {
        // Set type
        const radio = document.querySelector(`input[name="type"][value="${type}"]`);
        if (radio) {
            radio.checked = true;
            onQuickModalTypeChange(type);
        }

        // Set amount
        const amountInput = document.getElementById('quickModalAmount');
        const amountDisplay = document.getElementById('quickModalAmountDisplay');
        amountInput.value = amount;
        amountDisplay.value = amount ? Number(amount).toLocaleString('id-ID') : '';
        updateQuickModalAmountText();

        // Set description
        document.getElementById('quickModalDesc').value = desc;

        // Auto match category
        if (type === 'expense' && categoryKeyword) {
            const expSelect = document.getElementById('quickModalExpenseCatId');
            for (let i = 0; i < expSelect.options.length; i++) {
                const optText = expSelect.options[i].getAttribute('data-name') || '';
                if (optText.includes(categoryKeyword)) {
                    expSelect.selectedIndex = i;
                    break;
                }
            }
        } else if (type === 'income' && categoryKeyword) {
            const incSelect = document.getElementById('quickModalIncomeCatId');
            for (let i = 0; i < incSelect.options.length; i++) {
                const optText = incSelect.options[i].getAttribute('data-name') || '';
                if (optText.includes(categoryKeyword)) {
                    incSelect.selectedIndex = i;
                    break;
                }
            }
        }

        // Focus submit button
        amountInput.focus();
    }

    function applyQuickSmartText() {
        const input = document.getElementById('smartQuickInput');
        const text = input.value.trim();
        if (!text) return;

        let str = text;
        let amount = 0;

        // Match nominal like 50000, 50.000, 50rb, 50k, 5jt, 2.5jt
        const regex = /(\d+(?:[.,]\d+)?)\s*(jt|juta|m|rb|ribu|k)?\b/i;
        const match = str.match(regex);

        if (match) {
            let numStr = match[1].replace(',', '.');
            if (/^\d{1,3}(\.\d{3})+$/.test(match[1])) {
                numStr = match[1].replace(/\./g, '');
            }
            let num = parseFloat(numStr);
            let unit = (match[2] || '').toLowerCase();

            if (unit === 'jt' || unit === 'juta' || unit === 'm') {
                amount = Math.round(num * 1000000);
            } else if (unit === 'rb' || unit === 'ribu' || unit === 'k') {
                amount = Math.round(num * 1000);
            } else {
                amount = Math.round(num);
            }

            str = str.replace(match[0], '').trim();
        }

        let desc = str.replace(/^[-–\u2014:,/]+|[-–\u2014:,/]+$/g, '').trim();
        const lower = text.toLowerCase();
        let type = 'expense';
        let catKeyword = '';

        if (lower.includes('gaji') || lower.includes('salary') || lower.includes('bonus') || lower.includes('dividen') || lower.includes('omset') || lower.includes('pendapatan')) {
            type = 'income';
            catKeyword = 'gaji';
        } else if (lower.includes('top up') || lower.includes('transfer') || lower.includes('tarik tunai')) {
            type = 'transfer';
        } else if (lower.includes('makan') || lower.includes('kopi') || lower.includes('resto') || lower.includes('cafe') || lower.includes('sarapan') || lower.includes('lunch') || lower.includes('dinner')) {
            catKeyword = 'makanan';
        } else if (lower.includes('bensin') || lower.includes('pertamax') || lower.includes('pertalite') || lower.includes('parkir') || lower.includes('tol') || lower.includes('ojol') || lower.includes('gojek') || lower.includes('grab')) {
            catKeyword = 'transportasi';
        } else if (lower.includes('belanja') || lower.includes('supermarket') || lower.includes('indomaret') || lower.includes('alfamart') || lower.includes('pasar')) {
            catKeyword = 'belanja';
        } else if (lower.includes('listrik') || lower.includes('pln') || lower.includes('air') || lower.includes('pdam') || lower.includes('pulsa') || lower.includes('kuota') || lower.includes('wifi') || lower.includes('tagihan')) {
            catKeyword = 'utilitas';
        }

        if (!desc && catKeyword) {
            desc = catKeyword.charAt(0).toUpperCase() + catKeyword.slice(1);
        }

        applyQuickPreset(desc || text, amount, type, catKeyword);
        input.value = '';
    }

    // Keyboard shortcut handler
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('quickTransactionModal');
        const isModalOpen = modal && !modal.classList.contains('hidden');

        if (e.key === 'Escape') {
            if (e.defaultPrevented || document.querySelector('.arus-category-popover[style*="display: block"]')) {
                return;
            }
            closeQuickTransactionModal();
            return;
        }

        if (isModalOpen && e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            submitQuickModalAndAgain();
            return;
        }

        // Press 'c' or 'C' when not inside form field to trigger quick modal
        if (!isModalOpen && (e.key === 'c' || e.key === 'C') && !e.ctrlKey && !e.metaKey && !e.altKey) {
            const active = document.activeElement;
            const isInput = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT' || active.isContentEditable);
            if (!isInput) {
                e.preventDefault();
                openQuickTransactionModal();
            }
        }
    });

    // Close on overlay backdrop click
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('quickTransactionModal');
        if (modal && e.target === modal) {
            closeQuickTransactionModal();
        }
    });
</script>
