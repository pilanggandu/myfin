<x-layouts.app title="Koreksi Transaksi #{{ $transaction->id }}">
    <div class="mb-6">
        <a href="{{ route('transactions.show', $transaction) }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali ke rincian transaksi
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / KOREKSI TRANSAKSI</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-1">
            Koreksi Transaksi #{{ $transaction->id }}
        </h1>
        <p class="text-xs text-muted max-w-2xl">
            Perubahan data transaksi akan dicatat sebagai jejak revisi (audit trail) permanen untuk menjaga integritas pembukuan finansial.
        </p>
    </div>

    <!-- Alert Audit Trail Information -->
    <div class="mb-6 p-4 rounded-xl border border-line bg-panel flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-forest/10 text-forest flex items-center justify-center font-bold text-xs flex-shrink-0">
                v{{ $transaction->version }}
            </span>
            <div>
                <span class="font-bold text-charcoal">Mode Koreksi Terkendali (Optimistic Lock)</span>
                <span class="text-muted block text-[11px]">
                    Koreksi akan menghasilkan revisi versi v{{ $transaction->version + 1 }}.
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Tipe Transaksi:</span>
            <x-badge variant="default">{{ strtoupper($transaction->type) }}</x-badge>
        </div>
    </div>

    <form method="POST" action="{{ route('transactions.update', $transaction) }}" id="editTransactionForm" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="version" value="{{ $transaction->version }}">
        <input type="hidden" name="type" id="hiddenType" value="{{ $transaction->type }}">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- FORM AREA (LEFT COLUMN) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                
                <!-- KARTU 1: NOMINAL UTAMA -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-5">
                    <div class="flex items-center justify-between">
                        <label for="amountInput" class="text-[11px] font-bold tracking-wider text-muted uppercase">
                            1. Nominal Transaksi Baru
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-cream text-muted border border-line">
                            RUPIAH (IDR)
                        </span>
                    </div>

                    <div class="p-4 sm:p-5 rounded-xl bg-sidebar/70 border border-line">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="font-serif text-2xl sm:text-3xl text-muted font-normal select-none flex-shrink-0">Rp</span>
                            <input 
                                type="hidden" 
                                id="amountInput" 
                                name="amount" 
                                value="{{ old('amount', $transaction->totalAmount()) }}" 
                                required
                            >
                            <input 
                                type="text" 
                                inputmode="decimal" 
                                id="amountDisplay" 
                                value="{{ old('amount', $transaction->totalAmount()) ? (fmod((float)old('amount', $transaction->totalAmount()), 1.0) == 0.0 ? number_format((float)old('amount', $transaction->totalAmount()), 0, ',', '.') : number_format((float)old('amount', $transaction->totalAmount()), 2, ',', '.')) : '' }}" 
                                placeholder="0" 
                                required 
                                autofocus 
                                class="w-full bg-transparent border-0 p-0 text-3xl sm:text-4xl font-mono font-bold text-charcoal text-right placeholder:text-muted/40 focus:ring-0 focus:outline-none focus:border-0 borderless-amount-input"
                                style="outline: none !important; border: none !important; box-shadow: none !important;"
                                oninput="onAmountDisplayInput(this.value)"
                                onfocus="this.select()"
                                onclick="this.select()"
                            >
                        </div>

                        <!-- Live Spelled-out Preview -->
                        <div class="text-xs text-muted font-mono mt-2 pt-2 border-t border-line/50 flex items-center justify-between">
                            <span id="amountSpelledPreview">Rp {{ number_format($transaction->totalAmount(), 0, ',', '.') }}</span>
                            <span id="amountSignPreview" class="text-[11px] font-bold text-rose-500">
                                {{ $transaction->isExpense() ? 'Kas Keluar (-)' : ($transaction->isIncome() ? 'Kas Masuk (+)' : 'Rotasi Kas (⇄)') }}
                            </span>
                        </div>

                        <!-- Quick Amount Chips -->
                        <div class="flex items-center gap-1.5 flex-wrap pt-3 mt-1 border-t border-line/40">
                            <span class="text-[10px] font-bold text-muted uppercase mr-1 select-none">Cepat:</span>
                            <button type="button" onclick="addAmount(10000)" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-2 sm:py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +10rb
                            </button>
                            <button type="button" onclick="addAmount(50000)" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-2 sm:py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +50rb
                            </button>
                            <button type="button" onclick="addAmount(100000)" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-2 sm:py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +100rb
                            </button>
                            <button type="button" onclick="addAmount(500000)" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-2 sm:py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +500rb
                            </button>
                            <button type="button" onclick="clearAmount()" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-2 sm:py-1 rounded-md text-xs font-semibold bg-panel border border-line text-muted hover:text-red hover:border-red/40 transition cursor-pointer ml-auto" title="Kosongkan Nominal">
                                Reset
                            </button>
                        </div>
                        @error('amount')
                            <div class="field-error mt-2 text-xs text-red"><span>{{ $message }}</span></div>
                        @enderror
                    </div>
                </div>

                <!-- KARTU 2: REKENING & KATEGORI -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-5">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        2. Rekening & Kategori
                    </div>

                    @if ($transaction->isIncome() || $transaction->isExpense())
                        @php
                            $currentAccountId = $transaction->isIncome() 
                                ? $transaction->destinationAccount()?->id 
                                : $transaction->sourceAccount()?->id;
                            $categories = $transaction->isIncome() ? $incomeCategories : $expenseCategories;
                            $recentCategoryIds = $transaction->isIncome()
                                ? ($recentIncomeCategoryIds ?? [])
                                : ($recentExpenseCategoryIds ?? []);
                            $recentCategoryScope = $transaction->isIncome() ? 'income' : 'expense';
                            $accountLabel = $transaction->isIncome() ? 'Rekening Tujuan (Kas Masuk)' : 'Rekening Sumber (Kas Keluar)';
                        @endphp

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Rekening -->
                            <div>
                                <label for="account_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                    {{ $accountLabel }}
                                </label>
                                <select 
                                    name="account_id" 
                                    id="account_id" 
                                    class="field-select w-full"
                                    data-searchable-picker="true"
                                    data-picker-kind="account"
                                    data-recent-ids='@json($recentAccountIds ?? [])'
                                    data-recent-scope="account"
                                    data-show-recent-chips="true"
                                    data-search-placeholder="Cari rekening..."
                                    required
                                    onchange="updateReceipt()"
                                >
                                    @foreach ($accounts as $acc)
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
                                            data-balance="{{ $acc->currentBalance() }}"
                                            data-name="{{ $acc->name }}"
                                            data-icon="{{ $icon }}"
                                            data-chip-label="{{ $icon }} {{ $acc->name }}"
                                            data-search-text="{{ $acc->name }} {{ $acc->type }}{{ $acc->is_default ? ' default' : '' }}"
                                            {{ (string)old('account_id', $currentAccountId) === (string)$acc->id ? 'selected' : '' }}
                                        >
                                            {{ $icon }} {{ $acc->name }} (Saldo: Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_id')
                                    <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                                @enderror
                            </div>

                            <!-- Kategori -->
                            <div>
                                <label for="category_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                    Kategori Transaksi
                                </label>
                                <select 
                                    name="category_id" 
                                    id="category_id" 
                                    class="field-select w-full {{ $errors->has('category_id') ? 'has-error' : '' }}"
                                    data-category-picker="true"
                                    data-recent-ids='@json($recentCategoryIds)'
                                    data-recent-scope="{{ $recentCategoryScope }}"
                                    data-show-recent-chips="true"
                                    data-search-placeholder="Cari kategori transaksi..."
                                    required
                                    onchange="updateReceipt()"
                                >
                                    @foreach ($categories as $cat)
                                        <option 
                                            value="{{ $cat->id }}" 
                                            data-name="{{ $cat->name }}"
                                            {{ (string)old('category_id', $transaction->category_id) === (string)$cat->id ? 'selected' : '' }}
                                        >
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                                @enderror
                            </div>
                        </div>

                    @elseif ($transaction->isTransfer())
                        @include('transactions.partials._transfer-fields', [
                            'mode' => 'edit',
                            'accounts' => $accounts,
                            'expenseCategories' => $expenseCategories,
                            'recentExpenseCategoryIds' => $recentExpenseCategoryIds ?? [],
                            'recentAccountIds' => $recentAccountIds ?? [],
                            'transaction' => $transaction,
                        ])
                    @endif
                </div>

                <!-- KARTU 3: WAKTU, KETERANGAN & ALASAN KOREKSI -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        3. Rincian & Jejak Audit Koreksi
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tanggal & Jam Transaksi -->
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 sm:gap-2">
                                <div class="sm:col-span-3">
                                    <label for="transaction_date" class="block text-xs font-bold text-charcoal mb-1.5">
                                        Tanggal Transaksi
                                    </label>
                                    <input 
                                        type="date" 
                                        name="transaction_date" 
                                        id="transaction_date" 
                                        value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" 
                                        class="field-input w-full"
                                        required
                                        onchange="updateReceipt()"
                                    >
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="transaction_time" class="block text-xs font-bold text-charcoal mb-1.5 flex items-center justify-between">
                                        <span>Jam</span>
                                        <span class="text-[10px] text-muted font-normal">Opsional</span>
                                    </label>
                                    <input 
                                        type="time" 
                                        name="transaction_time" 
                                        id="transaction_time" 
                                        value="{{ old('transaction_time', $transaction->formattedTime() ?? (app('currentSpace') ? app('currentSpace')->now() : now())->format('H:i')) }}" 
                                        class="field-input w-full"
                                        onchange="updateReceipt()"
                                    >
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mt-1.5">
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="setDatePreset(0)" class="min-h-[40px] sm:min-h-0 px-1 text-[11px] text-muted hover:text-forest underline transition cursor-pointer inline-flex items-center">
                                        Hari ini
                                    </button>
                                    <span class="text-line">&bull;</span>
                                    <button type="button" onclick="setDatePreset(-1)" class="min-h-[40px] sm:min-h-0 px-1 text-[11px] text-muted hover:text-forest underline transition cursor-pointer inline-flex items-center">
                                        Kemarin
                                    </button>
                                </div>
                                <button type="button" onclick="setCurrentHour()" class="min-h-[40px] sm:min-h-0 px-1 text-[11px] text-muted hover:text-forest underline transition cursor-pointer inline-flex items-center">
                                    Jam sekarang
                                </button>
                            </div>
                            @error('transaction_date')
                                <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                            @error('transaction_time')
                                <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                        </div>

                        <!-- Keterangan Transaksi -->
                        <div>
                            <label for="descriptionInput" class="block text-xs font-bold text-charcoal mb-1.5">
                                Keterangan Transaksi
                            </label>
                            <input 
                                type="text" 
                                name="description" 
                                id="descriptionInput" 
                                value="{{ old('description', $transaction->description) }}" 
                                class="field-input w-full"
                                required
                                oninput="updateReceipt()"
                            >
                            @error('description')
                                <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                        </div>

                        @if (!empty($members) && $members->isNotEmpty())
                            <div>
                                <label for="transactionMember" class="block text-xs font-bold text-charcoal mb-1.5 flex items-center justify-between">
                                    <span>Pihak / Member</span>
                                    <span class="text-[11px] text-muted font-normal">Opsional</span>
                                </label>
                                <select name="member_id" id="transactionMember" class="field-select w-full">
                                    <option value="">Tanpa Member (Umum)</option>
                                    @foreach ($members as $m)
                                        <option value="{{ $m->id }}" {{ (string)old('member_id', $transaction->member_id) === (string)$m->id ? 'selected' : '' }}>
                                            {{ $m->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Alasan Koreksi (Audit Trail Requirement) -->
                    <div class="pt-3 border-t border-line/50">
                        <label for="reasonInput" class="block text-xs font-bold text-charcoal mb-1.5">
                            Alasan Koreksi (Wajib untuk Catatan Audit)
                        </label>
                        <input 
                            type="text" 
                            name="reason" 
                            id="reasonInput" 
                            value="{{ old('reason', '') }}" 
                            placeholder="Contoh: Salah input nominal, revisi kategori faktur, ganti rekening" 
                            class="field-input w-full"
                        >
                        <div class="flex items-center gap-1.5 flex-wrap pt-2">
                            <span class="text-[10px] font-bold text-muted uppercase mr-1 select-none">Pilihan Alasan:</span>
                            <button type="button" onclick="setReason('Salah input nominal transaksi')" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2 py-2 sm:py-0.5 rounded text-[11px] bg-sidebar border border-line text-muted hover:text-charcoal hover:border-forest/40 transition cursor-pointer">
                                Salah nominal
                            </button>
                            <button type="button" onclick="setReason('Revisi pengelompokan kategori faktur')" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2 py-2 sm:py-0.5 rounded text-[11px] bg-sidebar border border-line text-muted hover:text-charcoal hover:border-forest/40 transition cursor-pointer">
                                Revisi kategori
                            </button>
                            <button type="button" onclick="setReason('Koreksi rekening pembayaran')" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2 py-2 sm:py-0.5 rounded text-[11px] bg-sidebar border border-line text-muted hover:text-charcoal hover:border-forest/40 transition cursor-pointer">
                                Ganti rekening
                            </button>
                            <button type="button" onclick="setReason('Koreksi tanggal buku nota')" class="min-h-[44px] sm:min-h-0 px-3 sm:px-2 py-2 sm:py-0.5 rounded text-[11px] bg-sidebar border border-line text-muted hover:text-charcoal hover:border-forest/40 transition cursor-pointer">
                                Koreksi tanggal
                            </button>
                        </div>
                        @error('reason')
                            <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                        @enderror
                    </div>
                </div>

                <!-- TOMBOL AKSI -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 pt-2">
                    <button type="submit" id="btnSaveEdit" class="btn-arus-primary min-h-[46px] px-6 text-sm font-bold flex-1 sm:flex-initial inline-flex items-center gap-2 cursor-pointer">
                        <svg id="saveEditIcon" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <svg id="saveEditSpinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="saveEditLabel">Simpan Koreksi Transaksi</span>
                    </button>
                    <a href="{{ route('transactions.show', $transaction) }}" id="btnCancelEdit" class="btn-arus-secondary min-h-[46px] px-5 text-sm font-bold">
                        Batal
                    </a>
                </div>
            </div>

            <!-- RIGHT COLUMN: LIVE BUKTI TRANSAKSI (RECEIPT PREVIEW) -->
            <div class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-[88px] space-y-4">
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel shadow-sm space-y-5">
                    <div class="flex items-center justify-between border-b border-line pb-3">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('arus-logo.svg') }}" alt="" class="arus-logo arus-logo-small">
                            <div>
                                <div class="text-[10px] font-bold text-muted uppercase tracking-wider">STRUK KOREKSI</div>
                                <div class="text-xs font-bold text-charcoal">Revisi #{{ $transaction->id }}</div>
                            </div>
                        </div>
                        <span id="receiptBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                            {{ strtoupper($transaction->type) }}
                        </span>
                    </div>

                    <!-- Nominal Slip Header -->
                    <div class="text-center py-2 bg-sidebar/50 rounded-xl border border-line/60">
                        <div class="text-[10px] font-bold text-muted uppercase tracking-wider mb-1">
                            Nominal Baru
                        </div>
                        <div id="receiptNominal" class="font-mono text-2xl sm:text-3xl font-bold tracking-tight">
                            Rp 0
                        </div>
                        <div id="receiptDateLabel" class="text-[11px] text-muted mt-1">
                            {{ $transaction->transaction_date->format('d F Y') }}
                        </div>
                    </div>

                    <!-- Slip Rincian Data -->
                    <div class="divide-y divide-line/60 text-xs">
                        <div class="py-2.5 flex justify-between items-center">
                            <span class="text-muted">Keterangan:</span>
                            <span id="receiptDesc" class="font-bold text-charcoal text-right max-w-[180px] truncate">
                                {{ $transaction->description }}
                            </span>
                        </div>

                        @if (! $transaction->isTransfer())
                            <div class="py-2.5 flex justify-between items-center" id="receiptCategoryRow">
                                <span class="text-muted">Kategori:</span>
                                <span id="receiptCat" class="font-bold text-charcoal text-right">
                                    {{ $transaction->category?->name ?? '-' }}
                                </span>
                            </div>
                        @endif

                        <div class="py-2.5 space-y-1.5">
                            <div class="flex justify-between items-center">
                                <span class="text-muted" id="receiptAccountLabel">Rekening:</span>
                                <span id="receiptAccName" class="font-bold text-charcoal text-right">
                                    {{ $transaction->sourceAccount()?->name ?? $transaction->destinationAccount()?->name }}
                                </span>
                            </div>
                            <div id="receiptBalanceSimulation" class="text-[11px] text-muted bg-cream/40 p-2 rounded-lg border border-line/40">
                                <!-- Balance simulation details -->
                            </div>
                        </div>
                    </div>

                    <!-- Footer Struk -->
                    <div class="pt-3 border-t border-line flex items-center justify-between text-[11px] text-muted">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            Jejak audit tersimpan
                        </span>
                        <span class="font-bold text-forest">v{{ $transaction->version }} &rarr; v{{ $transaction->version + 1 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        const trxType = '{{ $transaction->type }}';

        function onAmountDisplayInput(val) {
            let cleaned = val.replace(/[^0-9,\.]/g, '');
            let intPart = '';
            let decPart = '';
            let hasComma = false;

            if (cleaned.includes(',')) {
                hasComma = true;
                const parts = cleaned.split(',');
                intPart = parts[0].replace(/\D/g, '');
                decPart = parts.slice(1).join('').replace(/\D/g, '').slice(0, 2);
            } else if (cleaned.includes('.')) {
                const parts = cleaned.split('.');
                if (parts.length === 2 && parts[1].length <= 2 && parts[0].length > 0) {
                    hasComma = true;
                    intPart = parts[0].replace(/\D/g, '');
                    decPart = parts[1].slice(0, 2);
                } else {
                    intPart = cleaned.replace(/\D/g, '');
                }
            } else {
                intPart = cleaned.replace(/\D/g, '');
            }

            const intNum = parseInt(intPart, 10) || 0;
            const formattedInt = intNum > 0 ? Number(intNum).toLocaleString('id-ID') : (intPart === '0' ? '0' : '');

            let displayVal = '';
            let submitVal = '';

            if (formattedInt !== '') {
                displayVal = formattedInt + (hasComma ? ',' + decPart : '');
                submitVal = decPart ? intNum + '.' + decPart : (intNum > 0 ? intNum.toString() : '');
            } else if (hasComma) {
                displayVal = '0,' + decPart;
                submitVal = '0.' + (decPart || '0');
            }

            document.getElementById('amountInput').value = submitVal;
            document.getElementById('amountDisplay').value = displayVal;
            updateReceipt();
        }

        function addAmount(val) {
            const hidden = document.getElementById('amountInput');
            const current = parseFloat(hidden.value) || 0;
            const next = Math.round((current + val) * 100) / 100;
            hidden.value = next;
            const isInt = next % 1 === 0;
            document.getElementById('amountDisplay').value = Number(next).toLocaleString('id-ID', {
                minimumFractionDigits: isInt ? 0 : 2,
                maximumFractionDigits: 2
            });
            updateReceipt();
        }

        function clearAmount() {
            document.getElementById('amountInput').value = '';
            document.getElementById('amountDisplay').value = '';
            updateReceipt();
        }

        function setReason(text) {
            document.getElementById('reasonInput').value = text;
        }

        function setDatePreset(offsetDays) {
            const d = new Date();
            d.setDate(d.getDate() + offsetDays);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            const iso = `${yyyy}-${mm}-${dd}`;
            document.getElementById('transaction_date').value = iso;
            updateReceipt();
        }

        function setCurrentHour() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const timeInput = document.getElementById('transaction_time');
            if (timeInput) {
                timeInput.value = `${hh}:${mm}`;
                updateReceipt();
            }
        }

        function formatRupiah(num) {
            const isInt = num % 1 === 0;
            return 'Rp ' + Number(num).toLocaleString('id-ID', {
                minimumFractionDigits: isInt ? 0 : 2,
                maximumFractionDigits: 2
            });
        }

        function syncFeePresetButtons() {
            const currentFee = parseInt(document.getElementById('fee_amount')?.value || '0', 10) || 0;
            document.querySelectorAll('.fee-preset-btn[data-fee-preset]').forEach(btn => {
                const presetVal = parseInt(btn.getAttribute('data-fee-preset') || '0', 10);
                const isActive = presetVal === currentFee;
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                btn.classList.toggle('bg-forest-light', isActive);
                btn.classList.toggle('border-forest', isActive);
                btn.classList.toggle('text-forest', isActive);
                btn.classList.toggle('dark:text-blue-300', isActive);
                btn.classList.toggle('bg-panel', !isActive);
                btn.classList.toggle('border-line', !isActive);
                btn.classList.toggle('text-charcoal', !isActive);
                btn.classList.toggle('hover:bg-cream', !isActive);
                btn.classList.toggle('hover:border-sage', !isActive);

                const amountLabel = btn.querySelector('.fee-preset-amount');
                if (amountLabel) {
                    amountLabel.classList.toggle('text-forest', isActive);
                    amountLabel.classList.toggle('dark:text-blue-300', isActive);
                    amountLabel.classList.toggle('text-[#526070]', !isActive);
                    amountLabel.classList.toggle('dark:text-muted', !isActive);
                }
            });
        }

        function onFeeDisplayInput(val) {
            const cleanDigits = val.replace(/\D/g, '');
            const num = parseInt(cleanDigits, 10) || 0;
            const feeInput = document.getElementById('fee_amount');
            const feeDisplay = document.getElementById('fee_amount_display');
            if (feeInput) feeInput.value = num;
            if (feeDisplay) feeDisplay.value = num > 0 ? num.toLocaleString('id-ID') : (cleanDigits === '' ? '' : '0');
            syncFeePresetButtons();
            updateReceipt();
        }

        function setFeePreset(val) {
            const feeInput = document.getElementById('fee_amount');
            const feeDisplay = document.getElementById('fee_amount_display');
            if (feeInput) feeInput.value = val;
            if (feeDisplay) feeDisplay.value = val > 0 ? val.toLocaleString('id-ID') : '0';
            syncFeePresetButtons();
            updateReceipt();
        }

        function updateReceipt() {
            const amountVal = parseFloat(document.getElementById('amountInput').value) || 0;
            const dateVal = document.getElementById('transaction_date').value;
            const descVal = document.getElementById('descriptionInput').value.trim();

            const receiptBadge = document.getElementById('receiptBadge');
            const receiptNominal = document.getElementById('receiptNominal');
            const amountSignPreview = document.getElementById('amountSignPreview');
            const receiptDateLabel = document.getElementById('receiptDateLabel');
            const receiptDesc = document.getElementById('receiptDesc');
            const receiptCat = document.getElementById('receiptCat');
            const receiptAccountLabel = document.getElementById('receiptAccountLabel');
            const receiptAccName = document.getElementById('receiptAccName');
            const balanceSimulation = document.getElementById('receiptBalanceSimulation');

            if (dateVal) {
                const parsed = new Date(dateVal + 'T00:00:00');
                const timeVal = document.getElementById('transaction_time')?.value;
                const formattedDate = parsed.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                receiptDateLabel.textContent = timeVal ? `${formattedDate} • ${timeVal}` : formattedDate;
            }

            receiptDesc.textContent = descVal || '(Belum diisi)';

            const spelled = document.getElementById('amountSpelledPreview');
            if (amountVal > 0) {
                spelled.textContent = formatRupiah(amountVal);
            } else {
                spelled.textContent = 'Nol rupiah';
            }

            if (trxType === 'expense') {
                receiptBadge.textContent = 'PENGELUARAN';
                receiptBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20';
                receiptNominal.textContent = '- ' + formatRupiah(amountVal);
                receiptNominal.className = 'font-mono text-2xl sm:text-3xl font-bold text-rose-500 tracking-tight';
                amountSignPreview.textContent = 'Kas Keluar (-)';
                amountSignPreview.className = 'text-[11px] font-bold text-rose-500';

                const catSelect = document.getElementById('category_id');
                if (catSelect && receiptCat) {
                    const selectedCat = catSelect.options[catSelect.selectedIndex];
                    receiptCat.textContent = selectedCat && selectedCat.value ? selectedCat.getAttribute('data-name') : '(Belum dipilih)';
                }

                receiptAccountLabel.textContent = 'Rekening Sumber:';
                const accSelect = document.getElementById('account_id');
                if (accSelect) {
                    const selectedAcc = accSelect.options[accSelect.selectedIndex];
                    if (selectedAcc && selectedAcc.value) {
                        const accName = selectedAcc.getAttribute('data-name');
                        const balance = parseInt(selectedAcc.getAttribute('data-balance'), 10) || 0;
                        receiptAccName.textContent = accName;
                        balanceSimulation.classList.remove('hidden');
                        balanceSimulation.innerHTML = `
                            <div class="flex justify-between">
                                <span>Saldo saat ini:</span>
                                <span class="font-semibold">${formatRupiah(balance)}</span>
                            </div>
                        `;
                    }
                }
            } else if (trxType === 'income') {
                receiptBadge.textContent = 'PEMASUKAN';
                receiptBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
                receiptNominal.textContent = '+ ' + formatRupiah(amountVal);
                receiptNominal.className = 'font-mono text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight';
                amountSignPreview.textContent = 'Kas Masuk (+)';
                amountSignPreview.className = 'text-[11px] font-bold text-emerald-600 dark:text-emerald-400';

                const catSelect = document.getElementById('category_id');
                if (catSelect && receiptCat) {
                    const selectedCat = catSelect.options[catSelect.selectedIndex];
                    receiptCat.textContent = selectedCat && selectedCat.value ? selectedCat.getAttribute('data-name') : '(Belum dipilih)';
                }

                receiptAccountLabel.textContent = 'Rekening Tujuan:';
                const accSelect = document.getElementById('account_id');
                if (accSelect) {
                    const selectedAcc = accSelect.options[accSelect.selectedIndex];
                    if (selectedAcc && selectedAcc.value) {
                        const accName = selectedAcc.getAttribute('data-name');
                        const balance = parseInt(selectedAcc.getAttribute('data-balance'), 10) || 0;
                        receiptAccName.textContent = accName;
                        balanceSimulation.classList.remove('hidden');
                        balanceSimulation.innerHTML = `
                            <div class="flex justify-between">
                                <span>Saldo saat ini:</span>
                                <span class="font-semibold">${formatRupiah(balance)}</span>
                            </div>
                        `;
                    }
                }
            } else if (trxType === 'transfer') {
                receiptBadge.textContent = 'TRANSFER';
                receiptBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-slate-500/10 text-charcoal border border-line';
                receiptNominal.textContent = '⇄ ' + formatRupiah(amountVal);
                receiptNominal.className = 'font-mono text-2xl sm:text-3xl font-bold text-charcoal tracking-tight';

                const feeInput = document.getElementById('fee_amount');
                const feeVal = feeInput ? (parseFloat(feeInput.value) || 0) : 0;

                if (feeVal > 0) {
                    amountSignPreview.textContent = `Rotasi Kas (⇄) • Admin: ${formatRupiah(feeVal)}`;
                    amountSignPreview.className = 'text-[11px] font-bold text-rose-500';
                } else {
                    amountSignPreview.textContent = 'Rotasi Kas (⇄)';
                    amountSignPreview.className = 'text-[11px] font-bold text-charcoal';
                }

                const srcSelect = document.getElementById('source_account_id');
                const dstSelect = document.getElementById('destination_account_id');
                const selectedSrc = srcSelect.options[srcSelect.selectedIndex];
                const selectedDst = dstSelect.options[dstSelect.selectedIndex];

                const srcName = selectedSrc && selectedSrc.value ? selectedSrc.getAttribute('data-name') : 'Rekening Asal';
                const dstName = selectedDst && selectedDst.value ? selectedDst.getAttribute('data-name') : 'Rekening Tujuan';

                receiptAccountLabel.textContent = 'Jalur Transfer:';
                receiptAccName.textContent = `${srcName} → ${dstName}`;

                if (selectedSrc && selectedSrc.value && selectedDst && selectedDst.value) {
                    const srcBal = parseInt(selectedSrc.getAttribute('data-balance'), 10) || 0;
                    const dstBal = parseInt(selectedDst.getAttribute('data-balance'), 10) || 0;
                    const totalSourceDeduction = amountVal + feeVal;

                    balanceSimulation.classList.remove('hidden');
                    balanceSimulation.innerHTML = `
                        <div class="space-y-1.5">
                            <div class="flex justify-between">
                                <span>${srcName}:</span>
                                <span class="font-semibold">${formatRupiah(srcBal)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>${dstName}:</span>
                                <span class="font-semibold">${formatRupiah(dstBal)}</span>
                            </div>
                            ${feeVal > 0 ? `
                            <div class="pt-1.5 mt-1 border-t border-line/40 text-[11px] flex justify-between text-muted">
                                <span>Total Pengurangan Asal:</span>
                                <span class="font-bold text-red">${formatRupiah(totalSourceDeduction)}</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-muted">
                                <span>Beban Biaya Admin:</span>
                                <span class="font-bold text-red">${formatRupiah(feeVal)}</span>
                            </div>
                            ` : ''}
                        </div>
                    `;
                }
            }
        }

        function syncTransferAccounts() {
            const srcSelect = document.getElementById('source_account_id');
            const dstSelect = document.getElementById('destination_account_id');
            const alertEl = document.getElementById('transferSameAccountAlert');
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
                const alertTextEl = document.getElementById('transferSameAccountAlertText');
                if (srcSelect.value && dstSelect.value && srcSelect.value === dstSelect.value) {
                    if (alertTextEl) alertTextEl.textContent = 'Rekening tujuan transfer tidak boleh sama dengan rekening asal.';
                    alertEl.classList.remove('hidden');
                } else {
                    alertEl.classList.add('hidden');
                }
            }
        }

        function onTransferAccountChange() {
            syncTransferAccounts();
            updateReceipt();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (trxType === 'transfer') {
                syncTransferAccounts();
                syncFeePresetButtons();
            }
            updateReceipt();

            const form = document.getElementById('editTransactionForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (trxType === 'transfer') {
                        const src = document.getElementById('source_account_id');
                        const dst = document.getElementById('destination_account_id');
                        if (src && dst && (!src.value || !dst.value || src.value === dst.value)) {
                            e.preventDefault();
                            const alertEl = document.getElementById('transferSameAccountAlert');
                            const alertTextEl = document.getElementById('transferSameAccountAlertText');
                            if (alertTextEl) {
                                if (!src.value) {
                                    alertTextEl.textContent = 'Rekening asal transfer wajib dipilih.';
                                } else if (!dst.value) {
                                    alertTextEl.textContent = 'Rekening tujuan transfer wajib dipilih.';
                                } else {
                                    alertTextEl.textContent = 'Rekening tujuan transfer tidak boleh sama dengan rekening asal.';
                                }
                            }
                            if (alertEl) alertEl.classList.remove('hidden');
                            if (!src.value) {
                                src.focus();
                            } else {
                                dst.focus();
                            }
                            return false;
                        }
                    }

                    if (e.defaultPrevented) return;

                    const btnSave = document.getElementById('btnSaveEdit');
                    const icon = document.getElementById('saveEditIcon');
                    const spinner = document.getElementById('saveEditSpinner');
                    const label = document.getElementById('saveEditLabel');
                    const cancelLink = document.getElementById('btnCancelEdit');

                    if (icon) icon.classList.add('hidden');
                    if (spinner) spinner.classList.remove('hidden');
                    if (label) label.textContent = 'Menyimpan...';
                    if (btnSave) btnSave.classList.add('opacity-75', 'pointer-events-none');
                    if (cancelLink) cancelLink.classList.add('opacity-50', 'pointer-events-none');
                });
            }
        });
    </script>
</x-layouts.app>
