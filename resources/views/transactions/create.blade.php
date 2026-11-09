<x-layouts.app title="Catat Transaksi">
    <div class="mb-6">
        <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar transaksi
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / CATAT TRANSAKSI BARU</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-1">
            Catat Transaksi Keuangan
        </h1>
        <p class="text-xs text-muted max-w-2xl">
            Catat arus pengeluaran harian, kas masuk penghasilan, atau perpindahan dana antar-rekening dengan presisi dobel-entri.
        </p>
    </div>

    @if ($accounts->isEmpty())
        <div class="panel-card p-8 text-center space-y-4 max-w-xl mx-auto border border-line rounded-2xl bg-panel">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto text-xl">
                ⚠️
            </div>
            <div>
                <h3 class="font-bold text-base text-charcoal">Belum Ada Rekening Aktif</h3>
                <p class="text-xs text-muted mt-1 max-w-md mx-auto">
                    Anda perlu membuat setidaknya satu rekening atau dompet sebelum dapat mulai mencatat transaksi keuangan.
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('accounts.create') }}" class="btn-arus-primary">
                    + Tambah Rekening Sekarang
                </a>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('transactions.store') }}" id="transactionForm" novalidate>
            @csrf
            <input type="hidden" name="request_key" value="{{ $requestKey }}">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- FORM AREA (LEFT COLUMN) -->
                <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                    <!-- KARTU PINTAS: QUICK INPUT & TEMPLAT CEPAT -->
                    <div class="panel-card p-5 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-forest text-white flex items-center justify-center text-xs font-bold">⚡</span>
                                <span class="text-xs font-bold text-charcoal">Quick Input & Templat Cepat</span>
                            </div>
                            <span class="text-[10px] text-muted">Input 1-Klik</span>
                        </div>

                        <!-- Smart Natural Input Bar -->
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                id="pageSmartInput" 
                                placeholder="Ketik cepat: contoh 'Kopi 25rb', 'Bensin 50rb', atau 'Gaji 5jt'..." 
                                class="field-input flex-1 text-xs"
                                onkeydown="if(event.key === 'Enter') { event.preventDefault(); applyPageSmartText(); }"
                            >
                            <button 
                                type="button" 
                                onclick="applyPageSmartText()" 
                                class="px-3.5 py-2 rounded-lg text-xs font-bold bg-forest text-white hover:bg-forest-hover transition cursor-pointer flex-shrink-0"
                            >
                                Terapkan
                            </button>
                        </div>

                        <!-- 1-Click Preset Chips -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1">
                            <button 
                                type="button" 
                                onclick="applyPagePreset('Kopi Pagi & Sarapan', 25000, 'expense', 'makanan')"
                                class="p-2 rounded-lg border border-line bg-sidebar/60 hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group"
                            >
                                <div class="text-xs font-bold text-charcoal group-hover:text-forest">☕ Kopi Pagi</div>
                                <div class="text-[11px] font-mono text-muted">Rp 25.000</div>
                            </button>

                            <button 
                                type="button" 
                                onclick="applyPagePreset('Makan Siang', 35000, 'expense', 'makanan')"
                                class="p-2 rounded-lg border border-line bg-sidebar/60 hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group"
                            >
                                <div class="text-xs font-bold text-charcoal group-hover:text-forest">🍱 Makan Siang</div>
                                <div class="text-[11px] font-mono text-muted">Rp 35.000</div>
                            </button>

                            <button 
                                type="button" 
                                onclick="applyPagePreset('Bensin Motor/Mobil', 50000, 'expense', 'transportasi')"
                                class="p-2 rounded-lg border border-line bg-sidebar/60 hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group"
                            >
                                <div class="text-xs font-bold text-charcoal group-hover:text-forest">⛽ Bensin</div>
                                <div class="text-[11px] font-mono text-muted">Rp 50.000</div>
                            </button>

                            <button 
                                type="button" 
                                onclick="applyPagePreset('Belanja Minimarket', 100000, 'expense', 'belanja')"
                                class="p-2 rounded-lg border border-line bg-sidebar/60 hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group"
                            >
                                <div class="text-xs font-bold text-charcoal group-hover:text-forest">🛒 Belanja</div>
                                <div class="text-[11px] font-mono text-muted">Rp 100.000</div>
                            </button>

                            <button 
                                type="button" 
                                onclick="applyPagePreset('Pulsa & Paket Data', 50000, 'expense', 'utilitas')"
                                class="p-2 rounded-lg border border-line bg-sidebar/60 hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group"
                            >
                                <div class="text-xs font-bold text-charcoal group-hover:text-forest">📱 Pulsa Data</div>
                                <div class="text-[11px] font-mono text-muted">Rp 50.000</div>
                            </button>

                            <button 
                                type="button" 
                                onclick="applyPagePreset('Gaji Bulanan', 5000000, 'income', 'gaji')"
                                class="p-2 rounded-lg border border-line bg-sidebar/60 hover:bg-cream hover:border-forest/40 transition text-left cursor-pointer group"
                            >
                                <div class="text-xs font-bold text-charcoal group-hover:text-forest">💼 Gaji Pokok</div>
                                <div class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-bold">+5 Juta</div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- KARTU 1: JENIS & NOMINAL UTAMA -->
                    <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-5">
                        <!-- Jenis Transaksi (Segmented Switcher) -->
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider text-muted uppercase mb-2">
                                1. Jenis Transaksi
                            </label>
                            <div class="grid grid-cols-3 gap-2 p-1.5 rounded-xl bg-sidebar border border-line" id="typeSwitcher">
                                <label class="cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="expense" 
                                        class="sr-only peer" 
                                        {{ old('type', $defaultType) === 'expense' ? 'checked' : '' }} 
                                        onchange="onTypeChanged('expense')"
                                    >
                                    <div class="py-2.5 px-3 rounded-lg text-xs font-bold text-muted text-center flex items-center justify-center gap-1.5 transition border border-transparent peer-checked:bg-panel peer-checked:border-rose-500/30 peer-checked:text-rose-500 dark:peer-checked:text-rose-400 peer-checked:shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                                        </svg>
                                        <span>Pengeluaran</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="income" 
                                        class="sr-only peer" 
                                        {{ old('type', $defaultType) === 'income' ? 'checked' : '' }} 
                                        onchange="onTypeChanged('income')"
                                    >
                                    <div class="py-2.5 px-3 rounded-lg text-xs font-bold text-muted text-center flex items-center justify-center gap-1.5 transition border border-transparent peer-checked:bg-panel peer-checked:border-emerald-500/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 peer-checked:shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 4.5-15 15m0 0h11.25m-11.25 0V8.25" />
                                        </svg>
                                        <span>Pemasukan</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="transfer" 
                                        class="sr-only peer" 
                                        {{ old('type', $defaultType) === 'transfer' ? 'checked' : '' }} 
                                        onchange="onTypeChanged('transfer')"
                                    >
                                    <div class="py-2.5 px-3 rounded-lg text-xs font-bold text-muted text-center flex items-center justify-center gap-1.5 transition border border-transparent peer-checked:bg-panel peer-checked:border-line peer-checked:text-charcoal peer-checked:shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                        </svg>
                                        <span>Transfer</span>
                                    </div>
                                </label>
                            </div>
                            @error('type')
                                <div class="field-error mt-1.5 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                        </div>

                        <!-- Hero Nominal Input Box -->
                        <div class="p-4 sm:p-5 rounded-xl bg-sidebar/70 border border-line">
                            <div class="flex items-center justify-between mb-2">
                                <label for="amountInput" class="text-[11px] font-bold tracking-wider text-muted uppercase">
                                    2. Nominal Transaksi
                                </label>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-cream text-muted border border-line">
                                    RUPIAH (IDR)
                                </span>
                            </div>

                            <div class="flex items-baseline justify-between gap-3">
                                <span class="font-serif text-2xl sm:text-3xl text-muted font-normal select-none flex-shrink-0">Rp</span>
                                <input 
                                    type="hidden" 
                                    id="amountInput" 
                                    name="amount" 
                                    value="{{ old('amount', '') }}" 
                                    required
                                >
                                <input 
                                    type="text" 
                                    inputmode="decimal" 
                                    id="amountDisplay" 
                                    value="{{ old('amount') ? (fmod((float)old('amount'), 1.0) == 0.0 ? number_format((float)old('amount'), 0, ',', '.') : number_format((float)old('amount'), 2, ',', '.')) : '' }}" 
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
                                <span id="amountSpelledPreview">Nol rupiah</span>
                                <span id="amountSignPreview" class="text-[11px] font-bold text-rose-500">Kas Keluar (-)</span>
                            </div>

                            <!-- Quick Amount Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap pt-3 mt-1 border-t border-line/40">
                                <span class="text-[10px] font-bold text-muted uppercase mr-1 select-none">Cepat:</span>
                                <button type="button" onclick="addAmount(10000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                    +10rb
                                </button>
                                <button type="button" onclick="addAmount(50000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                    +50rb
                                </button>
                                <button type="button" onclick="addAmount(100000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                    +100rb
                                </button>
                                <button type="button" onclick="addAmount(500000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                    +500rb
                                </button>
                                <button type="button" onclick="addAmount(1000000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                    +1jt
                                </button>
                                <button type="button" onclick="clearAmount()" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-muted hover:text-red hover:border-red/40 transition cursor-pointer ml-auto" title="Kosongkan Nominal">
                                    Reset
                                </button>
                            </div>
                            @error('amount')
                                <div class="field-error mt-2 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                        </div>
                    </div>

                    <!-- KARTU 2: ALUR REKENING & KATEGORI -->
                    <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-5">
                        <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                            3. Rekening & Kategori
                        </div>

                        <!-- Single Account Flow (Income & Expense) -->
                        <div id="singleAccountBlock" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Rekening Sumber/Tujuan -->
                            <div>
                                <label for="account_id" class="block text-xs font-bold text-charcoal mb-1.5" id="accountLabel">
                                    Rekening Sumber (Kas Keluar)
                                </label>
                                <select 
                                    name="account_id" 
                                    id="account_id" 
                                    class="field-select w-full"
                                    onchange="updateReceipt()"
                                >
                                    <option value="">Pilih Rekening...</option>
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
                                            {{ (string)old('account_id', request('account_id', $defaultAccount?->id)) === (string)$acc->id ? 'selected' : '' }}
                                        >
                                            {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Saldo: Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_id')
                                    <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                                @enderror
                            </div>

                            <!-- Kategori (Expense) -->
                            <div id="expenseCategoryContainer">
                                <label for="expense_category_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                    Kategori Pengeluaran
                                </label>
                                <select 
                                    name="category_id" 
                                    id="expense_category_id" 
                                    class="field-select w-full"
                                    onchange="updateReceipt()"
                                >
                                    <option value="">Pilih Kategori Pengeluaran...</option>
                                    @foreach ($expenseCategories as $cat)
                                        <option 
                                            value="{{ $cat->id }}" 
                                            data-name="{{ $cat->name }}"
                                            {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}
                                        >
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                                @enderror
                            </div>

                            <!-- Kategori (Income) -->
                            <div id="incomeCategoryContainer" class="hidden">
                                <label for="income_category_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                    Kategori Pemasukan
                                </label>
                                <select 
                                    name="category_id" 
                                    id="income_category_id" 
                                    class="field-select w-full" 
                                    disabled
                                    onchange="updateReceipt()"
                                >
                                    <option value="">Pilih Kategori Pemasukan...</option>
                                    @foreach ($incomeCategories as $cat)
                                        <option 
                                            value="{{ $cat->id }}" 
                                            data-name="{{ $cat->name }}"
                                            {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}
                                        >
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Transfer Flow (Antar-Rekening) -->
                        <div id="transferBlock" class="hidden space-y-4">
                            <div class="p-4 rounded-xl bg-sidebar/50 border border-line space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-11 gap-3 items-center">
                                    <!-- Rekening Asal -->
                                    <div class="sm:col-span-5">
                                        <label for="source_account_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                            Dari Rekening (Asal)
                                        </label>
                                        <select 
                                            name="source_account_id" 
                                            id="source_account_id" 
                                            class="field-select w-full"
                                            disabled
                                            onchange="onTransferAccountChange()"
                                        >
                                            <option value="">Pilih Rekening Asal...</option>
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
                                                    {{ (string)old('source_account_id', request('source_account_id', $defaultAccount?->id)) === (string)$acc->id ? 'selected' : '' }}
                                                >
                                                    {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Divider Icon -->
                                    <div class="sm:col-span-1 text-center py-1 sm:py-0">
                                        <div class="w-8 h-8 rounded-full bg-panel border border-line text-muted flex items-center justify-center mx-auto text-xs shadow-xs">
                                            &rarr;
                                        </div>
                                    </div>

                                    <!-- Rekening Tujuan -->
                                    <div class="sm:col-span-5">
                                        <label for="destination_account_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                            Ke Rekening (Tujuan)
                                        </label>
                                        <select 
                                            name="destination_account_id" 
                                            id="destination_account_id" 
                                            class="field-select w-full"
                                            disabled
                                            onchange="onTransferAccountChange()"
                                        >
                                            <option value="">Pilih Rekening Tujuan...</option>
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
                                                    {{ (string)old('destination_account_id') === (string)$acc->id ? 'selected' : '' }}
                                                >
                                                    {{ $icon }} {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div id="transferSameAccountAlert" class="hidden p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs font-semibold flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <span>Rekening tujuan transfer tidak boleh sama dengan rekening asal.</span>
                                </div>
                                <p class="text-[11px] text-muted text-center pt-1 border-t border-line/40">
                                    Nominal transfer akan menambah saldo rekening tujuan dan mengurangi saldo rekening asal.
                                </p>
                            </div>

                            <div class="p-4 rounded-xl bg-sidebar/50 border border-line space-y-3">
                                <div class="flex items-center justify-between">
                                    <label for="fee_amount_display" class="block text-xs font-bold text-charcoal">
                                        Biaya Admin Transfer <span class="text-[11px] font-normal text-muted">(Opsional)</span>
                                    </label>
                                    <span class="text-[11px] text-muted">Dipotong dari rekening asal</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">
                                    <div>
                                        <div class="relative field-prefix-wrapper">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted font-bold text-xs select-none z-10">
                                                Rp
                                            </span>
                                            <input 
                                                type="hidden" 
                                                id="fee_amount" 
                                                name="fee_amount" 
                                                value="{{ old('fee_amount', '0') }}"
                                                disabled
                                            >
                                            <input 
                                                type="text" 
                                                inputmode="numeric" 
                                                id="fee_amount_display" 
                                                placeholder="0" 
                                                value="{{ old('fee_amount') && old('fee_amount') > 0 ? number_format((float)old('fee_amount'), 0, ',', '.') : '0' }}" 
                                                class="field-input w-full has-prefix font-mono text-sm"
                                                style="padding-left: 44px !important;"
                                                oninput="onFeeDisplayInput(this.value)"
                                                disabled
                                            >
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                            <button type="button" onclick="setFeePreset(0)" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-line bg-panel hover:bg-forest/10 hover:border-forest/30 text-charcoal transition cursor-pointer">
                                                Gratis (Rp 0)
                                            </button>
                                            <button type="button" onclick="setFeePreset(2500)" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-line bg-panel hover:bg-forest/10 hover:border-forest/30 text-charcoal transition cursor-pointer">
                                                BI-FAST (Rp 2.500)
                                            </button>
                                            <button type="button" onclick="setFeePreset(6500)" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-line bg-panel hover:bg-forest/10 hover:border-forest/30 text-charcoal transition cursor-pointer">
                                                Online (Rp 6.500)
                                            </button>
                                            <button type="button" onclick="setFeePreset(1000)" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-line bg-panel hover:bg-forest/10 hover:border-forest/30 text-charcoal transition cursor-pointer">
                                                Top-Up (Rp 1.000)
                                            </button>
                                        </div>
                                        @error('fee_amount')
                                            <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="fee_category_id" class="block text-xs font-bold text-charcoal mb-1.5">
                                            Kategori Biaya Admin
                                        </label>
                                        <select 
                                            name="fee_category_id" 
                                            id="fee_category_id" 
                                            class="field-select w-full text-xs"
                                            disabled
                                            onchange="updateReceipt()"
                                        >
                                            @foreach ($expenseCategories as $cat)
                                                <option 
                                                    value="{{ $cat->id }}" 
                                                    data-name="{{ $cat->name }}"
                                                    {{ (string)old('fee_category_id') === (string)$cat->id || (empty(old('fee_category_id')) && (str_contains(strtolower($cat->name), 'admin') || str_contains(strtolower($cat->name), 'layanan'))) ? 'selected' : '' }}
                                                >
                                                    {{ $cat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-muted mt-1.5">
                                            Biaya admin dicatat sebagai beban pengeluaran pada kategori ini.
                                        </p>
                                        @error('fee_category_id')
                                            <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            @error('source_account_id')
                                <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                            @error('destination_account_id')
                                <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                            @enderror
                        </div>
                    </div>

                    <!-- KARTU 3: WAKTU & KETERANGAN TRANSAKSI -->
                    <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4">
                        <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                            4. Waktu & Catatan Transaksi
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tanggal & Jam Transaksi -->
                            <div>
                                <div class="grid grid-cols-5 gap-2">
                                    <div class="col-span-3">
                                        <label for="transaction_date" class="block text-xs font-bold text-charcoal mb-1.5">
                                            Tanggal Transaksi
                                        </label>
                                        <input 
                                            type="date" 
                                            name="transaction_date" 
                                            id="transaction_date" 
                                            value="{{ old('transaction_date', request('date', (app('currentSpace') ? app('currentSpace')->now() : now())->format('Y-m-d'))) }}" 
                                            class="field-input w-full"
                                            required
                                            onchange="updateReceipt()"
                                        >
                                    </div>
                                    <div class="col-span-2">
                                        <label for="transaction_time" class="block text-xs font-bold text-charcoal mb-1.5 flex items-center justify-between">
                                            <span>Jam</span>
                                            <span class="text-[10px] text-muted font-normal">Opsional</span>
                                        </label>
                                        <input 
                                            type="time" 
                                            name="transaction_time" 
                                            id="transaction_time" 
                                            value="{{ old('transaction_time', request('time', (app('currentSpace') ? app('currentSpace')->now() : now())->format('H:i'))) }}" 
                                            class="field-input w-full"
                                            onchange="updateReceipt()"
                                        >
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-1.5">
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="setDatePreset(0)" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
                                            Hari ini
                                        </button>
                                        <span class="text-line">&bull;</span>
                                        <button type="button" onclick="setDatePreset(-1)" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
                                            Kemarin
                                        </button>
                                    </div>
                                    <button type="button" onclick="setCurrentHour()" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
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
                                    Keterangan / Catatan
                                </label>
                                <input 
                                    type="text" 
                                    name="description" 
                                    id="descriptionInput" 
                                    value="{{ old('description', '') }}" 
                                    placeholder="Contoh: Makan siang bersama tim" 
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
                                        <span class="text-[11px] text-muted font-normal">Opsional (Contoh: Me, Parent, Family)</span>
                                    </label>
                                    <select name="member_id" id="transactionMember" class="field-select w-full">
                                        <option value="">Tanpa Member (Umum)</option>
                                        @foreach ($members as $m)
                                            <option value="{{ $m->id }}" {{ (string)old('member_id') === (string)$m->id ? 'selected' : '' }}>
                                                {{ $m->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <!-- Saran Cepat Keterangan -->
                        <div class="pt-2 border-t border-line/40">
                            <span class="text-[10px] font-bold text-muted uppercase block mb-1.5 select-none">
                                Saran Cepat:
                            </span>
                            <div class="flex items-center gap-1.5 flex-wrap" id="suggestionTags">
                                <!-- Suggestions injected via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI -->
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <input type="hidden" name="action" id="transactionFormAction" value="save_and_close">
                        <button 
                            type="submit" 
                            id="btnSaveAndClose" 
                            onclick="setTransactionAction('save_and_close')" 
                            class="btn-arus-primary min-h-[46px] px-6 text-sm font-bold flex-1 sm:flex-initial cursor-pointer inline-flex items-center gap-2"
                        >
                            <svg id="saveCloseIcon" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <svg id="saveCloseSpinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="saveCloseLabel">Simpan Transaksi</span>
                        </button>
                        <button 
                            type="submit" 
                            id="btnSaveAndAgain" 
                            onclick="setTransactionAction('save_and_again')" 
                            class="btn-arus-secondary min-h-[46px] px-5 text-sm font-bold text-forest border-forest/30 hover:bg-forest-light inline-flex items-center gap-1.5 cursor-pointer" 
                            title="Simpan transaksi ini dan lanjut input transaksi berikutnya langsung"
                        >
                            <svg id="saveAgainIcon" class="w-4 h-4 text-forest shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            <svg id="saveAgainSpinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-forest shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="saveAgainLabel">Simpan & Input Lagi</span>
                        </button>
                        <a href="{{ route('transactions.index') }}" id="btnCancelTransaction" class="btn-arus-secondary min-h-[46px] px-5 text-sm font-bold">
                            Batal
                        </a>
                    </div>
                </div>

                <!-- RIGHT COLUMN: LIVE BUKTI TRANSAKSI (RECEIPT PREVIEW) -->
                <div class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-[88px] space-y-4">
                    <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-line pb-3">
                            <div class="flex items-center gap-2">
                                <span class="brandmark text-xs w-6 h-6 rounded-md">a.</span>
                                <div>
                                    <div class="text-[10px] font-bold text-muted uppercase tracking-wider">STRUK PEMBUKUAN</div>
                                    <div class="text-xs font-bold text-charcoal">Pratinjau Entri</div>
                                </div>
                            </div>
                            <span id="receiptBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                PENGELUARAN
                            </span>
                        </div>

                        <!-- Nominal Slip Header -->
                        <div class="text-center py-2 bg-sidebar/50 rounded-xl border border-line/60">
                            <div class="text-[10px] font-bold text-muted uppercase tracking-wider mb-1">
                                Total Tercatat
                            </div>
                            <div id="receiptNominal" class="font-mono text-2xl sm:text-3xl font-bold text-rose-500 tracking-tight">
                                - Rp 0
                            </div>
                            <div id="receiptDateLabel" class="text-[11px] text-muted mt-1">
                                {{ date('d F Y') }}
                            </div>
                        </div>

                        <!-- Slip Rincian Data -->
                        <div class="divide-y divide-line/60 text-xs">
                            <div class="py-2.5 flex justify-between items-center">
                                <span class="text-muted">Keterangan:</span>
                                <span id="receiptDesc" class="font-bold text-charcoal text-right max-w-[180px] truncate">
                                    (Belum diisi)
                                </span>
                            </div>

                            <div class="py-2.5 flex justify-between items-center" id="receiptCategoryRow">
                                <span class="text-muted">Kategori:</span>
                                <span id="receiptCat" class="font-bold text-charcoal text-right">
                                    (Belum dipilih)
                                </span>
                            </div>

                            <div class="py-2.5 space-y-1.5" id="receiptAccountRow">
                                <div class="flex justify-between items-center">
                                    <span class="text-muted" id="receiptAccountLabel">Rekening:</span>
                                    <span id="receiptAccName" class="font-bold text-charcoal text-right">
                                        (Belum dipilih)
                                    </span>
                                </div>
                                <div id="receiptBalanceSimulation" class="text-[11px] text-muted bg-cream/40 p-2 rounded-lg border border-line/40 hidden">
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
                                Dobel-entri atomik
                            </span>
                            <span class="text-muted">Versi 1</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
            let currentType = '{{ old('type', $defaultType) }}';

            const suggestionMap = {
                expense: [
                    'Makan Siang', 
                    'Belanja Harian', 
                    'Bensin & Parkir', 
                    'Kopi & Cemilan', 
                    'Tagihan Listrik/Air', 
                    'Paket Data & Pulsa',
                    'Kebutuhan Rumah'
                ],
                income: [
                    'Gaji Bulanan', 
                    'Bonus Proyek', 
                    'Hasil Usaha', 
                    'Dividen Investasi', 
                    'Pengembalian Dana'
                ],
                transfer: [
                    'Top Up E-Wallet', 
                    'Top Up Investasi / RDN',
                    'Tarik Dana Investasi',
                    'Tarik Tunai ATM', 
                    'Pindah ke Rekening Tabungan', 
                    'Alokasi Dana Darurat'
                ]
            };

            function onTypeChanged(type) {
                currentType = type;

                const singleBlock = document.getElementById('singleAccountBlock');
                const transferBlock = document.getElementById('transferBlock');
                const accountLabel = document.getElementById('accountLabel');
                const accountSelect = document.getElementById('account_id');
                const expenseCatContainer = document.getElementById('expenseCategoryContainer');
                const incomeCatContainer = document.getElementById('incomeCategoryContainer');
                const expenseCatSelect = document.getElementById('expense_category_id');
                const incomeCatSelect = document.getElementById('income_category_id');
                const sourceAccSelect = document.getElementById('source_account_id');
                const destAccSelect = document.getElementById('destination_account_id');
                const feeInput = document.getElementById('fee_amount');
                const feeDisplay = document.getElementById('fee_amount_display');
                const feeCatSelect = document.getElementById('fee_category_id');

                if (type === 'transfer') {
                    singleBlock.classList.add('hidden');
                    transferBlock.classList.remove('hidden');

                    accountSelect.disabled = true;
                    expenseCatSelect.disabled = true;
                    incomeCatSelect.disabled = true;
                    sourceAccSelect.disabled = false;
                    destAccSelect.disabled = false;
                    if (feeInput) feeInput.disabled = false;
                    if (feeDisplay) feeDisplay.disabled = false;
                    if (feeCatSelect) feeCatSelect.disabled = false;
                    syncTransferAccounts();
                } else {
                    singleBlock.classList.remove('hidden');
                    transferBlock.classList.add('hidden');

                    accountSelect.disabled = false;
                    sourceAccSelect.disabled = true;
                    destAccSelect.disabled = true;
                    if (feeInput) feeInput.disabled = true;
                    if (feeDisplay) feeDisplay.disabled = true;
                    if (feeCatSelect) feeCatSelect.disabled = true;

                    if (type === 'income') {
                        accountLabel.textContent = 'Rekening Tujuan (Kas Masuk)';
                        expenseCatContainer.classList.add('hidden');
                        incomeCatContainer.classList.remove('hidden');
                        expenseCatSelect.disabled = true;
                        incomeCatSelect.disabled = false;
                    } else {
                        accountLabel.textContent = 'Rekening Sumber (Kas Keluar)';
                        expenseCatContainer.classList.remove('hidden');
                        incomeCatContainer.classList.add('hidden');
                        expenseCatSelect.disabled = false;
                        incomeCatSelect.disabled = true;
                    }
                }

                updateSuggestions();
                updateReceipt();
            }

            function updateSuggestions() {
                const container = document.getElementById('suggestionTags');
                container.innerHTML = '';
                const tags = suggestionMap[currentType] || [];

                tags.forEach(text => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'px-2 py-0.5 rounded text-[11px] bg-sidebar border border-line text-muted hover:text-charcoal hover:border-forest/40 transition cursor-pointer';
                    btn.textContent = text;
                    btn.onclick = () => {
                        document.getElementById('descriptionInput').value = text;
                        updateReceipt();
                    };
                    container.appendChild(btn);
                });
            }

            function applyPagePreset(desc, amount, type, categoryKeyword) {
                const radio = document.querySelector(`input[name="type"][value="${type}"]`);
                if (radio) {
                    radio.checked = true;
                    onTypeChanged(type);
                }

                const amountInput = document.getElementById('amountInput');
                const amountDisplay = document.getElementById('amountDisplay');
                amountInput.value = amount;
                amountDisplay.value = amount ? Number(amount).toLocaleString('id-ID') : '';

                document.getElementById('descriptionInput').value = desc;

                const accSelect = document.getElementById('account_id');
                if (accSelect && accSelect.options.length > 1 && !accSelect.value) {
                    accSelect.selectedIndex = 1;
                }

                if (type === 'expense' && categoryKeyword) {
                    const expSelect = document.getElementById('expense_category_id');
                    for (let i = 0; i < expSelect.options.length; i++) {
                        const optText = (expSelect.options[i].getAttribute('data-name') || '').toLowerCase();
                        if (optText.includes(categoryKeyword)) {
                            expSelect.selectedIndex = i;
                            break;
                        }
                    }
                } else if (type === 'income' && categoryKeyword) {
                    const incSelect = document.getElementById('income_category_id');
                    for (let i = 0; i < incSelect.options.length; i++) {
                        const optText = (incSelect.options[i].getAttribute('data-name') || '').toLowerCase();
                        if (optText.includes(categoryKeyword)) {
                            incSelect.selectedIndex = i;
                            break;
                        }
                    }
                }

                updateReceipt();
                amountInput.focus();
            }

            function applyPageSmartText() {
                const input = document.getElementById('pageSmartInput');
                const text = input.value.trim();
                if (!text) return;

                let str = text;
                let amount = 0;

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

                applyPagePreset(desc || text, amount, type, catKeyword);
                input.value = '';
            }

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

            function setDatePreset(offsetDays) {
                const d = new Date();
                d.setDate(d.getDate() + offsetDays);
                const iso = d.toISOString().split('T')[0];
                document.getElementById('transaction_date').value = iso;
                updateReceipt();
            }

            function setCurrentHour() {
                const now = new Date();
                const hh = String(now.getHours()).padStart(2, '0');
                const mm = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('transaction_time').value = `${hh}:${mm}`;
                updateReceipt();
            }

            function formatRupiah(num) {
                const isInt = num % 1 === 0;
                return 'Rp ' + Number(num).toLocaleString('id-ID', {
                    minimumFractionDigits: isInt ? 0 : 2,
                    maximumFractionDigits: 2
                });
            }

            function onFeeDisplayInput(val) {
                const cleanDigits = val.replace(/\D/g, '');
                const num = parseInt(cleanDigits, 10) || 0;
                const feeInput = document.getElementById('fee_amount');
                const feeDisplay = document.getElementById('fee_amount_display');
                if (feeInput) feeInput.value = num;
                if (feeDisplay) feeDisplay.value = num > 0 ? num.toLocaleString('id-ID') : (cleanDigits === '' ? '' : '0');
                updateReceipt();
            }

            function setFeePreset(val) {
                const feeInput = document.getElementById('fee_amount');
                const feeDisplay = document.getElementById('fee_amount_display');
                if (feeInput) feeInput.value = val;
                if (feeDisplay) feeDisplay.value = val > 0 ? val.toLocaleString('id-ID') : '0';
                updateReceipt();
            }

            function updateReceipt() {
                const amountVal = parseFloat(document.getElementById('amountInput').value) || 0;
                const dateVal = document.getElementById('transaction_date').value;
                const timeVal = document.getElementById('transaction_time')?.value;
                const descVal = document.getElementById('descriptionInput').value.trim();

                const receiptBadge = document.getElementById('receiptBadge');
                const receiptNominal = document.getElementById('receiptNominal');
                const amountSignPreview = document.getElementById('amountSignPreview');
                const receiptDateLabel = document.getElementById('receiptDateLabel');
                const receiptDesc = document.getElementById('receiptDesc');
                const receiptCat = document.getElementById('receiptCat');
                const receiptCategoryRow = document.getElementById('receiptCategoryRow');
                const receiptAccountLabel = document.getElementById('receiptAccountLabel');
                const receiptAccName = document.getElementById('receiptAccName');
                const balanceSimulation = document.getElementById('receiptBalanceSimulation');

                // 1. Tanggal & Jam
                if (dateVal) {
                    const parsed = new Date(dateVal + 'T00:00:00');
                    const formattedDate = parsed.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    receiptDateLabel.textContent = timeVal ? `${formattedDate}, ${timeVal}` : formattedDate;
                }

                // 2. Keterangan
                receiptDesc.textContent = descVal || '(Belum diisi)';

                // 3. Spelled preview text
                const spelled = document.getElementById('amountSpelledPreview');
                if (amountVal > 0) {
                    spelled.textContent = formatRupiah(amountVal);
                } else {
                    spelled.textContent = 'Nol rupiah';
                }

                // 4. Badge & Nominal styling by Type
                if (currentType === 'expense') {
                    receiptBadge.textContent = 'PENGELUARAN';
                    receiptBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20';
                    receiptNominal.textContent = '- ' + formatRupiah(amountVal);
                    receiptNominal.className = 'font-mono text-2xl sm:text-3xl font-bold text-rose-500 tracking-tight';
                    amountSignPreview.textContent = 'Kas Keluar (-)';
                    amountSignPreview.className = 'text-[11px] font-bold text-rose-500';

                    receiptCategoryRow.classList.remove('hidden');
                    const catSelect = document.getElementById('expense_category_id');
                    const selectedCat = catSelect.options[catSelect.selectedIndex];
                    receiptCat.textContent = selectedCat && selectedCat.value ? selectedCat.getAttribute('data-name') : '(Belum dipilih)';

                    receiptAccountLabel.textContent = 'Rekening Sumber:';
                    const accSelect = document.getElementById('account_id');
                    const selectedAcc = accSelect.options[accSelect.selectedIndex];
                    if (selectedAcc && selectedAcc.value) {
                        const accName = selectedAcc.getAttribute('data-name');
                        const balance = parseInt(selectedAcc.getAttribute('data-balance'), 10) || 0;
                        const projected = balance - amountVal;
                        receiptAccName.textContent = accName;
                        balanceSimulation.classList.remove('hidden');
                        balanceSimulation.innerHTML = `
                            <div class="flex justify-between">
                                <span>Saldo saat ini:</span>
                                <span class="font-semibold">${formatRupiah(balance)}</span>
                            </div>
                            <div class="flex justify-between font-bold text-rose-500 pt-0.5">
                                <span>Saldo setelah transaksi:</span>
                                <span>${formatRupiah(projected)}</span>
                            </div>
                        `;
                    } else {
                        receiptAccName.textContent = '(Belum dipilih)';
                        balanceSimulation.classList.add('hidden');
                    }
                } else if (currentType === 'income') {
                    receiptBadge.textContent = 'PEMASUKAN';
                    receiptBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
                    receiptNominal.textContent = '+ ' + formatRupiah(amountVal);
                    receiptNominal.className = 'font-mono text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight';
                    amountSignPreview.textContent = 'Kas Masuk (+)';
                    amountSignPreview.className = 'text-[11px] font-bold text-emerald-600 dark:text-emerald-400';

                    receiptCategoryRow.classList.remove('hidden');
                    const catSelect = document.getElementById('income_category_id');
                    const selectedCat = catSelect.options[catSelect.selectedIndex];
                    receiptCat.textContent = selectedCat && selectedCat.value ? selectedCat.getAttribute('data-name') : '(Belum dipilih)';

                    receiptAccountLabel.textContent = 'Rekening Tujuan:';
                    const accSelect = document.getElementById('account_id');
                    const selectedAcc = accSelect.options[accSelect.selectedIndex];
                    if (selectedAcc && selectedAcc.value) {
                        const accName = selectedAcc.getAttribute('data-name');
                        const balance = parseInt(selectedAcc.getAttribute('data-balance'), 10) || 0;
                        const projected = balance + amountVal;
                        receiptAccName.textContent = accName;
                        balanceSimulation.classList.remove('hidden');
                        balanceSimulation.innerHTML = `
                            <div class="flex justify-between">
                                <span>Saldo saat ini:</span>
                                <span class="font-semibold">${formatRupiah(balance)}</span>
                            </div>
                            <div class="flex justify-between font-bold text-emerald-600 dark:text-emerald-400 pt-0.5">
                                <span>Saldo setelah transaksi:</span>
                                <span>${formatRupiah(projected)}</span>
                            </div>
                        `;
                    } else {
                        receiptAccName.textContent = '(Belum dipilih)';
                        balanceSimulation.classList.add('hidden');
                    }
                } else if (currentType === 'transfer') {
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

                    const feeCatSelect = document.getElementById('fee_category_id');
                    const selectedFeeCat = feeCatSelect ? feeCatSelect.options[feeCatSelect.selectedIndex] : null;
                    if (feeVal > 0 && selectedFeeCat) {
                        receiptCategoryRow.classList.remove('hidden');
                        receiptCat.textContent = `Admin: ${selectedFeeCat.getAttribute('data-name') || selectedFeeCat.text}`;
                    } else {
                        receiptCategoryRow.classList.add('hidden');
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
                                    <span class="font-semibold">${formatRupiah(srcBal)} → ${formatRupiah(srcBal - totalSourceDeduction)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>${dstName}:</span>
                                    <span class="font-semibold">${formatRupiah(dstBal)} → ${formatRupiah(dstBal + amountVal)}</span>
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
                    } else {
                        balanceSimulation.classList.add('hidden');
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
                    if (srcSelect.value && dstSelect.value && srcSelect.value === dstSelect.value) {
                        alertEl.classList.remove('hidden');
                    } else {
                        alertEl.classList.add('hidden');
                    }
                }
            }

            let selectedTransactionAction = 'save_and_close';

            function setTransactionAction(action) {
                selectedTransactionAction = action;
                const actionInput = document.getElementById('transactionFormAction');
                if (actionInput) actionInput.value = action;
            }

            function onTransferAccountChange() {
                syncTransferAccounts();
                updateReceipt();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const checked = document.querySelector('input[name="type"]:checked');
                if (checked) {
                    onTypeChanged(checked.value);
                } else {
                    onTypeChanged('expense');
                }
                updateReceipt();

                const txForm = document.getElementById('transactionForm');
                if (txForm) {
                    txForm.addEventListener('submit', function(e) {
                        if (currentType === 'transfer') {
                            const src = document.getElementById('source_account_id').value;
                            const dst = document.getElementById('destination_account_id').value;
                            if (!src || !dst || src === dst) {
                                e.preventDefault();
                                const alertEl = document.getElementById('transferSameAccountAlert');
                                if (alertEl) alertEl.classList.remove('hidden');
                                if (src === dst || !dst) {
                                    document.getElementById('destination_account_id').focus();
                                } else {
                                    document.getElementById('source_account_id').focus();
                                }
                                return false;
                            }
                        }

                        if (e.defaultPrevented) return;

                        const btnClose = document.getElementById('btnSaveAndClose');
                        const btnAgain = document.getElementById('btnSaveAndAgain');
                        const cancelLink = document.getElementById('btnCancelTransaction');

                        if (selectedTransactionAction === 'save_and_again') {
                            const icon = document.getElementById('saveAgainIcon');
                            const spinner = document.getElementById('saveAgainSpinner');
                            const label = document.getElementById('saveAgainLabel');
                            if (icon) icon.classList.add('hidden');
                            if (spinner) spinner.classList.remove('hidden');
                            if (label) label.textContent = 'Menyimpan...';
                            if (btnAgain) btnAgain.classList.add('opacity-75', 'pointer-events-none');
                            if (btnClose) btnClose.classList.add('opacity-50', 'pointer-events-none');
                        } else {
                            const icon = document.getElementById('saveCloseIcon');
                            const spinner = document.getElementById('saveCloseSpinner');
                            const label = document.getElementById('saveCloseLabel');
                            if (icon) icon.classList.add('hidden');
                            if (spinner) spinner.classList.remove('hidden');
                            if (label) label.textContent = 'Menyimpan...';
                            if (btnClose) btnClose.classList.add('opacity-75', 'pointer-events-none');
                            if (btnAgain) btnAgain.classList.add('opacity-50', 'pointer-events-none');
                        }
                        if (cancelLink) cancelLink.classList.add('opacity-50', 'pointer-events-none');
                    });
                }
            });
        </script>
    @endif
</x-layouts.app>
