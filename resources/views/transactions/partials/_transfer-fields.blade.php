@php
    $isCreate = ($mode ?? 'create') === 'create';
    $selectedSourceId = $isCreate
        ? (string) old('source_account_id', request('source_account_id', $defaultAccount?->id))
        : (string) old('source_account_id', $transaction?->sourceAccount()?->id);
    $selectedDestinationId = $isCreate
        ? (string) old('destination_account_id')
        : (string) old('destination_account_id', $transaction?->destinationAccount()?->id);
    $initialFeeAmount = $isCreate
        ? (float) old('fee_amount', 0)
        : (float) old('fee_amount', (float) ($transaction?->fee_amount ?? 0));
    $feeDisplayValue = $initialFeeAmount > 0 ? number_format($initialFeeAmount, 0, ',', '.') : '0';
    $selectedFeeCategoryId = $isCreate
        ? (string) old('fee_category_id')
        : (string) old('fee_category_id', $transaction?->fee_category_id);

    $feePresets = [
        ['label' => 'Gratis', 'amount' => 0, 'display' => 'Rp 0'],
        ['label' => 'Top-Up', 'amount' => 1000, 'display' => 'Rp 1.000'],
        ['label' => 'BI-FAST', 'amount' => 2500, 'display' => 'Rp 2.500'],
        ['label' => 'Online', 'amount' => 6500, 'display' => 'Rp 6.500'],
    ];

    $accountIcon = static fn (string $type): string => match ($type) {
        'bank' => '🏦',
        'e-wallet' => '📱',
        'cash' => '💵',
        'investment' => '📈',
        default => '💳',
    };
@endphp

<div id="transferBlock" class="{{ $isCreate ? 'hidden ' : '' }}space-y-4">
    <!-- Jalur Rekening Transfer -->
    <div class="p-4 sm:p-5 rounded-xl bg-sidebar/50 border border-line space-y-3.5">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pb-2.5 border-b border-line/50">
            <span class="text-xs font-bold text-charcoal">
                Jalur Transfer Rekening
            </span>
            <span class="text-[11px] text-[#526070] dark:text-muted">
                Saldo asal berkurang, saldo tujuan bertambah
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_28px_minmax(0,1fr)] gap-2.5 items-start">
            <!-- Rekening Asal -->
            <div class="min-w-0">
                <label for="source_account_id" class="block text-xs leading-4 font-bold text-charcoal mb-1.5">
                    Dari Rekening (Asal)
                </label>
                <select
                    name="source_account_id"
                    id="source_account_id"
                    class="field-select w-full {{ $errors->has('source_account_id') ? 'has-error' : '' }}"
                    data-searchable-picker="true"
                    data-picker-kind="account"
                    data-recent-ids='@json($recentAccountIds ?? [])'
                    data-recent-scope="account"
                    data-show-recent-chips="true"
                    data-search-placeholder="Cari rekening asal..."
                    {{ $isCreate ? 'disabled' : 'required' }}
                    onchange="onTransferAccountChange()"
                >
                    @if ($isCreate)
                        <option value="">Pilih Rekening Asal...</option>
                    @endif
                    @foreach ($accounts as $acc)
                        @php $icon = $accountIcon($acc->type); @endphp
                        <option
                            value="{{ $acc->id }}"
                            data-balance="{{ $acc->currentBalance() }}"
                            data-name="{{ $acc->name }}"
                            data-icon="{{ $icon }}"
                            data-chip-label="{{ $icon }} {{ $acc->name }}"
                            data-search-text="{{ $acc->name }} {{ $acc->type }}{{ $acc->is_default ? ' default' : '' }}"
                            {{ $selectedSourceId === (string) $acc->id ? 'selected' : '' }}
                        >
                            {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Konektor Arah Transfer -->
            <div class="flex items-center justify-center sm:mt-[22px] sm:h-[38px]" aria-hidden="true">
                <div class="w-7 h-7 rounded-full bg-panel border border-line text-[#526070] dark:text-muted flex items-center justify-center shadow-2xs">
                    <svg class="w-3.5 h-3.5 rotate-90 sm:rotate-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </div>
            </div>

            <!-- Rekening Tujuan -->
            <div class="min-w-0">
                <label for="destination_account_id" class="block text-xs leading-4 font-bold text-charcoal mb-1.5">
                    Ke Rekening (Tujuan)
                </label>
                <select
                    name="destination_account_id"
                    id="destination_account_id"
                    class="field-select w-full {{ $errors->has('destination_account_id') ? 'has-error' : '' }}"
                    data-searchable-picker="true"
                    data-picker-kind="account"
                    data-recent-ids='@json($recentAccountIds ?? [])'
                    data-recent-scope="account"
                    data-show-recent-chips="true"
                    data-search-placeholder="Cari rekening tujuan..."
                    {{ $isCreate ? 'disabled' : 'required' }}
                    onchange="onTransferAccountChange()"
                >
                    @if ($isCreate)
                        <option value="">Pilih Rekening Tujuan...</option>
                    @endif
                    @foreach ($accounts as $acc)
                        @php $icon = $accountIcon($acc->type); @endphp
                        <option
                            value="{{ $acc->id }}"
                            data-balance="{{ $acc->currentBalance() }}"
                            data-name="{{ $acc->name }}"
                            data-icon="{{ $icon }}"
                            data-chip-label="{{ $icon }} {{ $acc->name }}"
                            data-search-text="{{ $acc->name }} {{ $acc->type }}{{ $acc->is_default ? ' default' : '' }}"
                            {{ $selectedDestinationId === (string) $acc->id ? 'selected' : '' }}
                        >
                            {{ $icon }} {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="transferSameAccountAlert" class="hidden p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span id="transferSameAccountAlertText">Rekening tujuan transfer tidak boleh sama dengan rekening asal.</span>
        </div>

        @if ($errors->has('source_account_id') || $errors->has('destination_account_id'))
            <div class="space-y-1">
                @error('source_account_id')
                    <div class="field-error text-xs text-red"><span>{{ $message }}</span></div>
                @enderror
                @error('destination_account_id')
                    <div class="field-error text-xs text-red"><span>{{ $message }}</span></div>
                @enderror
            </div>
        @endif
    </div>

    <!-- Biaya Admin Transfer -->
    <div class="p-4 sm:p-5 rounded-xl bg-sidebar/50 border border-line space-y-3.5">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pb-2.5 border-b border-line/50">
            <span class="text-xs font-bold text-charcoal">
                Biaya Admin Transfer <span class="text-[11px] font-normal text-[#526070] dark:text-muted">(Opsional)</span>
            </span>
            <span class="text-[11px] text-[#526070] dark:text-muted">
                Dipotong dari rekening asal
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-x-12 items-start">
            <div class="min-w-0">
                <label for="fee_amount_display" class="block text-xs leading-4 font-bold text-charcoal mb-1.5">
                    Nominal Biaya Admin
                </label>
                <div class="relative field-prefix-wrapper">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#526070] dark:text-muted font-bold text-xs select-none z-10">
                        Rp
                    </span>
                    <input
                        type="hidden"
                        id="fee_amount"
                        name="fee_amount"
                        value="{{ $initialFeeAmount }}"
                        {{ $isCreate ? 'disabled' : '' }}
                    >
                    <input
                        type="text"
                        inputmode="numeric"
                        id="fee_amount_display"
                        placeholder="0"
                        value="{{ $feeDisplayValue }}"
                        class="field-input w-full has-prefix font-mono text-sm {{ $errors->has('fee_amount') ? 'has-error' : '' }}"
                        style="padding-left: 44px !important;"
                        oninput="onFeeDisplayInput(this.value)"
                        {{ $isCreate ? 'disabled' : '' }}
                    >
                </div>
                @error('fee_amount')
                    <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                @enderror
            </div>

            <div class="min-w-0">
                <label for="fee_category_id" class="block text-xs leading-4 font-bold text-charcoal mb-1.5">
                    Kategori Biaya Admin
                </label>
                <select
                    name="fee_category_id"
                    id="fee_category_id"
                    class="field-select w-full text-xs {{ $errors->has('fee_category_id') ? 'has-error' : '' }}"
                    data-category-picker="true"
                    data-recent-ids='@json($recentExpenseCategoryIds ?? [])'
                    data-recent-scope="expense"
                    data-search-placeholder="Cari kategori biaya admin..."
                    {{ $isCreate ? 'disabled' : '' }}
                    onchange="updateReceipt()"
                >
                    @foreach ($expenseCategories as $cat)
                        @php
                            $isFeeCategorySelected = $isCreate
                                ? ($selectedFeeCategoryId === (string) $cat->id || ($selectedFeeCategoryId === '' && (str_contains(strtolower($cat->name), 'admin') || str_contains(strtolower($cat->name), 'layanan'))))
                                : ($selectedFeeCategoryId === (string) $cat->id);
                        @endphp
                        <option
                            value="{{ $cat->id }}"
                            data-name="{{ $cat->name }}"
                            {{ $isFeeCategorySelected ? 'selected' : '' }}
                        >
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('fee_category_id')
                    <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                @enderror
            </div>
        </div>

        <div class="pt-3 border-t border-line/40 space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <span class="text-[10.5px] font-bold uppercase tracking-wider text-[#526070] dark:text-muted">
                    Pilih Cepat Biaya Admin
                </span>
                <span class="text-[11px] text-[#526070] dark:text-muted">
                    Dicatat sebagai beban pengeluaran pada kategori dipilih
                </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 gap-2">
                @foreach ($feePresets as $preset)
                    @php $isActivePreset = (int) round($initialFeeAmount) === $preset['amount']; @endphp
                    <button
                        type="button"
                        data-fee-preset="{{ $preset['amount'] }}"
                        onclick="setFeePreset({{ $preset['amount'] }})"
                        aria-pressed="{{ $isActivePreset ? 'true' : 'false' }}"
                        class="fee-preset-btn min-h-[44px] px-3 py-2 rounded-lg border text-left transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest dark:focus-visible:outline-blue-400 {{ $isActivePreset ? 'bg-forest-light border-forest text-forest dark:text-blue-300' : 'bg-panel border-line text-charcoal hover:bg-cream hover:border-sage' }}"
                    >
                        <span class="block text-xs font-bold leading-tight">{{ $preset['label'] }}</span>
                        <span class="fee-preset-amount block text-[11px] font-mono leading-tight mt-0.5 {{ $isActivePreset ? 'text-forest dark:text-blue-300' : 'text-[#526070] dark:text-muted' }}">{{ $preset['display'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
