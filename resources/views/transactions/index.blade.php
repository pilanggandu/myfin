<x-layouts.app title="Daftar Transaksi">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / KEUANGAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Catatan Transaksi
            </h1>
            <p class="text-xs text-muted">Seluruh pencatatan arus kas masuk, pengeluaran, dan mutasi transfer antar-rekening.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'transactions-table\']')" 
                class="privacy-card-toggle inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer flex-1 sm:flex-initial"
                title="Sembunyikan / Tampilkan Nominal Transaksi"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Transaksi</span>
            </button>
            <a href="{{ route('transactions.create') }}" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px]">
                + Catat Transaksi Baru
            </a>
        </div>
    </div>

    <!-- Filter & Search Panel -->
    <div class="panel-card p-4 sm:p-5 mb-6">
        @php
            $advancedKeys = ['type', 'account_id', 'category_id', 'member_id', 'start_date', 'end_date', 'include_future', 'min_amount', 'max_amount'];
            $activeCount = 0;
            foreach ($advancedKeys as $k) {
                if (request()->filled($k)) {
                    $activeCount++;
                }
            }
            if (request()->has('status') && request('status') !== 'posted') {
                $activeCount++;
            }
            if (request()->filled('sort') && request('sort') !== 'date_desc') {
                $activeCount++;
            }
            $isAdvancedOpen = $activeCount > 0;
            $hasAnyFilter = request()->hasAny(['search', 'type', 'account_id', 'category_id', 'member_id', 'start_date', 'end_date', 'include_future', 'min_amount', 'max_amount'])
                || (request()->has('status') && request('status') !== 'posted')
                || (request()->has('sort') && request('sort') !== 'date_desc');
        @endphp

        <form method="GET" action="{{ route('transactions.index') }}" class="space-y-3" id="transactionFilterForm">
            @if ($showUpcoming)
                <input type="hidden" name="include_future" value="1" />
            @endif
            <!-- Baris Utama: Pencarian Cepat + Tombol Dropdown Filter Lanjutan + Aksi -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        name="search" 
                        id="transactionSearchInput"
                        value="{{ request('search') }}" 
                        placeholder="Cari keterangan, kategori, rekening, member, atau nominal..." 
                        aria-label="Cari transaksi"
                        autocomplete="off"
                        class="field-input pl-9 pr-9 w-full" 
                        style="padding-left: 36px !important; padding-right: 36px !important;"
                    />
                    <button
                        type="button"
                        id="clearTransactionSearchBtn"
                        onclick="clearTransactionSearch()"
                        class="{{ request()->filled('search') ? 'inline-flex' : 'hidden' }} absolute inset-y-0 right-1 my-auto w-9 h-9 sm:w-7 sm:h-7 rounded-md items-center justify-center text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer text-sm leading-none"
                        title="Hapus kata kunci pencarian"
                        aria-label="Hapus kata kunci pencarian"
                    >
                        &times;
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <button 
                        type="button" 
                        id="toggleAdvancedBtn"
                        onclick="toggleAdvancedFilters()"
                        class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] px-3.5 text-xs font-bold inline-flex items-center gap-2 cursor-pointer transition select-none flex-1 sm:flex-initial justify-center {{ $isAdvancedOpen ? 'border-forest text-forest dark:text-blue-300 bg-forest-light/40' : '' }}"
                        aria-expanded="{{ $isAdvancedOpen ? 'true' : 'false' }}"
                    >
                        <svg class="w-4 h-4 text-forest dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        <span>Filter Lanjutan</span>
                        @if ($activeCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-forest text-white font-bold font-mono">
                                {{ $activeCount }}
                            </span>
                        @endif
                        <svg id="advancedChevron" class="w-3.5 h-3.5 text-muted transition-transform duration-200 {{ $isAdvancedOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <button type="submit" class="btn-arus-primary min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold cursor-pointer">
                        Terapkan
                    </button>

                    <a
                        href="{{ route('transactions.index') }}"
                        id="transactionResetLink"
                        class="{{ $hasAnyFilter ? 'inline-flex' : 'hidden' }} btn-arus-secondary min-h-[44px] sm:min-h-[38px] px-3 text-xs font-bold items-center justify-center text-muted hover:text-red transition cursor-pointer"
                        title="Reset Semua Filter"
                    >
                        Reset
                    </a>
                </div>
            </div>

            <!-- Status Pencarian Langsung (Loading / Error) -->
            <div id="transactionSearchLoading" class="hidden items-center gap-2 text-xs text-muted pt-1" role="status" aria-live="polite">
                <svg class="animate-spin w-3.5 h-3.5 text-forest dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Mencari transaksi...</span>
            </div>

            <div id="transactionSearchError" class="hidden p-2.5 rounded-lg border border-red/30 bg-red/10 text-xs text-red flex items-center justify-between gap-2" role="alert">
                <span>Gagal memuat hasil pencarian otomatis. Klik tombol Terapkan untuk memuat ulang.</span>
                <button type="button" onclick="this.parentElement.classList.add('hidden')" class="font-bold px-1.5 cursor-pointer" aria-label="Tutup pesan kesalahan">&times;</button>
            </div>

            @if ($recentFilterCategories->isNotEmpty())
                <div class="flex items-center gap-2 sm:gap-1.5 flex-wrap pt-3 border-t border-line/60" id="recentCategoryFilterBar">
                    <span class="w-full sm:w-auto text-[11px] font-semibold text-muted sm:mr-1">Kategori terakhir:</span>
                    <button
                        type="button"
                        data-quick-filter-category=""
                        onclick="applyQuickCategoryFilter('')"
                        aria-pressed="{{ !request()->filled('category_id') ? 'true' : 'false' }}"
                        class="min-h-[44px] sm:min-h-[28px] px-3 sm:px-2.5 py-2 sm:py-1 rounded-lg text-xs border transition cursor-pointer {{ !request()->filled('category_id') ? 'bg-forest-light border-forest text-forest dark:text-blue-300 font-bold' : 'bg-panel border-line text-charcoal font-medium hover:bg-cream hover:border-sage' }}"
                    >
                        Semua
                    </button>
                    @foreach ($recentFilterCategories as $recentCat)
                        @php
                            $isCatActive = (string) request('category_id') === (string) $recentCat->id;
                        @endphp
                        <button
                            type="button"
                            data-quick-filter-category="{{ $recentCat->id }}"
                            onclick="applyQuickCategoryFilter('{{ $recentCat->id }}')"
                            aria-pressed="{{ $isCatActive ? 'true' : 'false' }}"
                            class="min-h-[44px] sm:min-h-[28px] px-3 sm:px-2.5 py-2 sm:py-1 rounded-lg text-xs border transition cursor-pointer {{ $isCatActive ? 'bg-forest-light border-forest text-forest dark:text-blue-300 font-bold' : 'bg-panel border-line text-charcoal font-medium hover:bg-cream hover:border-sage' }}"
                        >
                            {{ $recentCat->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            <!-- Panel Dropdown / Accordion: Filter Lanjutan -->
            <div id="advancedFilterPanel" class="{{ $isAdvancedOpen ? '' : 'hidden' }} pt-4 mt-3.5 border-t border-line transition-all duration-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <div>
                        <label for="filterTypeSelect" class="block text-[11px] font-semibold text-charcoal mb-1.5">Jenis Transaksi</label>
                        <select name="type" id="filterTypeSelect" class="field-select w-full">
                            <option value="">Semua Jenis</option>
                            <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Pemasukan (Income)</option>
                            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran (Expense)</option>
                            <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer Antar-Rekening</option>
                            <option value="credit_payment" {{ request('type') === 'credit_payment' ? 'selected' : '' }}>Pembayaran Kredit</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterAccountSelect" class="block text-[11px] font-semibold text-charcoal mb-1.5">Rekening</label>
                        <select
                            name="account_id"
                            id="filterAccountSelect"
                            class="field-select w-full"
                            data-searchable-picker="true"
                            data-picker-kind="account"
                            data-allow-empty="true"
                            data-empty-as-value="true"
                            data-recent-ids='@json($recentAccountIds ?? [])'
                            data-recent-scope="account"
                            data-search-placeholder="Cari rekening..."
                        >
                            <option value="">Semua Rekening</option>
                            @foreach ($accounts as $acc)
                                <option
                                    value="{{ $acc->id }}"
                                    data-name="{{ $acc->name }}"
                                    data-search-text="{{ $acc->name }} {{ $acc->type }}"
                                    {{ (string)request('account_id') === (string)$acc->id ? 'selected' : '' }}
                                >
                                    {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filterCategorySelect" class="block text-[11px] font-semibold text-charcoal mb-1.5">Kategori</label>
                        <select
                            name="category_id"
                            id="filterCategorySelect"
                            class="field-select w-full"
                            data-category-picker="true"
                            data-allow-empty="true"
                            data-empty-as-value="true"
                            data-recent-ids='@json($recentCategoryIds ?? [])'
                            data-search-placeholder="Cari kategori..."
                        >
                            <option value="">Semua Kategori</option>
                            <optgroup label="Pengeluaran">
                                @foreach ($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)request('category_id') === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Pemasukan">
                                @foreach ($incomeCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)request('category_id') === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label for="filterMemberSelect" class="block text-[11px] font-semibold text-charcoal mb-1.5">Member Terkait</label>
                        <select name="member_id" id="filterMemberSelect" class="field-select w-full">
                            <option value="">Semua Member</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}" {{ (string)request('member_id') === (string)$m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filterStatusSelect" class="block text-[11px] font-semibold text-charcoal mb-1.5">Status Transaksi</label>
                        <select name="status" id="filterStatusSelect" class="field-select w-full">
                            <option value="posted" {{ request('status', 'posted') === 'posted' ? 'selected' : '' }}>Posted (Aktif - Default)</option>
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status (Termasuk Void)</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void (Dibatalkan)</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterSortSelect" class="block text-[11px] font-semibold text-charcoal mb-1.5">Urutan</label>
                        <select name="sort" id="filterSortSelect" class="field-select w-full">
                            <option value="date_desc" {{ request('sort', 'date_desc') === 'date_desc' ? 'selected' : '' }}>Terbaru (Tanggal ↓)</option>
                            <option value="date_asc" {{ request('sort') === 'date_asc' ? 'selected' : '' }}>Terlama (Tanggal ↑)</option>
                            <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>Nominal Terbesar</option>
                            <option value="amount_asc" {{ request('sort') === 'amount_asc' ? 'selected' : '' }}>Nominal Terkecil</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterStartDate" class="block text-[11px] font-semibold text-charcoal mb-1.5">Mulai Tanggal</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            id="filterStartDate"
                            value="{{ request('start_date') }}" 
                            class="field-input w-full" 
                        />
                    </div>

                    <div>
                        <label for="filterEndDate" class="block text-[11px] font-semibold text-charcoal mb-1.5">Sampai Tanggal</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="filterEndDate"
                            value="{{ request('end_date') }}" 
                            class="field-input w-full" 
                        />
                    </div>

                    <div>
                        <label for="filterMinAmount" class="block text-[11px] font-semibold text-charcoal mb-1.5">Nominal Minimum (Rp)</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="min_amount"
                            id="filterMinAmount"
                            value="{{ request('min_amount') }}"
                            placeholder="Contoh: 50.000"
                            class="field-input w-full font-mono"
                        />
                    </div>

                    <div>
                        <label for="filterMaxAmount" class="block text-[11px] font-semibold text-charcoal mb-1.5">Nominal Maksimum (Rp)</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="max_amount"
                            id="filterMaxAmount"
                            value="{{ request('max_amount') }}"
                            placeholder="Contoh: 500.000"
                            class="field-input w-full font-mono"
                        />
                    </div>
                </div>

                <!-- Pilihan Tanggal Cepat & Tombol Aksi -->
                <div class="mt-4 pt-3.5 border-t border-line/60 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-1.5 flex-wrap" id="quickDatePresetBar">
                        <span class="w-full sm:w-auto text-[11px] font-semibold text-muted sm:mr-1">Pilihan Tanggal Cepat:</span>
                        <button type="button" data-date-preset="today" onclick="setDateFilterPreset('today')" aria-pressed="false" class="min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border border-line bg-panel text-charcoal font-medium hover:bg-cream hover:border-sage transition cursor-pointer">
                            Hari Ini
                        </button>
                        <button type="button" data-date-preset="this_month" onclick="setDateFilterPreset('this_month')" aria-pressed="false" class="min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border border-line bg-panel text-charcoal font-medium hover:bg-cream hover:border-sage transition cursor-pointer">
                            Bulan Ini
                        </button>
                        <button type="button" data-date-preset="last_30_days" onclick="setDateFilterPreset('last_30_days')" aria-pressed="false" class="min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border border-line bg-panel text-charcoal font-medium hover:bg-cream hover:border-sage transition cursor-pointer">
                            30 Hari Terakhir
                        </button>
                        <button type="button" data-date-preset="upcoming" onclick="setDateFilterPreset('upcoming')" aria-pressed="false" class="min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border border-line bg-panel text-charcoal font-medium hover:bg-cream hover:border-sage transition cursor-pointer">
                            Mendatang
                        </button>
                        <button type="button" data-date-preset="clear" onclick="setDateFilterPreset('clear')" class="min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border border-line bg-panel text-muted font-medium hover:text-red hover:border-red/40 hover:bg-red/5 transition cursor-pointer">
                            Hapus Tanggal
                        </button>
                    </div>

                    <div class="flex items-center gap-2 sm:justify-end shrink-0">
                        <button type="button" onclick="clearAdvancedFilters()" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] px-3.5 text-xs font-semibold text-muted hover:text-charcoal cursor-pointer flex-1 sm:flex-initial">
                            Bersihkan
                        </button>
                        <button type="submit" class="btn-arus-primary min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer flex-1 sm:flex-initial">
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Action Feedback Banner (AJAX) -->
    <div id="bulkActionFeedback" class="hidden mb-4 p-4 rounded-xl border border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-200 flex items-center justify-between gap-3 shadow-xs" role="status" aria-live="polite">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0" aria-hidden="true">✓</span>
            <span id="bulkActionFeedbackText" class="text-xs font-semibold"></span>
        </div>
        <button type="button" onclick="hideBulkActionFeedback()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-emerald-500/30 text-emerald-800 dark:text-emerald-200 hover:bg-emerald-500/15 transition inline-flex items-center justify-center cursor-pointer shrink-0" aria-label="Tutup pesan status">
            ✕
        </button>
    </div>

    <!-- Bulk Action Toolbar -->
    <div id="bulkActionBar" class="hidden sticky top-3 z-30 mb-4 panel-card bg-panel/95 backdrop-blur-xs border border-forest/40 p-3.5 sm:px-5 sm:py-3 shadow-md" role="region" aria-label="Bilah aksi koreksi massal">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 min-w-0">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-forest dark:bg-blue-600 text-white text-xs font-bold shrink-0">
                    <span id="bulkSelectedCount">0</span>
                    <span>dipilih</span>
                </span>
                <span id="bulkSelectionBreakdown" class="text-xs font-medium text-charcoal"></span>
                <span id="bulkInstallmentBadge" class="hidden text-[11px] font-semibold px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-700/60 text-amber-800 dark:text-amber-200">
                    Termasuk transaksi cicilan (nominal &amp; rekening terkunci)
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <button type="button" onclick="openBulkCorrectModal('update')" class="btn-arus-primary min-h-[44px] sm:min-h-[36px] px-4 text-xs font-bold justify-center flex-1 sm:flex-initial cursor-pointer">
                    Koreksi Massal
                </button>
                <button type="button" onclick="openBulkCorrectModal('void')" class="min-h-[44px] sm:min-h-[36px] px-3.5 py-2 rounded-lg border border-red-300 dark:border-red-700/60 bg-red-50 dark:bg-red-950/50 text-red-700 dark:text-red-300 hover:bg-red-700 hover:text-white dark:hover:bg-red-700 dark:hover:text-white text-xs font-bold transition cursor-pointer inline-flex items-center justify-center flex-1 sm:flex-initial">
                    Void Massal
                </button>
                <button type="button" onclick="clearBulkSelection()" class="btn-arus-secondary min-h-[44px] sm:min-h-[36px] px-3.5 text-xs font-semibold justify-center cursor-pointer">
                    Batal Pilih
                </button>
            </div>
        </div>
    </div>

    <div id="transactionResultsContainer" class="space-y-4">
        @if ($hasAnyFilter)
            @php
                $selectedCatName = $expenseCategories->concat($incomeCategories)->firstWhere('id', (int) request('category_id'))?->name;
                $selectedAccName = $accounts->firstWhere('id', (int) request('account_id'))?->name;
                $selectedMemName = $members->firstWhere('id', (int) request('member_id'))?->name;
            @endphp
            <div class="flex items-center justify-between gap-2 flex-wrap px-1 text-xs" id="activeFilterSummary">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-muted font-semibold">Filter aktif:</span>
                    @if (request()->filled('search'))
                        <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-forest-light border border-forest/30 text-forest dark:text-blue-300 font-semibold hover:opacity-85 transition">
                            <span>Cari: "{{ request('search') }}"</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('type'))
                        <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Jenis: {{ ucfirst(request('type')) }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($selectedCatName)
                        <a href="{{ request()->fullUrlWithQuery(['category_id' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Kategori: {{ $selectedCatName }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($selectedAccName)
                        <a href="{{ request()->fullUrlWithQuery(['account_id' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Rekening: {{ $selectedAccName }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($selectedMemName)
                        <a href="{{ request()->fullUrlWithQuery(['member_id' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Member: {{ $selectedMemName }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('start_date') || request()->filled('end_date'))
                        <a href="{{ request()->fullUrlWithQuery(['start_date' => null, 'end_date' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Tanggal: {{ request('start_date', '...') }} s/d {{ request('end_date', '...') }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if (request()->has('status') && request('status') !== 'posted')
                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Status: {{ request('status') === 'all' ? 'Semua' : strtoupper((string) request('status')) }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if (request('sort') === 'date_asc')
                        <a href="{{ request()->fullUrlWithQuery(['sort' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Urutan: Terlama</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($showUpcoming)
                        <a href="{{ request()->fullUrlWithQuery(['include_future' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-panel border border-line text-charcoal font-medium hover:bg-cream transition">
                            <span>Termasuk Mendatang</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                </div>
                <span class="text-muted font-mono text-[11px]">{{ $transactions->total() }} hasil ditemukan</span>
            </div>
        @endif

        {{-- Banner Notifikasi Transaksi Mendatang --}}
        @if (! $showUpcoming && $upcomingCount > 0)
            <div class="p-4 rounded-xl border border-sky-500/25 bg-sky-500/10 text-sky-950 dark:text-sky-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-700 dark:text-sky-300 flex items-center justify-center text-sm flex-shrink-0">
                        📅
                    </span>
                    <div>
                        <div class="text-xs font-bold">
                            Terdapat {{ $upcomingCount }} transaksi mendatang disembunyikan
                        </div>
                        <div class="text-[11px] text-muted">
                            Secara default, transaksi bertanggal di masa depan (seperti tagihan kredit berikutnya) disembunyikan dari riwayat utama.
                        </div>
                    </div>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['include_future' => 1]) }}" class="btn-arus-secondary text-xs py-1.5 px-3 font-bold border-sky-500/30 text-sky-700 dark:text-sky-300 hover:bg-sky-500/15 transition flex-shrink-0 inline-flex items-center gap-1.5">
                    <span>Tampilkan Transaksi Mendatang</span>
                    <span>&rarr;</span>
                </a>
            </div>
        @elseif ($showUpcoming)
            <div class="p-4 rounded-xl border border-amber-500/25 bg-amber-500/10 text-amber-950 dark:text-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-700 dark:text-amber-300 flex items-center justify-center text-sm flex-shrink-0">
                        ⏳
                    </span>
                    <div>
                        <div class="text-xs font-bold">
                            Menampilkan transaksi termasuk transaksi mendatang
                        </div>
                        <div class="text-[11px] text-muted">
                            Mode transaksi mendatang aktif. Transaksi bertanggal masa depan ikut ditampilkan di tabel.
                        </div>
                    </div>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['include_future' => null]) }}" class="btn-arus-secondary text-xs py-1.5 px-3 font-bold border-amber-500/30 text-amber-700 dark:text-amber-300 hover:bg-amber-500/15 transition flex-shrink-0 inline-flex items-center gap-1.5">
                    <span>Sembunyikan Transaksi Mendatang</span>
                </a>
            </div>
        @endif

        <!-- Table & List Panel -->
        <div class="panel-card p-0 overflow-hidden" data-privacy-scope="transactions-table">
            <div class="px-4 sm:px-6 py-4 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-sm font-bold text-forest dark:text-blue-300 block">Daftar Transaksi ({{ $transactions->total() }})</span>
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-7 sm:h-7 -my-2 sm:my-0 inline-flex items-center justify-center text-muted hover:text-charcoal transition-colors rounded-lg cursor-pointer shrink-0" 
                        title="Sembunyikan / Tampilkan Nominal Transaksi"
                        aria-label="Sembunyikan / Tampilkan Nominal Transaksi"
                    >
                        <span class="privacy-toggle-icon text-muted"></span>
                    </button>
                </div>
                <span class="text-xs text-muted">Halaman {{ $transactions->currentPage() }} dari {{ $transactions->lastPage() }}</span>
            </div>

            @if ($transactions->isEmpty())
                <div class="p-10 text-center space-y-3">
                    <div class="w-11 h-11 rounded-xl bg-cream border border-line text-forest dark:text-blue-300 flex items-center justify-center mx-auto">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <div class="font-bold text-base text-charcoal">
                        {{ $hasAnyFilter ? 'Tidak Ada Transaksi yang Cocok' : 'Belum Ada Transaksi Tercatat' }}
                    </div>
                    <p class="text-xs text-muted max-w-md mx-auto">
                        {{ $hasAnyFilter
                            ? 'Tidak ada transaksi yang sesuai dengan kata kunci pencarian atau filter yang sedang aktif.'
                            : 'Anda belum mencatat mutasi keuangan di ruang ini. Catat pemasukan, pengeluaran, atau transfer pertama Anda.' }}
                    </p>
                    <div class="pt-2 flex items-center justify-center gap-2 flex-wrap">
                        @if ($hasAnyFilter)
                            <a href="{{ route('transactions.index') }}" class="btn-arus-secondary">
                                Reset Pencarian &amp; Filter
                            </a>
                        @endif
                        <a href="{{ route('transactions.create') }}" class="btn-arus-primary">
                            + Catat Transaksi Baru
                        </a>
                    </div>
                </div>
            @else
            <!-- Tampilan Ponsel (Mobile Cards) -->
            <div class="md:hidden divide-y divide-line">
                <div class="px-4 py-2 border-b border-line bg-cream/35 flex items-center justify-between gap-2">
                    <label class="inline-flex items-center gap-2.5 min-h-[44px] text-xs font-semibold text-charcoal cursor-pointer select-none">
                        <input
                            type="checkbox"
                            data-bulk-select-all
                            aria-label="Pilih semua transaksi yang memenuhi syarat di halaman ini"
                            class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                        >
                        <span>Pilih semua di halaman ini</span>
                    </label>
                    <span class="text-[11px] text-muted">Centang untuk koreksi massal</span>
                </div>
                @foreach ($transactions as $trx)
                    @php
                        $isVoid = $trx->isVoid();
                        $isBulkEligible = ! $isVoid && in_array($trx->type, ['income', 'expense', 'transfer'], true);
                        $hasInstallmentPlan = $trx->installmentPlan !== null;
                        $amount = $trx->totalAmount();
                        $typeBadge = match($trx->type) {
                            'income' => 'Pemasukan',
                            'expense' => 'Pengeluaran',
                            'transfer' => 'Transfer',
                            default => $trx->type,
                        };
                        $amountColor = match($trx->type) {
                            'income' => 'text-forest',
                            'expense' => 'text-red',
                            'transfer' => 'text-charcoal',
                            default => 'text-charcoal',
                        };
                        $prefix = match($trx->type) {
                            'income' => '+',
                            'expense' => '-',
                            'transfer' => '⇄',
                            default => '',
                        };
                    @endphp
                    <div class="p-5 space-y-3 hover:bg-cream/20 transition-colors {{ $isVoid ? 'opacity-55' : '' }}" data-trx-card="{{ $trx->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <label
                                class="w-11 h-11 -ml-2.5 -mt-2 inline-flex items-center justify-center rounded-lg hover:bg-cream/60 transition shrink-0 {{ $isBulkEligible ? 'cursor-pointer' : 'cursor-not-allowed opacity-40' }}"
                                title="{{ $isBulkEligible ? 'Pilih transaksi untuk koreksi massal' : 'Transaksi void atau sistem kredit tidak dapat dikoreksi massal' }}"
                            >
                                <input
                                    type="checkbox"
                                    data-bulk-select-row
                                    data-trx-id="{{ $trx->id }}"
                                    data-trx-version="{{ $trx->version }}"
                                    data-trx-type="{{ $trx->type }}"
                                    data-trx-installment="{{ $hasInstallmentPlan ? '1' : '0' }}"
                                    data-trx-desc="{{ $trx->description }}"
                                    aria-label="Pilih transaksi {{ $trx->description }}"
                                    @disabled(! $isBulkEligible)
                                    class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest disabled:cursor-not-allowed cursor-pointer"
                                >
                            </label>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-base text-charcoal {{ $isVoid ? 'line-through' : '' }}">
                                        {{ $trx->description }}
                                    </span>
                                    @if ($trx->member)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background-color: {{ $trx->member->color ?: '#0f4c3a' }};">
                                            {{ $trx->member->name }}
                                        </span>
                                    @endif
                                    <x-badge variant="default">{{ $typeBadge }}</x-badge>
                                </div>
                                <div class="text-xs text-muted mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>{{ $trx->transaction_date->format('d M Y') }}</span>
                                    @if ($trx->formattedTime())
                                        <span class="text-muted/75 font-mono text-[11px]">({{ $trx->formattedTime() }})</span>
                                    @endif
                                    @if ($trx->category)
                                        <span class="text-line">&bull;</span>
                                        <span>{{ $trx->category->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-bold text-base {{ $amountColor }} {{ $isVoid ? 'line-through' : '' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $prefix }} Rp {{ number_format($amount, 0, ',', '.') }}">
                                        {{ $prefix }} Rp {{ number_format($amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                @if ($trx->isTransfer() && (float)($trx->fee_amount ?? 0) > 0)
                                    <div class="text-[10px] text-muted font-normal mt-0.5">
                                        + fee <span class="privacy-balance" data-raw-value="Rp {{ number_format($trx->fee_amount, 0, ',', '.') }}">Rp {{ number_format($trx->fee_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div>
                                    @if ($isVoid)
                                        <x-badge variant="inactive">VOID</x-badge>
                                    @else
                                        <span class="text-forest font-bold text-[11px]">POSTED</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Rekening Info -->
                        <div class="p-2.5 rounded-lg bg-sidebar/50 border border-line text-xs flex flex-wrap items-center justify-between gap-1.5">
                            <span class="text-muted">Rekening:</span>
                            @if ($trx->isTransfer())
                                <span class="font-bold text-charcoal text-right">
                                    {{ $trx->sourceAccount()?->name }} &rarr; {{ $trx->destinationAccount()?->name }}
                                </span>
                            @elseif ($trx->isExpense())
                                <span class="font-bold text-charcoal text-right">{{ $trx->sourceAccount()?->name }}</span>
                            @else
                                <span class="font-bold text-charcoal text-right">{{ $trx->destinationAccount()?->name }}</span>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-line/60">
                            <a href="{{ route('transactions.show', $trx) }}" class="flex-1 min-w-[96px] min-h-[44px] px-3 py-2 rounded-lg text-xs font-semibold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center gap-1.5" title="Lihat Detail Transaksi">
                                <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <span>Detail</span>
                            </a>
                            @if (! $isVoid)
                                <a href="{{ route('transactions.edit', $trx) }}" class="min-w-[44px] min-h-[44px] px-3.5 py-2 rounded-lg text-xs font-semibold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center gap-1.5" title="Koreksi Transaksi">
                                    <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    <span>Koreksi</span>
                                </a>
                                <form method="POST" action="{{ route('transactions.void', $trx) }}" onsubmit="return confirm('Batalkan transaksi ini (void)? Catatan entri akan dinonaktifkan dari saldo kas.')">
                                    @csrf
                                    <button type="submit" class="min-w-[44px] min-h-[44px] px-3.5 py-2 rounded-lg text-xs font-semibold text-red hover:bg-red/10 border border-line hover:border-red/40 bg-panel transition cursor-pointer inline-flex items-center justify-center gap-1.5" title="Batalkan Transaksi (Void)">
                                        <svg class="w-4 h-4 text-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span>Void</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Tampilan Desktop (Table View) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                            <th class="py-3 pl-5 pr-2 w-10">
                                <label class="inline-flex items-center justify-center w-8 h-8 cursor-pointer rounded hover:bg-cream/60" title="Pilih semua transaksi yang memenuhi syarat di halaman ini">
                                    <input
                                        type="checkbox"
                                        data-bulk-select-all
                                        aria-label="Pilih semua transaksi yang memenuhi syarat di halaman ini"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                </label>
                            </th>
                            <th class="py-3 pl-2 pr-6">
                                @php
                                    $currentSort = request('sort', 'date_desc');
                                    $nextSort = $currentSort === 'date_desc' ? 'date_asc' : 'date_desc';
                                    $sortUrl = request()->fullUrlWithQuery(['sort' => $nextSort]);
                                @endphp
                                <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1.5 hover:text-forest transition cursor-pointer select-none group" title="Klik untuk membalik urutan tanggal">
                                    <span>Tanggal</span>
                                    <span class="text-xs font-bold {{ $currentSort === 'date_asc' ? 'text-forest' : 'text-muted group-hover:text-forest' }}">
                                        {{ $currentSort === 'date_asc' ? '↑' : '↓' }}
                                    </span>
                                </a>
                            </th>
                            <th class="py-3 px-4">Keterangan</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Rekening</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($transactions as $trx)
                            @php
                                $isVoid = $trx->isVoid();
                                $isBulkEligible = ! $isVoid && in_array($trx->type, ['income', 'expense', 'transfer'], true);
                                $hasInstallmentPlan = $trx->installmentPlan !== null;
                                $amount = $trx->totalAmount();
                                $amountColor = match($trx->type) {
                                    'income' => 'text-forest',
                                    'expense' => 'text-red',
                                    'transfer' => 'text-charcoal',
                                    default => 'text-charcoal',
                                };
                                $prefix = match($trx->type) {
                                    'income' => '+',
                                    'expense' => '-',
                                    'transfer' => '⇄',
                                    default => '',
                                };
                            @endphp
                            <tr class="hover:bg-cream/30 transition-colors {{ $isVoid ? 'opacity-55' : '' }}" data-trx-row="{{ $trx->id }}">
                                <td class="py-4 pl-5 pr-2 w-10 whitespace-nowrap">
                                    <label
                                        class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-cream/60 {{ $isBulkEligible ? 'cursor-pointer' : 'cursor-not-allowed opacity-40' }}"
                                        title="{{ $isBulkEligible ? 'Pilih transaksi untuk koreksi massal' : 'Transaksi void atau sistem kredit tidak dapat dikoreksi massal' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            data-bulk-select-row
                                            data-trx-id="{{ $trx->id }}"
                                            data-trx-version="{{ $trx->version }}"
                                            data-trx-type="{{ $trx->type }}"
                                            data-trx-installment="{{ $hasInstallmentPlan ? '1' : '0' }}"
                                            data-trx-desc="{{ $trx->description }}"
                                            aria-label="Pilih transaksi {{ $trx->description }}"
                                            @disabled(! $isBulkEligible)
                                            class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest disabled:cursor-not-allowed cursor-pointer"
                                        >
                                    </label>
                                </td>
                                <td class="py-4 pl-2 pr-6 whitespace-nowrap text-muted">
                                    <div class="font-medium text-charcoal">{{ $trx->transaction_date->format('d M Y') }}</div>
                                    @if ($trx->formattedTime())
                                        <div class="text-[11px] text-muted/75 font-mono">{{ $trx->formattedTime() }} WIB</div>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-sm text-charcoal {{ $isVoid ? 'line-through' : '' }} flex items-center gap-1.5">
                                        <span>{{ $trx->description }}</span>
                                        @if ($trx->member)
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-bold text-white shadow-2xs" style="background-color: {{ $trx->member->color ?: '#0f4c3a' }};">
                                                {{ $trx->member->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted uppercase tracking-wider text-[10px]">
                                        {{ strtoupper($trx->type) }}
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($trx->category)
                                        <x-badge variant="default">{{ $trx->category->name }}</x-badge>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-charcoal">
                                    @if ($trx->isTransfer())
                                        <span>{{ $trx->sourceAccount()?->name }} &rarr; {{ $trx->destinationAccount()?->name }}</span>
                                    @elseif ($trx->isExpense())
                                        <span>{{ $trx->sourceAccount()?->name }}</span>
                                    @else
                                        <span>{{ $trx->destinationAccount()?->name }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($isVoid)
                                        <x-badge variant="inactive">VOID</x-badge>
                                    @else
                                        <span class="text-forest font-bold text-[11px]">POSTED</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-right font-bold text-sm {{ $amountColor }} {{ $isVoid ? 'line-through' : '' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $prefix }} Rp {{ number_format($amount, 0, ',', '.') }}">
                                        {{ $prefix }} Rp {{ number_format($amount, 0, ',', '.') }}
                                    </span>
                                    @if ($trx->isTransfer() && (float)($trx->fee_amount ?? 0) > 0)
                                        <div class="text-[10px] text-muted font-normal mt-0.5">
                                            + fee <span class="privacy-balance" data-raw-value="Rp {{ number_format($trx->fee_amount, 0, ',', '.') }}">Rp {{ number_format($trx->fee_amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a 
                                            href="{{ route('transactions.show', $trx) }}" 
                                            class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
                                            title="Lihat Detail Transaksi"
                                            aria-label="Lihat Detail Transaksi"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>
                                        @if (! $isVoid)
                                            <a 
                                                href="{{ route('transactions.edit', $trx) }}" 
                                                class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
                                                title="Koreksi Transaksi"
                                                aria-label="Koreksi Transaksi"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('transactions.void', $trx) }}" class="inline" onsubmit="return confirm('Batalkan transaksi ini (void)? Catatan entri akan dinonaktifkan dari saldo kas.')">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Batalkan Transaksi (Void)"
                                                    aria-label="Batalkan Transaksi (Void)"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="p-4 border-t border-line">
                    {{ $transactions->links() }}
                </div>
            @endif
        @endif
        </div>
    </div>

    <script>
        let transactionSearchTimer = null;
        let transactionSearchAbort = null;

        function resolveFormActionUrl(form, fallbackUrl) {
            // Read the HTML attribute directly so child inputs named "action" cannot shadow form.action.
            const rawAction = form?.getAttribute('action') || fallbackUrl || window.location.pathname;
            if (
                window.location.protocol === 'https:'
                && typeof rawAction === 'string'
                && rawAction.startsWith(`http://${window.location.host}/`)
            ) {
                return rawAction.replace(/^http:/, 'https:');
            }
            return rawAction;
        }

        function buildTransactionFilterUrl() {
            const form = document.getElementById('transactionFilterForm');
            if (!form) return window.location.href;

            const params = new URLSearchParams();
            const data = new FormData(form);

            for (const [key, value] of data.entries()) {
                const str = String(value).trim();
                if (str === '') continue;
                if (key === 'status' && str === 'posted') continue;
                if (key === 'sort' && str === 'date_desc') continue;
                params.append(key, str);
            }

            const qs = params.toString();
            const baseUrl = resolveFormActionUrl(form, @js(route('transactions.index', [], false)));
            return qs ? `${baseUrl}?${qs}` : baseUrl;
        }

        function syncQuickFilterChips(activeCategoryId) {
            document.querySelectorAll('[data-quick-filter-category]').forEach(btn => {
                const btnVal = btn.getAttribute('data-quick-filter-category') || '';
                const isActive = btnVal === String(activeCategoryId || '');
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                if (isActive) {
                    btn.className = 'min-h-[44px] sm:min-h-[28px] px-3 sm:px-2.5 py-2 sm:py-1 rounded-lg text-xs border transition cursor-pointer bg-forest-light border-forest text-forest dark:text-blue-300 font-bold';
                } else {
                    btn.className = 'min-h-[44px] sm:min-h-[28px] px-3 sm:px-2.5 py-2 sm:py-1 rounded-lg text-xs border transition cursor-pointer bg-panel border-line text-charcoal font-medium hover:bg-cream hover:border-sage';
                }
            });
        }

        function syncResetButtonVisibility() {
            const resetLink = document.getElementById('transactionResetLink');
            const form = document.getElementById('transactionFilterForm');
            if (!resetLink || !form) return;

            const data = new FormData(form);
            let hasFilter = false;
            for (const [key, value] of data.entries()) {
                const str = String(value).trim();
                if (str === '') continue;
                if (key === 'status' && str === 'posted') continue;
                if (key === 'sort' && str === 'date_desc') continue;
                hasFilter = true;
                break;
            }

            resetLink.classList.toggle('hidden', !hasFilter);
            resetLink.classList.toggle('inline-flex', hasFilter);
        }

        async function runLiveTransactionSearch() {
            const resultsContainer = document.getElementById('transactionResultsContainer');
            const loadingEl = document.getElementById('transactionSearchLoading');
            const errorEl = document.getElementById('transactionSearchError');
            if (!resultsContainer) return;

            if (transactionSearchAbort) {
                transactionSearchAbort.abort();
            }
            transactionSearchAbort = new AbortController();

            const url = buildTransactionFilterUrl();
            window.history.replaceState({}, '', url);
            syncResetButtonVisibility();

            if (errorEl) errorEl.classList.add('hidden');
            if (loadingEl) {
                loadingEl.classList.remove('hidden');
                loadingEl.classList.add('flex');
            }

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: transactionSearchAbort.signal,
                });

                if (!response.ok) {
                    throw new Error('Failed to load results');
                }

                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextResults = doc.getElementById('transactionResultsContainer');

                if (nextResults) {
                    resultsContainer.innerHTML = nextResults.innerHTML;
                    if (typeof window.initPrivacySystem === 'function') {
                        window.initPrivacySystem();
                    }
                    if (typeof window.syncBulkSelectionAfterResultsRender === 'function') {
                        window.syncBulkSelectionAfterResultsRender();
                    }
                }
            } catch (err) {
                if (err.name === 'AbortError') return;
                if (errorEl) errorEl.classList.remove('hidden');
            } finally {
                if (loadingEl) {
                    loadingEl.classList.add('hidden');
                    loadingEl.classList.remove('flex');
                }
            }
        }

        function clearTransactionSearch() {
            const input = document.getElementById('transactionSearchInput');
            const clearBtn = document.getElementById('clearTransactionSearchBtn');
            if (!input) return;

            input.value = '';
            if (clearBtn) {
                clearBtn.classList.add('hidden');
                clearBtn.classList.remove('inline-flex');
            }
            input.focus();
            runLiveTransactionSearch();
        }

        function applyQuickCategoryFilter(categoryId) {
            const catSelect = document.getElementById('filterCategorySelect');
            if (!catSelect) return;

            const current = String(catSelect.value || '');
            const nextVal = categoryId !== '' && current === String(categoryId) ? '' : String(categoryId);
            catSelect.value = nextVal;
            syncQuickFilterChips(nextVal);
            runLiveTransactionSearch();
        }

        function toLocalIsoDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function getDatePresetRange(preset) {
            const now = new Date();
            if (preset === 'today') {
                const todayStr = toLocalIsoDate(now);
                return { start: todayStr, end: todayStr };
            }
            if (preset === 'this_month') {
                return {
                    start: toLocalIsoDate(new Date(now.getFullYear(), now.getMonth(), 1)),
                    end: toLocalIsoDate(new Date(now.getFullYear(), now.getMonth() + 1, 0)),
                };
            }
            if (preset === 'last_30_days') {
                const past = new Date(now);
                past.setDate(past.getDate() - 30);
                return { start: toLocalIsoDate(past), end: toLocalIsoDate(now) };
            }
            if (preset === 'upcoming') {
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                return { start: toLocalIsoDate(tomorrow), end: '' };
            }
            return { start: '', end: '' };
        }

        function syncDatePresetChips() {
            const startInput = document.getElementById('filterStartDate');
            const endInput = document.getElementById('filterEndDate');
            if (!startInput || !endInput) return;

            const startVal = startInput.value || '';
            const endVal = endInput.value || '';

            document.querySelectorAll('#quickDatePresetBar [data-date-preset]').forEach(btn => {
                const preset = btn.getAttribute('data-date-preset');
                if (preset === 'clear') return;

                const range = getDatePresetRange(preset);
                const isActive = startVal !== '' && startVal === range.start && endVal === range.end;
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                btn.className = isActive
                    ? 'min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border transition cursor-pointer bg-forest-light border-forest text-forest dark:text-blue-300 font-bold'
                    : 'min-h-[44px] sm:min-h-[30px] px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-lg text-xs border border-line bg-panel text-charcoal font-medium hover:bg-cream hover:border-sage transition cursor-pointer';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('transactionSearchInput');
            const clearBtn = document.getElementById('clearTransactionSearchBtn');
            const catSelect = document.getElementById('filterCategorySelect');
            const startInput = document.getElementById('filterStartDate');
            const endInput = document.getElementById('filterEndDate');
            const form = document.getElementById('transactionFilterForm');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const hasValue = this.value.length > 0;
                    if (clearBtn) {
                        clearBtn.classList.toggle('hidden', !hasValue);
                        clearBtn.classList.toggle('inline-flex', hasValue);
                    }
                    clearTimeout(transactionSearchTimer);
                    transactionSearchTimer = setTimeout(() => {
                        runLiveTransactionSearch();
                    }, 350);
                });
            }

            if (catSelect) {
                catSelect.addEventListener('change', function() {
                    syncQuickFilterChips(this.value);
                });
            }

            [startInput, endInput].forEach(el => {
                if (el) {
                    el.addEventListener('change', syncDatePresetChips);
                }
            });
            syncDatePresetChips();

            if (form) {
                form.addEventListener('submit', function() {
                    clearTimeout(transactionSearchTimer);
                });
            }
        });

        function toggleAdvancedFilters() {
            const panel = document.getElementById('advancedFilterPanel');
            const chevron = document.getElementById('advancedChevron');
            const btn = document.getElementById('toggleAdvancedBtn');
            if (!panel) return;
            
            const isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                if (btn) {
                    btn.classList.add('border-forest', 'text-forest', 'dark:text-blue-300', 'bg-forest-light/40');
                    btn.setAttribute('aria-expanded', 'true');
                }
            } else {
                panel.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
                if (btn) {
                    btn.classList.remove('border-forest', 'text-forest', 'dark:text-blue-300', 'bg-forest-light/40');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        }

        function clearAdvancedFilters() {
            const form = document.getElementById('transactionFilterForm');
            if (!form) return;
            form.querySelectorAll('#advancedFilterPanel select').forEach(s => {
                if (s.name === 'status') {
                    s.value = 'posted';
                } else if (s.name === 'sort') {
                    s.value = 'date_desc';
                } else {
                    s.value = '';
                }
            });
            form.querySelectorAll('#advancedFilterPanel input[type="date"], #advancedFilterPanel input[name="min_amount"], #advancedFilterPanel input[name="max_amount"]').forEach(i => i.value = '');
            syncDatePresetChips();
            const futureInput = form.querySelector('input[name="include_future"]');
            if (futureInput) futureInput.remove();
            form.submit();
        }

        function setDateFilterPreset(preset) {
            const startInput = document.getElementById('filterStartDate');
            const endInput = document.getElementById('filterEndDate');
            if (!startInput || !endInput) return;

            const range = getDatePresetRange(preset);
            startInput.value = range.start;
            endInput.value = range.end;
            syncDatePresetChips();
        }
    </script>

    @php
        $bulkActiveIncomeCategories = ($activeIncomeCategories ?? $incomeCategories->filter(fn ($c) => ! $c->isArchived()))->values();
        $bulkActiveExpenseCategories = ($activeExpenseCategories ?? $expenseCategories->filter(fn ($c) => ! $c->isArchived()))->values();
        $bulkRecentIncomeCatIds = $recentIncomeCategoryIds ?? [];
        $bulkRecentExpenseCatIds = $recentExpenseCategoryIds ?? [];
    @endphp

    <!-- Modal Koreksi & Void Massal -->
    <div
        id="bulkCorrectModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-2.5 sm:p-4 overflow-y-auto hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="bulkModalTitle"
        onclick="closeBulkCorrectModal()"
    >
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-3xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-2 sm:my-6 max-h-[calc(100dvh-1rem)] sm:max-h-[90dvh] overflow-y-auto" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
                <div class="min-w-0">
                    <h2 id="bulkModalTitle" class="font-serif text-xl sm:text-2xl text-forest dark:text-blue-300 font-normal">
                        Koreksi Massal Transaksi
                    </h2>
                    <p id="bulkModalSelectionSummary" class="text-xs text-muted mt-0.5">
                        0 transaksi dipilih
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeBulkCorrectModal()"
                    class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition inline-flex items-center justify-center cursor-pointer text-sm shrink-0"
                    aria-label="Tutup modal koreksi massal"
                >
                    ✕
                </button>
            </div>

            <!-- Inline Error Alert -->
            <div id="bulkModalError" class="hidden p-3.5 rounded-xl border border-red-200 dark:border-red-800/70 bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 text-xs space-y-1" role="alert"></div>

            <!-- Mode Tabs -->
            <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-cream/70 border border-line" role="tablist" aria-label="Pilih mode aksi massal">
                <button
                    type="button"
                    id="bulkModeTabUpdate"
                    role="tab"
                    aria-selected="true"
                    onclick="switchBulkModalMode('update')"
                    class="min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg text-xs font-bold transition cursor-pointer bg-panel text-forest dark:text-blue-300 border border-line shadow-2xs"
                >
                    Ubah Kolom Seragam
                </button>
                <button
                    type="button"
                    id="bulkModeTabVoid"
                    role="tab"
                    aria-selected="false"
                    onclick="switchBulkModalMode('void')"
                    class="min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-muted hover:text-charcoal border border-transparent"
                >
                    Batalkan (Void) Massal
                </button>
            </div>

            <form id="bulkCorrectForm" method="POST" action="{{ route('transactions.bulk-correct') }}" class="space-y-4" onsubmit="return submitBulkCorrection(event)">
                @csrf
                <input type="hidden" id="bulkActionInput" name="action" value="update">

                <!-- Panel Mode Update -->
                <div id="bulkUpdateFieldsSection" class="space-y-3.5">
                    <p class="text-xs text-muted">
                        Centang hanya kolom yang ingin ditimpa ke seluruh transaksi terpilih. Kolom yang tidak dicentang tetap mempertahankan nilai asli masing-masing transaksi.
                    </p>

                    <div id="bulkCompatibilityNotice" class="hidden p-3 rounded-xl border border-amber-300 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 text-xs leading-relaxed"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- Field: Kategori -->
                        <div id="bulkFieldCardCategory" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                    <input
                                        type="checkbox"
                                        id="bulkApplyCategory"
                                        name="apply_category"
                                        value="1"
                                        data-bulk-field-toggle="bulkBodyCategory"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                    <span>Kategori</span>
                                </label>
                                <span id="bulkCategoryTypePill" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-cream text-muted"></span>
                            </div>
                            <div id="bulkBodyCategory" class="opacity-55 pointer-events-none transition-opacity" inert>
                                <div id="bulkExpenseCategoryWrap">
                                    <select
                                        id="bulkExpenseCategoryId"
                                        name="category_id"
                                        class="field-input text-xs w-full"
                                        data-searchable-picker="true"
                                        data-picker-kind="category"
                                        data-picker-noun="kategori"
                                        data-show-recent-chips="true"
                                        data-recent-ids='@json($bulkRecentExpenseCatIds)'
                                    >
                                        <option value="">-- Pilih Kategori Pengeluaran --</option>
                                        @foreach ($bulkActiveExpenseCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="bulkIncomeCategoryWrap" class="hidden">
                                    <select
                                        id="bulkIncomeCategoryId"
                                        name="category_id"
                                        class="field-input text-xs w-full"
                                        data-searchable-picker="true"
                                        data-picker-kind="category"
                                        data-picker-noun="kategori"
                                        data-show-recent-chips="true"
                                        data-recent-ids='@json($bulkRecentIncomeCatIds)'
                                        disabled
                                    >
                                        <option value="">-- Pilih Kategori Pemasukan --</option>
                                        @foreach ($bulkActiveIncomeCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <p id="bulkCategoryDisabledMsg" class="hidden text-[11px] text-muted leading-snug py-1.5">
                                    Hanya tersedia jika seluruh transaksi yang dipilih bertipe sama (semua Pengeluaran atau semua Pemasukan).
                                </p>
                            </div>
                        </div>

                        <!-- Field: Rekening (Single Account for Income/Expense) -->
                        <div id="bulkFieldCardAccount" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                    <input
                                        type="checkbox"
                                        id="bulkApplyAccount"
                                        name="apply_account"
                                        value="1"
                                        data-bulk-field-toggle="bulkBodyAccount"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                    <span>Rekening</span>
                                </label>
                                <span id="bulkAccountBadge" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-cream text-muted hidden"></span>
                            </div>
                            <div id="bulkBodyAccount" class="opacity-55 pointer-events-none transition-opacity" inert>
                                <div id="bulkSingleAccountSelectWrap">
                                    <select
                                        id="bulkAccountId"
                                        name="account_id"
                                        class="field-input text-xs w-full"
                                        data-searchable-picker="true"
                                        data-picker-kind="account"
                                        data-picker-noun="rekening"
                                        data-show-recent-chips="true"
                                        data-recent-ids='@json($recentAccountIds)'
                                    >
                                        <option value="">-- Pilih Rekening --</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <p id="bulkAccountDisabledMsg" class="hidden text-[11px] text-muted leading-snug py-1.5"></p>
                            </div>
                        </div>

                        <!-- Transfer-Only Fields (Shown when ALL selected transactions are Transfer) -->
                        <div id="bulkTransferSection" class="hidden md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 p-3.5 rounded-xl border border-line bg-cream/30">
                            <div class="md:col-span-2 text-[11px] font-bold uppercase tracking-wider text-forest dark:text-blue-300">
                                Penyesuaian Khusus Transfer Antar-Rekening
                            </div>

                            <!-- Rekening Asal -->
                            <div id="bulkFieldCardSourceAccount" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                                <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                    <input
                                        type="checkbox"
                                        id="bulkApplySourceAccount"
                                        name="apply_source_account"
                                        value="1"
                                        data-bulk-field-toggle="bulkBodySourceAccount"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                    <span>Rekening Asal Transfer</span>
                                </label>
                                <div id="bulkBodySourceAccount" class="opacity-55 pointer-events-none transition-opacity" inert>
                                    <select
                                        id="bulkSourceAccountId"
                                        name="source_account_id"
                                        class="field-input text-xs w-full"
                                        data-searchable-picker="true"
                                        data-picker-kind="account"
                                        data-picker-noun="rekening"
                                        data-show-recent-chips="true"
                                        data-recent-ids='@json($recentAccountIds)'
                                    >
                                        <option value="">-- Pilih Rekening Asal --</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Rekening Tujuan -->
                            <div id="bulkFieldCardDestAccount" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                                <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                    <input
                                        type="checkbox"
                                        id="bulkApplyDestinationAccount"
                                        name="apply_destination_account"
                                        value="1"
                                        data-bulk-field-toggle="bulkBodyDestinationAccount"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                    <span>Rekening Tujuan Transfer</span>
                                </label>
                                <div id="bulkBodyDestinationAccount" class="opacity-55 pointer-events-none transition-opacity" inert>
                                    <select
                                        id="bulkDestinationAccountId"
                                        name="destination_account_id"
                                        class="field-input text-xs w-full"
                                        data-searchable-picker="true"
                                        data-picker-kind="account"
                                        data-picker-noun="rekening"
                                        data-show-recent-chips="true"
                                        data-recent-ids='@json($recentAccountIds)'
                                    >
                                        <option value="">-- Pilih Rekening Tujuan --</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Biaya Admin -->
                            <div id="bulkFieldCardFeeAmount" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                                <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                    <input
                                        type="checkbox"
                                        id="bulkApplyFeeAmount"
                                        name="apply_fee_amount"
                                        value="1"
                                        data-bulk-field-toggle="bulkBodyFeeAmount"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                    <span>Biaya Admin Transfer (Rp)</span>
                                </label>
                                <div id="bulkBodyFeeAmount" class="opacity-55 pointer-events-none transition-opacity" inert>
                                    <input
                                        type="text"
                                        id="bulkFeeAmountDisplay"
                                        inputmode="numeric"
                                        placeholder="0"
                                        class="field-input text-xs w-full font-mono"
                                        oninput="onBulkMonetaryInput('bulkFeeAmountDisplay', 'bulkFeeAmount', true)"
                                    >
                                    <input type="hidden" id="bulkFeeAmount" name="fee_amount" value="0">
                                </div>
                            </div>

                            <!-- Kategori Biaya Admin -->
                            <div id="bulkFieldCardFeeCategory" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                                <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                    <input
                                        type="checkbox"
                                        id="bulkApplyFeeCategory"
                                        name="apply_fee_category"
                                        value="1"
                                        data-bulk-field-toggle="bulkBodyFeeCategory"
                                        class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                    >
                                    <span>Kategori Biaya Admin</span>
                                </label>
                                <div id="bulkBodyFeeCategory" class="opacity-55 pointer-events-none transition-opacity" inert>
                                    <select id="bulkFeeCategoryId" name="fee_category_id" class="field-input text-xs w-full">
                                        <option value="">Otomatis (Admin / Layanan)</option>
                                        @foreach ($bulkActiveExpenseCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Field: Nominal -->
                        <div id="bulkFieldCardAmount" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                <input
                                    type="checkbox"
                                    id="bulkApplyAmount"
                                    name="apply_amount"
                                    value="1"
                                    data-bulk-field-toggle="bulkBodyAmount"
                                    class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                >
                                <span>Nominal (Rp)</span>
                            </label>
                            <div id="bulkBodyAmount" class="opacity-55 pointer-events-none transition-opacity space-y-1" inert>
                                <div id="bulkAmountInputWrap" class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none">Rp</span>
                                    <input
                                        type="text"
                                        id="bulkAmountDisplay"
                                        inputmode="numeric"
                                        placeholder="Contoh: 50.000"
                                        class="field-input text-xs w-full pl-9 font-mono"
                                        oninput="onBulkMonetaryInput('bulkAmountDisplay', 'bulkAmount', false)"
                                    >
                                    <input type="hidden" id="bulkAmount" name="amount" value="">
                                </div>
                                <p id="bulkAmountDisabledMsg" class="hidden text-[11px] text-muted leading-snug py-1"></p>
                            </div>
                        </div>

                        <!-- Field: Member Terkait -->
                        <div id="bulkFieldCardMember" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                <input
                                    type="checkbox"
                                    id="bulkApplyMember"
                                    name="apply_member"
                                    value="1"
                                    data-bulk-field-toggle="bulkBodyMember"
                                    class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                >
                                <span>Member Terkait</span>
                            </label>
                            <div id="bulkBodyMember" class="opacity-55 pointer-events-none transition-opacity" inert>
                                <select id="bulkMemberId" name="member_id" class="field-input text-xs w-full">
                                    <option value="">Tanpa Member (Umum / Hapus Tag)</option>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Field: Tanggal -->
                        <div id="bulkFieldCardDate" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                <input
                                    type="checkbox"
                                    id="bulkApplyDate"
                                    name="apply_transaction_date"
                                    value="1"
                                    data-bulk-field-toggle="bulkBodyDate"
                                    class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                >
                                <span>Tanggal Transaksi</span>
                            </label>
                            <div id="bulkBodyDate" class="opacity-55 pointer-events-none transition-opacity" inert>
                                <input
                                    type="date"
                                    id="bulkTransactionDate"
                                    name="transaction_date"
                                    class="field-input text-xs w-full"
                                >
                            </div>
                        </div>

                        <!-- Field: Jam -->
                        <div id="bulkFieldCardTime" class="p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                <input
                                    type="checkbox"
                                    id="bulkApplyTime"
                                    name="apply_transaction_time"
                                    value="1"
                                    data-bulk-field-toggle="bulkBodyTime"
                                    class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                >
                                <span>Jam Transaksi</span>
                            </label>
                            <div id="bulkBodyTime" class="opacity-55 pointer-events-none transition-opacity space-y-1" inert>
                                <input
                                    type="time"
                                    id="bulkTransactionTime"
                                    name="transaction_time"
                                    class="field-input text-xs w-full"
                                >
                                <span class="block text-[11px] text-muted">
                                    Biarkan kosong dan centang kolom ini untuk menghapus jam.
                                </span>
                            </div>
                        </div>

                        <!-- Field: Keterangan -->
                        <div id="bulkFieldCardDescription" class="md:col-span-2 p-3 rounded-xl border border-line bg-surface/60 space-y-2 transition-colors">
                            <label class="inline-flex items-center gap-2 min-h-[28px] cursor-pointer text-xs font-bold text-charcoal select-none">
                                <input
                                    type="checkbox"
                                    id="bulkApplyDescription"
                                    name="apply_description"
                                    value="1"
                                    data-bulk-field-toggle="bulkBodyDescription"
                                    class="w-4 h-4 rounded border-line text-forest focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest cursor-pointer"
                                >
                                <span>Keterangan Transaksi</span>
                            </label>
                            <div id="bulkBodyDescription" class="opacity-55 pointer-events-none transition-opacity" inert>
                                <input
                                    type="text"
                                    id="bulkDescription"
                                    name="description"
                                    maxlength="255"
                                    placeholder="Masukkan keterangan seragam untuk seluruh transaksi terpilih"
                                    class="field-input text-xs w-full"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Mode Void -->
                <div id="bulkVoidSection" class="hidden space-y-3">
                    <div class="p-4 rounded-xl border border-red-200 dark:border-red-800/70 bg-red-50 dark:bg-red-950/50 text-red-800 dark:text-red-200 space-y-2 text-xs">
                        <div class="font-bold text-sm text-red-700 dark:text-red-300">
                            Konfirmasi Pembatalan (Void) Massal
                        </div>
                        <ul class="list-disc pl-4 space-y-1 leading-relaxed">
                            <li>Seluruh transaksi yang dipilih akan berstatus <strong>VOID</strong> dan tidak lagi dihitung dalam saldo rekening maupun laporan.</li>
                            <li>Transaksi yang berasal dari jadwal rutin akan dikembalikan menjadi status <strong>pending</strong>, dan rencana cicilan pembelian kredit terkait akan dibatalkan otomatis.</li>
                            <li>Tindakan ini tidak menghapus riwayat transaksi dan tetap tercatat pada Riwayat Revisi masing-masing transaksi.</li>
                        </ul>
                    </div>

                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-line bg-surface/70 cursor-pointer text-xs text-charcoal select-none">
                        <input
                            type="checkbox"
                            id="bulkVoidConfirmCheck"
                            class="w-4 h-4 mt-0.5 rounded border-line text-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 cursor-pointer shrink-0"
                            onchange="syncBulkSubmitButtonState()"
                        >
                        <span>Saya memahami konsekuensinya dan yakin ingin membatalkan (void) seluruh transaksi yang dipilih.</span>
                    </label>
                </div>

                <!-- Shared Audit Reason -->
                <div class="p-3 rounded-xl border border-line bg-cream/30 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label for="bulkReason" class="text-xs font-bold text-charcoal">
                            Alasan Koreksi / Catatan Audit <span class="font-normal text-muted">(Opsional)</span>
                        </label>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" onclick="applyBulkReasonPreset('Salah kategori')" class="px-2 py-1 rounded-md border border-line bg-panel text-[11px] font-medium text-charcoal hover:bg-cream transition cursor-pointer">
                                Salah kategori
                            </button>
                            <button type="button" onclick="applyBulkReasonPreset('Pindah rekening')" class="px-2 py-1 rounded-md border border-line bg-panel text-[11px] font-medium text-charcoal hover:bg-cream transition cursor-pointer">
                                Pindah rekening
                            </button>
                            <button type="button" onclick="applyBulkReasonPreset('Koreksi tanggal')" class="px-2 py-1 rounded-md border border-line bg-panel text-[11px] font-medium text-charcoal hover:bg-cream transition cursor-pointer">
                                Koreksi tanggal
                            </button>
                            <button type="button" onclick="applyBulkReasonPreset('Input duplikat')" class="px-2 py-1 rounded-md border border-line bg-panel text-[11px] font-medium text-charcoal hover:bg-cream transition cursor-pointer">
                                Input duplikat
                            </button>
                        </div>
                    </div>
                    <input
                        type="text"
                        id="bulkReason"
                        name="reason"
                        maxlength="255"
                        placeholder="Catatan audit untuk Riwayat Revisi transaksi (opsional)"
                        class="field-input text-xs w-full"
                    >
                </div>

                <!-- Modal Footer -->
                <div class="border-t border-line pt-4 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                    <button
                        type="button"
                        onclick="closeBulkCorrectModal()"
                        class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] px-4 justify-center cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        id="bulkModalSubmitBtn"
                        class="btn-arus-primary min-h-[44px] sm:min-h-[38px] px-5 justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Terapkan Koreksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const bulkSelectedTransactions = new Map();
        let bulkModalCurrentMode = 'update';
        let bulkIsSubmitting = false;

        function getBulkEligibleCheckboxes() {
            return Array.from(document.querySelectorAll('#transactionResultsContainer [data-bulk-select-row]:not(:disabled)'));
        }

        function setBulkRowSelected(checkbox) {
            const id = String(checkbox.getAttribute('data-trx-id') || '');
            if (!id) return;

            if (checkbox.checked) {
                bulkSelectedTransactions.set(id, {
                    id: Number(id),
                    version: Number(checkbox.getAttribute('data-trx-version') || 1),
                    type: String(checkbox.getAttribute('data-trx-type') || ''),
                    installment: checkbox.getAttribute('data-trx-installment') === '1',
                    desc: String(checkbox.getAttribute('data-trx-desc') || ''),
                });
            } else {
                bulkSelectedTransactions.delete(id);
            }
        }

        function syncBulkDomSelectionUI() {
            const selectedIds = new Set(bulkSelectedTransactions.keys());

            document.querySelectorAll('#transactionResultsContainer [data-bulk-select-row]').forEach(cb => {
                const id = String(cb.getAttribute('data-trx-id') || '');
                const isSelected = !cb.disabled && selectedIds.has(id);
                cb.checked = isSelected;
            });

            document.querySelectorAll('#transactionResultsContainer [data-trx-row], #transactionResultsContainer [data-trx-card]').forEach(el => {
                const id = String(el.getAttribute('data-trx-row') || el.getAttribute('data-trx-card') || '');
                el.classList.toggle('bg-forest-light/25', selectedIds.has(id));
            });

            const eligibleCheckboxes = getBulkEligibleCheckboxes();
            const uniqueEligibleIds = new Set(eligibleCheckboxes.map(cb => String(cb.getAttribute('data-trx-id'))));
            const selectedOnPageCount = Array.from(uniqueEligibleIds).filter(id => selectedIds.has(id)).length;
            const allChecked = uniqueEligibleIds.size > 0 && selectedOnPageCount === uniqueEligibleIds.size;
            const someChecked = selectedOnPageCount > 0 && selectedOnPageCount < uniqueEligibleIds.size;

            document.querySelectorAll('#transactionResultsContainer [data-bulk-select-all]').forEach(selectAllCb => {
                selectAllCb.disabled = uniqueEligibleIds.size === 0;
                selectAllCb.checked = allChecked;
                selectAllCb.indeterminate = someChecked;
            });

            const bar = document.getElementById('bulkActionBar');
            const countEl = document.getElementById('bulkSelectedCount');
            const breakdownEl = document.getElementById('bulkSelectionBreakdown');
            const installmentBadge = document.getElementById('bulkInstallmentBadge');

            if (!bar || !countEl || !breakdownEl) return;

            const totalSelected = bulkSelectedTransactions.size;
            bar.classList.toggle('hidden', totalSelected === 0);
            countEl.textContent = String(totalSelected);

            const typeCounts = { expense: 0, income: 0, transfer: 0 };
            let hasInstallment = false;
            bulkSelectedTransactions.forEach(item => {
                if (Object.prototype.hasOwnProperty.call(typeCounts, item.type)) {
                    typeCounts[item.type]++;
                }
                if (item.installment) {
                    hasInstallment = true;
                }
            });

            const parts = [];
            if (typeCounts.expense > 0) parts.push(`${typeCounts.expense} Pengeluaran`);
            if (typeCounts.income > 0) parts.push(`${typeCounts.income} Pemasukan`);
            if (typeCounts.transfer > 0) parts.push(`${typeCounts.transfer} Transfer`);
            breakdownEl.textContent = parts.length > 0 ? `(${parts.join(', ')})` : '';

            if (installmentBadge) {
                installmentBadge.classList.toggle('hidden', !hasInstallment);
            }
        }

        function syncBulkSelectionAfterResultsRender() {
            const visibleEligible = new Map();
            getBulkEligibleCheckboxes().forEach(cb => {
                const id = String(cb.getAttribute('data-trx-id') || '');
                if (id) {
                    visibleEligible.set(id, {
                        id: Number(id),
                        version: Number(cb.getAttribute('data-trx-version') || 1),
                        type: String(cb.getAttribute('data-trx-type') || ''),
                        installment: cb.getAttribute('data-trx-installment') === '1',
                        desc: String(cb.getAttribute('data-trx-desc') || ''),
                    });
                }
            });

            Array.from(bulkSelectedTransactions.keys()).forEach(id => {
                if (!visibleEligible.has(id)) {
                    bulkSelectedTransactions.delete(id);
                } else {
                    bulkSelectedTransactions.set(id, visibleEligible.get(id));
                }
            });

            syncBulkDomSelectionUI();
        }
        window.syncBulkSelectionAfterResultsRender = syncBulkSelectionAfterResultsRender;

        function clearBulkSelection() {
            bulkSelectedTransactions.clear();
            syncBulkDomSelectionUI();
        }

        function hideBulkModalError() {
            const errBox = document.getElementById('bulkModalError');
            if (!errBox) return;
            errBox.innerHTML = '';
            errBox.classList.add('hidden');
        }

        function showBulkModalErrors(messages) {
            const errBox = document.getElementById('bulkModalError');
            if (!errBox) return;

            const list = (Array.isArray(messages) ? messages : [messages])
                .map(msg => String(msg || '').trim())
                .filter(Boolean);

            if (list.length === 0) {
                hideBulkModalError();
                return;
            }

            errBox.innerHTML = '';
            list.forEach(msg => {
                const div = document.createElement('div');
                div.textContent = msg;
                errBox.appendChild(div);
            });
            errBox.classList.remove('hidden');
            errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function showBulkActionFeedback(message) {
            const box = document.getElementById('bulkActionFeedback');
            const text = document.getElementById('bulkActionFeedbackText');
            if (!box || !text) return;
            text.textContent = message;
            box.classList.remove('hidden');
            box.classList.add('flex');
        }

        function hideBulkActionFeedback() {
            const box = document.getElementById('bulkActionFeedback');
            if (!box) return;
            box.classList.add('hidden');
            box.classList.remove('flex');
        }

        function toggleBulkFieldBody(checkbox) {
            const targetId = checkbox.getAttribute('data-bulk-field-toggle');
            if (!targetId) return;
            const body = document.getElementById(targetId);
            const card = checkbox.closest('[id^="bulkFieldCard"]');
            const enabled = checkbox.checked && !checkbox.disabled;

            if (body) {
                body.classList.toggle('opacity-55', !enabled);
                body.classList.toggle('pointer-events-none', !enabled);
                body.inert = !enabled;
                if (!enabled) {
                    document.querySelectorAll('.arus-category-popover').forEach(popover => {
                        popover.dispatchEvent(new CustomEvent('arus:force-close'));
                    });
                }
                body.querySelectorAll('input, select').forEach(el => {
                    if (el.id === 'bulkExpenseCategoryId' || el.id === 'bulkIncomeCategoryId') {
                        return;
                    }
                    el.disabled = !enabled;
                });
            }

            if (targetId === 'bulkBodyCategory') {
                const expWrap = document.getElementById('bulkExpenseCategoryWrap');
                const expSel = document.getElementById('bulkExpenseCategoryId');
                const incSel = document.getElementById('bulkIncomeCategoryId');
                const isExpenseVisible = expWrap && !expWrap.classList.contains('hidden');
                if (expSel) expSel.disabled = !(enabled && isExpenseVisible);
                if (incSel) incSel.disabled = !(enabled && !isExpenseVisible);
            }

            if (card) {
                card.classList.toggle('border-forest/60', enabled);
                card.classList.toggle('bg-panel', enabled);
                card.classList.toggle('border-line', !enabled);
                card.classList.toggle('bg-surface/60', !enabled);
            }
        }

        function getBulkSelectionStats() {
            const types = new Set();
            let hasInstallment = false;
            bulkSelectedTransactions.forEach(item => {
                types.add(item.type);
                if (item.installment) {
                    hasInstallment = true;
                }
            });
            return {
                count: bulkSelectedTransactions.size,
                types,
                singleType: types.size === 1 ? Array.from(types)[0] : null,
                hasInstallment,
            };
        }

        function resetBulkModalFields() {
            const form = document.getElementById('bulkCorrectForm');
            if (!form) return;

            form.querySelectorAll('[data-bulk-field-toggle]').forEach(cb => {
                cb.checked = false;
                toggleBulkFieldBody(cb);
            });

            ['bulkExpenseCategoryId', 'bulkIncomeCategoryId', 'bulkAccountId', 'bulkSourceAccountId', 'bulkDestinationAccountId', 'bulkFeeCategoryId', 'bulkMemberId'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.value = '';
                    el.dispatchEvent(new Event('change'));
                }
            });

            ['bulkAmountDisplay', 'bulkAmount', 'bulkFeeAmountDisplay', 'bulkTransactionDate', 'bulkTransactionTime', 'bulkDescription', 'bulkReason'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

            const feeHidden = document.getElementById('bulkFeeAmount');
            if (feeHidden) feeHidden.value = '0';

            const voidConfirm = document.getElementById('bulkVoidConfirmCheck');
            if (voidConfirm) voidConfirm.checked = false;
        }

        function configureBulkCompatibilityUI() {
            const stats = getBulkSelectionStats();
            const summaryEl = document.getElementById('bulkModalSelectionSummary');
            const compatNotice = document.getElementById('bulkCompatibilityNotice');

            const typeParts = [];
            let expCount = 0;
            let incCount = 0;
            let trfCount = 0;
            bulkSelectedTransactions.forEach(item => {
                if (item.type === 'expense') expCount++;
                else if (item.type === 'income') incCount++;
                else if (item.type === 'transfer') trfCount++;
            });
            if (expCount > 0) typeParts.push(`${expCount} Pengeluaran`);
            if (incCount > 0) typeParts.push(`${incCount} Pemasukan`);
            if (trfCount > 0) typeParts.push(`${trfCount} Transfer`);

            if (summaryEl) {
                summaryEl.textContent = `${stats.count} transaksi dipilih (${typeParts.join(', ')})`;
            }

            const notices = [];

            // Kategori availability
            const catCb = document.getElementById('bulkApplyCategory');
            const catPill = document.getElementById('bulkCategoryTypePill');
            const expWrap = document.getElementById('bulkExpenseCategoryWrap');
            const incWrap = document.getElementById('bulkIncomeCategoryWrap');
            const catMsg = document.getElementById('bulkCategoryDisabledMsg');
            const categorySupported = stats.singleType === 'expense' || stats.singleType === 'income';

            if (catCb && expWrap && incWrap && catMsg && catPill) {
                if (stats.singleType === 'expense') {
                    catCb.disabled = false;
                    catPill.textContent = 'Pengeluaran';
                    expWrap.classList.remove('hidden');
                    incWrap.classList.add('hidden');
                    catMsg.classList.add('hidden');
                } else if (stats.singleType === 'income') {
                    catCb.disabled = false;
                    catPill.textContent = 'Pemasukan';
                    expWrap.classList.add('hidden');
                    incWrap.classList.remove('hidden');
                    catMsg.classList.add('hidden');
                } else {
                    catCb.checked = false;
                    catCb.disabled = true;
                    catPill.textContent = 'Tidak tersedia';
                    expWrap.classList.add('hidden');
                    incWrap.classList.add('hidden');
                    catMsg.classList.remove('hidden');
                    notices.push('Kolom Kategori dinonaktifkan karena pilihan berisi transaksi Transfer atau mencampur Pemasukan dan Pengeluaran.');
                }
                toggleBulkFieldBody(catCb);
            }

            // Account & Transfer section availability
            const accCard = document.getElementById('bulkFieldCardAccount');
            const accCb = document.getElementById('bulkApplyAccount');
            const accSelectWrap = document.getElementById('bulkSingleAccountSelectWrap');
            const accMsg = document.getElementById('bulkAccountDisabledMsg');
            const accBadge = document.getElementById('bulkAccountBadge');
            const transferSection = document.getElementById('bulkTransferSection');

            if (stats.singleType === 'transfer') {
                if (accCard) accCard.classList.add('hidden');
                if (accCb) {
                    accCb.checked = false;
                    accCb.disabled = true;
                    toggleBulkFieldBody(accCb);
                }
                if (transferSection) transferSection.classList.remove('hidden');
            } else {
                if (accCard) accCard.classList.remove('hidden');
                if (transferSection) {
                    transferSection.classList.add('hidden');
                    transferSection.querySelectorAll('[data-bulk-field-toggle]').forEach(cb => {
                        cb.checked = false;
                        toggleBulkFieldBody(cb);
                    });
                }

                const singleAccountBlocked = stats.types.has('transfer') || stats.hasInstallment;
                if (accCb && accSelectWrap && accMsg && accBadge) {
                    if (singleAccountBlocked) {
                        accCb.checked = false;
                        accCb.disabled = true;
                        accSelectWrap.classList.add('hidden');
                        accMsg.classList.remove('hidden');
                        accBadge.classList.remove('hidden');
                        accBadge.textContent = 'Terkunci';
                        accMsg.textContent = stats.types.has('transfer')
                            ? 'Kolom rekening tunggal tidak tersedia saat pilihan mencampur transaksi Transfer dengan Pengeluaran/Pemasukan.'
                            : 'Rekening transaksi pembelian cicilan tidak dapat diubah secara massal.';
                    } else {
                        accCb.disabled = false;
                        accSelectWrap.classList.remove('hidden');
                        accMsg.classList.add('hidden');
                        accBadge.classList.add('hidden');
                    }
                    toggleBulkFieldBody(accCb);
                }
            }

            if (stats.types.has('transfer') && stats.types.size > 1) {
                notices.push('Transaksi Transfer dicampur dengan jenis lain: Anda tetap dapat mengubah Tanggal, Jam, Member, Keterangan, Nominal, atau melakukan Void Massal.');
            }

            // Amount lock when installment plan is present
            const amtCb = document.getElementById('bulkApplyAmount');
            const amtWrap = document.getElementById('bulkAmountInputWrap');
            const amtMsg = document.getElementById('bulkAmountDisabledMsg');
            if (amtCb && amtWrap && amtMsg) {
                if (stats.hasInstallment) {
                    amtCb.checked = false;
                    amtCb.disabled = true;
                    amtWrap.classList.add('hidden');
                    amtMsg.classList.remove('hidden');
                    amtMsg.textContent = 'Nominal tidak dapat diubah karena pilihan mencakup transaksi pembelian cicilan kredit.';
                    notices.push('Sebagian transaksi merupakan pembelian cicilan kredit, sehingga Nominal dan Rekening dikunci.');
                } else {
                    amtCb.disabled = false;
                    amtWrap.classList.remove('hidden');
                    amtMsg.classList.add('hidden');
                }
                toggleBulkFieldBody(amtCb);
            }

            if (compatNotice) {
                if (notices.length > 0) {
                    compatNotice.textContent = notices.join(' ');
                    compatNotice.classList.remove('hidden');
                } else {
                    compatNotice.textContent = '';
                    compatNotice.classList.add('hidden');
                }
            }
        }

        function switchBulkModalMode(mode) {
            bulkModalCurrentMode = mode === 'void' ? 'void' : 'update';
            const actionInput = document.getElementById('bulkActionInput');
            const updateSection = document.getElementById('bulkUpdateFieldsSection');
            const voidSection = document.getElementById('bulkVoidSection');
            const tabUpdate = document.getElementById('bulkModeTabUpdate');
            const tabVoid = document.getElementById('bulkModeTabVoid');

            if (actionInput) actionInput.value = bulkModalCurrentMode;
            if (updateSection) updateSection.classList.toggle('hidden', bulkModalCurrentMode !== 'update');
            if (voidSection) voidSection.classList.toggle('hidden', bulkModalCurrentMode !== 'void');

            if (tabUpdate && tabVoid) {
                const isUpdate = bulkModalCurrentMode === 'update';
                tabUpdate.setAttribute('aria-selected', isUpdate ? 'true' : 'false');
                tabVoid.setAttribute('aria-selected', !isUpdate ? 'true' : 'false');

                tabUpdate.className = isUpdate
                    ? 'min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg text-xs font-bold transition cursor-pointer bg-panel text-forest dark:text-blue-300 border border-line shadow-2xs'
                    : 'min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-muted hover:text-charcoal border border-transparent';

                tabVoid.className = !isUpdate
                    ? 'min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg text-xs font-bold transition cursor-pointer bg-red-700 text-white border border-red-700 shadow-2xs'
                    : 'min-h-[44px] sm:min-h-[38px] px-3 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/40 border border-transparent';
            }

            hideBulkModalError();
            syncBulkSubmitButtonState();
        }

        function syncBulkSubmitButtonState() {
            const btn = document.getElementById('bulkModalSubmitBtn');
            const voidConfirm = document.getElementById('bulkVoidConfirmCheck');
            if (!btn) return;

            if (bulkIsSubmitting) {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
                return;
            }

            if (bulkModalCurrentMode === 'void') {
                btn.className = 'min-h-[44px] sm:min-h-[38px] px-5 py-2 rounded-lg bg-red-700 hover:bg-red-800 text-white text-xs font-bold transition inline-flex items-center justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed';
                btn.textContent = `Batalkan (Void) ${bulkSelectedTransactions.size} Transaksi`;
                btn.disabled = !(voidConfirm && voidConfirm.checked);
            } else {
                btn.className = 'btn-arus-primary min-h-[44px] sm:min-h-[38px] px-5 justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed';
                btn.textContent = `Terapkan Koreksi (${bulkSelectedTransactions.size} Transaksi)`;
                btn.disabled = false;
            }
        }

        function openBulkCorrectModal(mode = 'update') {
            if (bulkSelectedTransactions.size === 0) return;

            const modal = document.getElementById('bulkCorrectModal');
            if (!modal) return;

            hideBulkModalError();
            resetBulkModalFields();
            configureBulkCompatibilityUI();
            switchBulkModalMode(mode);

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            const focusTarget = document.getElementById(mode === 'void' ? 'bulkVoidConfirmCheck' : 'bulkModeTabUpdate');
            if (focusTarget) {
                setTimeout(() => focusTarget.focus(), 40);
            }
        }

        function closeBulkCorrectModal() {
            if (bulkIsSubmitting) return;
            const modal = document.getElementById('bulkCorrectModal');
            if (!modal) return;

            document.querySelectorAll('.arus-category-popover').forEach(popover => {
                popover.dispatchEvent(new CustomEvent('arus:force-close'));
            });

            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function applyBulkReasonPreset(text) {
            const input = document.getElementById('bulkReason');
            if (!input) return;
            input.value = text;
            input.focus();
        }

        function onBulkMonetaryInput(displayId, hiddenId, allowZero = false) {
            const displayEl = document.getElementById(displayId);
            const hiddenEl = document.getElementById(hiddenId);
            if (!displayEl || !hiddenEl) return;

            const digits = displayEl.value.replace(/\D/g, '');
            const num = digits !== '' ? parseInt(digits, 10) : (allowZero ? 0 : null);

            if (num === null) {
                displayEl.value = '';
                hiddenEl.value = '';
                return;
            }

            if (num === 0 && !allowZero) {
                displayEl.value = '';
                hiddenEl.value = '';
                return;
            }

            displayEl.value = Number(num).toLocaleString('id-ID');
            hiddenEl.value = String(num);
        }

        function buildBulkPayload() {
            const ids = Array.from(bulkSelectedTransactions.values()).map(item => item.id);
            const versions = {};
            bulkSelectedTransactions.forEach(item => {
                versions[item.id] = item.version;
            });

            const reasonVal = (document.getElementById('bulkReason')?.value || '').trim();
            const payload = {
                action: bulkModalCurrentMode,
                transaction_ids: ids,
                versions,
                reason: reasonVal !== '' ? reasonVal : null,
            };

            if (bulkModalCurrentMode === 'void') {
                return { payload, errors: [] };
            }

            const stats = getBulkSelectionStats();
            const errors = [];
            let appliedCount = 0;

            if (document.getElementById('bulkApplyCategory')?.checked) {
                appliedCount++;
                payload.apply_category = true;
                const catEl = stats.singleType === 'income'
                    ? document.getElementById('bulkIncomeCategoryId')
                    : document.getElementById('bulkExpenseCategoryId');
                const catVal = catEl?.value || '';
                if (!catVal) {
                    errors.push('Pilih kategori baru karena perubahan Kategori diaktifkan.');
                } else {
                    payload.category_id = Number(catVal);
                }
            }

            if (document.getElementById('bulkApplyAccount')?.checked) {
                appliedCount++;
                payload.apply_account = true;
                const accVal = document.getElementById('bulkAccountId')?.value || '';
                if (!accVal) {
                    errors.push('Pilih rekening tujuan perubahan karena perubahan Rekening diaktifkan.');
                } else {
                    payload.account_id = Number(accVal);
                }
            }

            if (document.getElementById('bulkApplySourceAccount')?.checked) {
                appliedCount++;
                payload.apply_source_account = true;
                const srcVal = document.getElementById('bulkSourceAccountId')?.value || '';
                if (!srcVal) {
                    errors.push('Pilih rekening asal transfer yang baru.');
                } else {
                    payload.source_account_id = Number(srcVal);
                }
            }

            if (document.getElementById('bulkApplyDestinationAccount')?.checked) {
                appliedCount++;
                payload.apply_destination_account = true;
                const dstVal = document.getElementById('bulkDestinationAccountId')?.value || '';
                if (!dstVal) {
                    errors.push('Pilih rekening tujuan transfer yang baru.');
                } else {
                    payload.destination_account_id = Number(dstVal);
                }
            }

            if (payload.apply_source_account && payload.apply_destination_account && payload.source_account_id === payload.destination_account_id) {
                errors.push('Rekening tujuan transfer harus berbeda dengan rekening asal.');
            }

            if (document.getElementById('bulkApplyFeeAmount')?.checked) {
                appliedCount++;
                payload.apply_fee_amount = true;
                const rawFee = document.getElementById('bulkFeeAmount')?.value;
                payload.fee_amount = rawFee !== '' ? Number(rawFee) : 0;
            }

            if (document.getElementById('bulkApplyFeeCategory')?.checked) {
                appliedCount++;
                payload.apply_fee_category = true;
                const feeCatVal = document.getElementById('bulkFeeCategoryId')?.value || '';
                payload.fee_category_id = feeCatVal !== '' ? Number(feeCatVal) : null;
            }

            if (document.getElementById('bulkApplyAmount')?.checked) {
                appliedCount++;
                payload.apply_amount = true;
                const amtVal = Number(document.getElementById('bulkAmount')?.value || 0);
                if (!amtVal || amtVal <= 0) {
                    errors.push('Masukkan nominal transaksi lebih dari Rp 0.');
                } else {
                    payload.amount = amtVal;
                }
            }

            if (document.getElementById('bulkApplyMember')?.checked) {
                appliedCount++;
                payload.apply_member = true;
                const memVal = document.getElementById('bulkMemberId')?.value || '';
                payload.member_id = memVal !== '' ? Number(memVal) : null;
            }

            if (document.getElementById('bulkApplyDate')?.checked) {
                appliedCount++;
                payload.apply_transaction_date = true;
                const dateVal = document.getElementById('bulkTransactionDate')?.value || '';
                if (!dateVal) {
                    errors.push('Isi tanggal transaksi yang baru.');
                } else {
                    payload.transaction_date = dateVal;
                }
            }

            if (document.getElementById('bulkApplyTime')?.checked) {
                appliedCount++;
                payload.apply_transaction_time = true;
                const timeVal = document.getElementById('bulkTransactionTime')?.value || '';
                payload.transaction_time = timeVal !== '' ? timeVal : null;
            }

            if (document.getElementById('bulkApplyDescription')?.checked) {
                appliedCount++;
                payload.apply_description = true;
                const descVal = (document.getElementById('bulkDescription')?.value || '').trim();
                if (!descVal) {
                    errors.push('Isi keterangan transaksi yang baru.');
                } else {
                    payload.description = descVal;
                }
            }

            if (appliedCount === 0) {
                errors.push('Centang minimal satu kolom yang ingin diubah secara seragam.');
            }

            return { payload, errors };
        }

        async function submitBulkCorrection(event) {
            event.preventDefault();
            if (bulkIsSubmitting) return false;

            hideBulkModalError();

            if (bulkSelectedTransactions.size === 0) {
                showBulkModalErrors('Tidak ada transaksi yang dipilih.');
                return false;
            }

            if (bulkModalCurrentMode === 'void' && !document.getElementById('bulkVoidConfirmCheck')?.checked) {
                showBulkModalErrors('Centang kotak konfirmasi pembatalan transaksi terlebih dahulu.');
                return false;
            }

            const { payload, errors } = buildBulkPayload();
            if (errors.length > 0) {
                showBulkModalErrors(errors);
                return false;
            }

            const form = document.getElementById('bulkCorrectForm');
            const csrfToken = form?.querySelector('input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '';
            const submitUrl = resolveFormActionUrl(form, @js(route('transactions.bulk-correct', [], false)));
            bulkIsSubmitting = true;
            syncBulkSubmitButtonState();

            try {
                const response = await fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                const data = isJson ? await response.json().catch(() => ({})) : {};

                if (response.status === 419 || (response.redirected && !isJson)) {
                    showBulkModalErrors('Sesi Anda telah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
                    return false;
                }

                if (!response.ok || !isJson) {
                    const serverErrors = [];
                    if (data.errors && typeof data.errors === 'object') {
                        Object.values(data.errors).forEach(arr => {
                            if (Array.isArray(arr)) {
                                arr.forEach(msg => serverErrors.push(msg));
                            }
                        });
                    }
                    if (serverErrors.length === 0) {
                        serverErrors.push(
                            data.message
                            || `Gagal memproses koreksi massal (HTTP ${response.status}). Silakan muat ulang halaman dan coba lagi.`
                        );
                    }
                    showBulkModalErrors(serverErrors);
                    return false;
                }

                bulkIsSubmitting = false;
                closeBulkCorrectModal();
                clearBulkSelection();
                showBulkActionFeedback(data.message || 'Koreksi massal transaksi berhasil disimpan.');
                await runLiveTransactionSearch();
            } catch (err) {
                showBulkModalErrors('Terjadi gangguan jaringan saat mengirim permintaan koreksi massal.');
            } finally {
                bulkIsSubmitting = false;
                syncBulkSubmitButtonState();
            }

            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const resultsContainer = document.getElementById('transactionResultsContainer');
            if (resultsContainer) {
                resultsContainer.addEventListener('change', function(event) {
                    const rowCb = event.target.closest('[data-bulk-select-row]');
                    if (rowCb) {
                        setBulkRowSelected(rowCb);
                        syncBulkDomSelectionUI();
                        return;
                    }

                    const selectAllCb = event.target.closest('[data-bulk-select-all]');
                    if (selectAllCb) {
                        const checked = selectAllCb.checked;
                        getBulkEligibleCheckboxes().forEach(cb => {
                            cb.checked = checked;
                            setBulkRowSelected(cb);
                        });
                        syncBulkDomSelectionUI();
                    }
                });
            }

            document.querySelectorAll('#bulkCorrectForm [data-bulk-field-toggle]').forEach(cb => {
                cb.addEventListener('change', function() {
                    toggleBulkFieldBody(this);
                });
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;
                const modal = document.getElementById('bulkCorrectModal');
                if (!modal || modal.classList.contains('hidden')) return;

                const hasOpenCategoryPopover = document.querySelector('.arus-category-popover[style*="display: block"]');
                const hasOpenCalendarPopup = document.querySelector('.arus-calendar-popup[style*="display: block"]');
                if (hasOpenCategoryPopover || hasOpenCalendarPopup) return;

                event.preventDefault();
                closeBulkCorrectModal();
            });
        });
    </script>
</x-layouts.app>
