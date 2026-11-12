@php
    $advAccounts = $quickAccounts ?? collect();
    $advIncomeCats = $quickIncomeCategories ?? collect();
    $advExpenseCats = $quickExpenseCategories ?? collect();
    $advMembers = $quickMembers ?? collect();
    $spaceTimezone = $currentSpace?->timezone ?? 'Asia/Jakarta';
    $spaceToday = $currentSpace ? $currentSpace->now()->toDateString() : now()->toDateString();
    $spaceMonthStart = $currentSpace ? $currentSpace->now()->startOfMonth()->toDateString() : now()->startOfMonth()->toDateString();
    $spaceMonthEnd = $currentSpace ? $currentSpace->now()->endOfMonth()->toDateString() : now()->endOfMonth()->toDateString();
    $spaceLastMonthStart = $currentSpace ? $currentSpace->now()->subMonthNoOverflow()->startOfMonth()->toDateString() : now()->subMonthNoOverflow()->startOfMonth()->toDateString();
    $spaceLastMonthEnd = $currentSpace ? $currentSpace->now()->subMonthNoOverflow()->endOfMonth()->toDateString() : now()->subMonthNoOverflow()->endOfMonth()->toDateString();
    $spaceSevenDaysAgo = $currentSpace ? $currentSpace->now()->subDays(6)->toDateString() : now()->subDays(6)->toDateString();
@endphp

<div
    id="globalAdvancedSearchModal"
    class="fixed inset-0 z-50 bg-charcoal/55 dark:bg-black/75 backdrop-blur-xs flex items-start justify-center p-2 sm:p-4 sm:pt-[5vh] overflow-y-auto hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="advSearchModalTitle"
    data-search-endpoint="{{ route('search.global') }}"
    data-transactions-url="{{ route('transactions.index') }}"
    data-space-id="{{ $currentSpace?->id ?? 0 }}"
    data-today="{{ $spaceToday }}"
    data-month-start="{{ $spaceMonthStart }}"
    data-month-end="{{ $spaceMonthEnd }}"
    data-last-month-start="{{ $spaceLastMonthStart }}"
    data-last-month-end="{{ $spaceLastMonthEnd }}"
    data-seven-days-ago="{{ $spaceSevenDaysAgo }}"
    onclick="if (event.target === this) { window.closeAdvancedSearchModal(); }"
>
    <div
        class="bg-panel border border-line rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[calc(100dvh-1rem)] sm:max-h-[88dvh]"
        onclick="event.stopPropagation()"
    >
        <h2 id="advSearchModalTitle" class="sr-only">Pencarian Lanjutan Global ARUS</h2>

        <!-- Baris Input Utama -->
        <div class="p-3 sm:p-4 border-b border-line bg-panel shrink-0">
            <div class="flex items-center gap-2">
                <div class="relative flex-1 min-w-0">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <input
                        type="search"
                        id="advSearchQueryInput"
                        autocomplete="off"
                        placeholder="Cari transaksi, nominal, rekening, kategori, target, atau halaman..."
                        aria-label="Kata kunci pencarian lanjutan"
                        class="field-input w-full !pl-9 !pr-10 h-11 sm:h-10 text-xs sm:text-sm"
                    />
                    <button
                        type="button"
                        id="advSearchClearQueryBtn"
                        class="hidden absolute inset-y-0 right-1 my-auto w-9 h-9 sm:w-8 sm:h-8 rounded-lg items-center justify-center text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer focus-visible:outline-2 focus-visible:outline-forest"
                        title="Hapus kata kunci"
                        aria-label="Hapus kata kunci"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <button
                    type="button"
                    id="advSearchFilterToggleBtn"
                    aria-expanded="false"
                    aria-controls="advSearchFilterPanel"
                    class="min-h-[44px] sm:min-h-[40px] px-3 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-semibold text-charcoal inline-flex items-center gap-1.5 transition cursor-pointer shrink-0 focus-visible:outline-2 focus-visible:outline-forest"
                >
                    <svg class="w-4 h-4 text-forest dark:text-blue-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                    </svg>
                    <span class="hidden sm:inline">Filter Lanjutan</span>
                    <span class="sm:hidden">Filter</span>
                    <span id="advSearchFilterBadge" class="hidden px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-forest text-white leading-none">0</span>
                </button>

                <button
                    type="button"
                    id="advSearchCloseBtn"
                    onclick="window.closeAdvancedSearchModal()"
                    class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] px-2.5 rounded-lg border border-line bg-panel hover:bg-cream text-muted hover:text-charcoal inline-flex items-center justify-center transition cursor-pointer shrink-0 focus-visible:outline-2 focus-visible:outline-forest"
                    aria-label="Tutup pencarian lanjutan"
                    title="Tutup (Esc)"
                >
                    <svg class="w-4 h-4 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    <span class="hidden sm:inline text-[11px] font-mono font-semibold">Esc</span>
                </button>
            </div>

            <!-- Scope Tabs -->
            <div class="flex items-center gap-1.5 mt-3 overflow-x-auto pb-0.5" role="tablist" aria-label="Cakupan modul pencarian">
                @php
                    $scopes = [
                        'all' => 'Semua',
                        'transactions' => 'Transaksi',
                        'accounts' => 'Rekening',
                        'plans' => 'Target & Rencana',
                        'other' => 'Kredit & Kategori',
                        'pages' => 'Menu',
                    ];
                @endphp
                @foreach ($scopes as $scopeKey => $scopeLabel)
                    <button
                        type="button"
                        role="tab"
                        data-adv-scope="{{ $scopeKey }}"
                        aria-selected="{{ $scopeKey === 'all' ? 'true' : 'false' }}"
                        class="adv-scope-tab min-h-[38px] sm:min-h-[30px] px-3 py-1 rounded-lg text-xs font-semibold border whitespace-nowrap inline-flex items-center gap-1.5 transition cursor-pointer focus-visible:outline-2 focus-visible:outline-forest {{ $scopeKey === 'all' ? 'bg-forest text-white border-forest' : 'bg-panel border-line text-charcoal hover:bg-cream' }}"
                    >
                        <span>{{ $scopeLabel }}</span>
                        <span data-adv-scope-count="{{ $scopeKey }}" class="hidden text-[10px] font-mono px-1.5 py-0.2 rounded-md bg-black/10 dark:bg-white/15">0</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Panel Filter Lanjutan (Collapsible) -->
        <div id="advSearchFilterPanel" class="hidden border-b border-line bg-sidebar/65 px-3 sm:px-4 py-3.5 shrink-0 max-h-[45dvh] overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="advFilterType" class="block text-[11px] font-semibold text-charcoal mb-1">Jenis Transaksi</label>
                    <select id="advFilterType" class="field-select w-full h-9 text-xs">
                        <option value="">Semua Jenis</option>
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                        <option value="transfer">Transfer Antar-Rekening</option>
                        <option value="credit_payment">Pembayaran Kredit</option>
                    </select>
                </div>

                <div>
                    <label for="advFilterStatus" class="block text-[11px] font-semibold text-charcoal mb-1">Status Transaksi</label>
                    <select id="advFilterStatus" class="field-select w-full h-9 text-xs">
                        <option value="posted">Posted (Aktif)</option>
                        <option value="all">Semua Status (Termasuk Void)</option>
                        <option value="void">Void (Dibatalkan)</option>
                    </select>
                </div>

                <div>
                    <label for="advFilterAccount" class="block text-[11px] font-semibold text-charcoal mb-1">Rekening / Wallet</label>
                    <select id="advFilterAccount" class="field-select w-full h-9 text-xs">
                        <option value="">Semua Rekening</option>
                        @foreach ($advAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="advFilterCategory" class="block text-[11px] font-semibold text-charcoal mb-1">Kategori</label>
                    <select id="advFilterCategory" class="field-select w-full h-9 text-xs">
                        <option value="">Semua Kategori</option>
                        @if ($advExpenseCats->isNotEmpty())
                            <optgroup label="Pengeluaran">
                                @foreach ($advExpenseCats as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if ($advIncomeCats->isNotEmpty())
                            <optgroup label="Pemasukan">
                                @foreach ($advIncomeCats as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                @if ($advMembers->isNotEmpty())
                    <div>
                        <label for="advFilterMember" class="block text-[11px] font-semibold text-charcoal mb-1">Member Terkait</label>
                        <select id="advFilterMember" class="field-select w-full h-9 text-xs">
                            <option value="">Semua Member</option>
                            @foreach ($advMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="advFilterStartDate" class="block text-[11px] font-semibold text-charcoal mb-1">Mulai Tanggal</label>
                    <input type="date" id="advFilterStartDate" class="field-input w-full h-9 text-xs" />
                </div>

                <div>
                    <label for="advFilterEndDate" class="block text-[11px] font-semibold text-charcoal mb-1">Sampai Tanggal</label>
                    <input type="date" id="advFilterEndDate" class="field-input w-full h-9 text-xs" />
                </div>

                <div>
                    <label for="advFilterMinAmount" class="block text-[11px] font-semibold text-charcoal mb-1">Nominal Minimum (Rp)</label>
                    <input type="text" inputmode="numeric" id="advFilterMinAmount" placeholder="Contoh: 50.000" class="field-input w-full h-9 text-xs font-mono" />
                </div>

                <div>
                    <label for="advFilterMaxAmount" class="block text-[11px] font-semibold text-charcoal mb-1">Nominal Maksimum (Rp)</label>
                    <input type="text" inputmode="numeric" id="advFilterMaxAmount" placeholder="Contoh: 500.000" class="field-input w-full h-9 text-xs font-mono" />
                </div>

                <div>
                    <label for="advFilterSort" class="block text-[11px] font-semibold text-charcoal mb-1">Urutkan Hasil</label>
                    <select id="advFilterSort" class="field-select w-full h-9 text-xs">
                        <option value="date_desc">Terbaru (Tanggal turun)</option>
                        <option value="date_asc">Terlama (Tanggal naik)</option>
                        <option value="amount_desc">Nominal Terbesar</option>
                        <option value="amount_asc">Nominal Terkecil</option>
                    </select>
                </div>
            </div>

            <!-- Preset Tanggal Cepat & Aksi Filter -->
            <div class="mt-3 pt-3 border-t border-line/70 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[11px] font-semibold text-muted mr-1">Tanggal cepat:</span>
                    <button type="button" data-adv-date-preset="today" class="adv-date-preset min-h-[36px] sm:min-h-[28px] px-2.5 py-1 rounded-md border border-line bg-panel hover:bg-cream text-[11px] font-medium text-charcoal transition cursor-pointer">Hari Ini</button>
                    <button type="button" data-adv-date-preset="last_7_days" class="adv-date-preset min-h-[36px] sm:min-h-[28px] px-2.5 py-1 rounded-md border border-line bg-panel hover:bg-cream text-[11px] font-medium text-charcoal transition cursor-pointer">7 Hari</button>
                    <button type="button" data-adv-date-preset="this_month" class="adv-date-preset min-h-[36px] sm:min-h-[28px] px-2.5 py-1 rounded-md border border-line bg-panel hover:bg-cream text-[11px] font-medium text-charcoal transition cursor-pointer">Bulan Ini</button>
                    <button type="button" data-adv-date-preset="last_month" class="adv-date-preset min-h-[36px] sm:min-h-[28px] px-2.5 py-1 rounded-md border border-line bg-panel hover:bg-cream text-[11px] font-medium text-charcoal transition cursor-pointer">Bulan Lalu</button>
                    <button type="button" data-adv-date-preset="clear" class="adv-date-preset min-h-[36px] sm:min-h-[28px] px-2.5 py-1 rounded-md border border-line bg-panel hover:border-red/40 hover:text-red text-[11px] font-medium text-muted transition cursor-pointer">Hapus</button>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-2.5 flex-wrap">
                    <label class="inline-flex items-center gap-2 text-xs text-charcoal cursor-pointer select-none min-h-[36px]">
                        <input type="checkbox" id="advFilterIncludeFuture" class="field-checkbox" checked />
                        <span>Sertakan transaksi mendatang</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" id="advSearchResetFiltersBtn" class="btn-arus-secondary min-h-[38px] px-3 text-xs font-semibold text-muted hover:text-red cursor-pointer">
                            Reset Filter
                        </button>
                        <a href="{{ route('transactions.index') }}" id="advSearchOpenTransactionsLink" class="btn-arus-primary min-h-[38px] px-3.5 text-xs font-bold">
                            Buka di Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Area Konten Hasil -->
        <div id="advSearchResultsContainer" class="flex-1 overflow-y-auto overscroll-contain p-3.5 sm:p-5 space-y-4">
            <!-- 1. Initial / Discover State -->
            <div id="advSearchInitialState" class="space-y-5">
                <div id="advRecentSearchesSection" class="hidden">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted">Pencarian Terakhir</h3>
                        <button type="button" id="advClearRecentBtn" class="text-[11px] font-semibold text-muted hover:text-red transition cursor-pointer">
                            Hapus Riwayat
                        </button>
                    </div>
                    <div id="advRecentSearchesList" class="flex flex-wrap gap-1.5"></div>
                </div>

                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Pintasan Filter Cepat</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        <button type="button" data-adv-quick-filter="expense_month" class="adv-quick-btn text-left p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition cursor-pointer min-h-[44px] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-charcoal">Pengeluaran Bulan Ini</div>
                                <div class="text-[11px] text-muted">Filter transaksi keluar periode berjalan</div>
                            </div>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-700 dark:text-rose-300 shrink-0">Keluar</span>
                        </button>

                        <button type="button" data-adv-quick-filter="income_month" class="adv-quick-btn text-left p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition cursor-pointer min-h-[44px] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-charcoal">Pemasukan Bulan Ini</div>
                                <div class="text-[11px] text-muted">Filter transaksi masuk periode berjalan</div>
                            </div>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 shrink-0">Masuk</span>
                        </button>

                        <button type="button" data-adv-quick-filter="transfer_all" class="adv-quick-btn text-left p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition cursor-pointer min-h-[44px] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-charcoal">Transfer Antar-Rekening</div>
                                <div class="text-[11px] text-muted">Lacak mutasi pemindahan saldo</div>
                            </div>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-forest-light text-forest dark:text-blue-300 shrink-0">Transfer</span>
                        </button>

                        <button type="button" data-adv-quick-filter="large_amount" class="adv-quick-btn text-left p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition cursor-pointer min-h-[44px] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-charcoal">Transaksi Besar (&ge; Rp 500.000)</div>
                                <div class="text-[11px] text-muted">Filter nominal minimal lima ratus ribu</div>
                            </div>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-cream text-charcoal shrink-0">&ge;500rb</span>
                        </button>

                        <button type="button" data-adv-quick-filter="void_only" class="adv-quick-btn text-left p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition cursor-pointer min-h-[44px] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-charcoal">Transaksi Dibatalkan (Void)</div>
                                <div class="text-[11px] text-muted">Periksa transaksi yang telah di-void</div>
                            </div>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 shrink-0">Void</span>
                        </button>

                        <button type="button" data-adv-quick-filter="open_custom_filters" class="adv-quick-btn text-left p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition cursor-pointer min-h-[44px] flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-forest dark:text-blue-300">Atur Filter Kustom</div>
                                <div class="text-[11px] text-muted">Pilih rekening, kategori, tanggal, &amp; nominal</div>
                            </div>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-forest-light text-forest dark:text-blue-300 shrink-0">Filter</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Navigasi Cepat Ruang Aktif ({{ $currentSpace?->name }})</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <a href="{{ route('transactions.create') }}" data-adv-search-item class="p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[44px] flex items-center gap-2 text-xs font-semibold text-charcoal">
                            <span class="w-6 h-6 rounded-md bg-forest-light text-forest dark:text-blue-300 inline-flex items-center justify-center shrink-0" aria-hidden="true">+</span>
                            <span class="truncate">Catat Transaksi</span>
                        </a>
                        <a href="{{ route('accounts.index') }}" data-adv-search-item class="p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[44px] flex items-center gap-2 text-xs font-semibold text-charcoal">
                            <span class="w-6 h-6 rounded-md bg-cream text-muted inline-flex items-center justify-center shrink-0" aria-hidden="true">R</span>
                            <span class="truncate">Rekening &amp; Wallet</span>
                        </a>
                        <a href="{{ route('reports.index') }}" data-adv-search-item class="p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[44px] flex items-center gap-2 text-xs font-semibold text-charcoal">
                            <span class="w-6 h-6 rounded-md bg-cream text-muted inline-flex items-center justify-center shrink-0" aria-hidden="true">L</span>
                            <span class="truncate">Laporan Keuangan</span>
                        </a>
                        <a href="{{ route('budgets.index') }}" data-adv-search-item class="p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[44px] flex items-center gap-2 text-xs font-semibold text-charcoal">
                            <span class="w-6 h-6 rounded-md bg-cream text-muted inline-flex items-center justify-center shrink-0" aria-hidden="true">A</span>
                            <span class="truncate">Anggaran</span>
                        </a>
                        <a href="{{ route('goals.index') }}" data-adv-search-item class="p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[44px] flex items-center gap-2 text-xs font-semibold text-charcoal">
                            <span class="w-6 h-6 rounded-md bg-cream text-muted inline-flex items-center justify-center shrink-0" aria-hidden="true">T</span>
                            <span class="truncate">Target &amp; Bersama</span>
                        </a>
                        <a href="{{ route('short-plans.index') }}" data-adv-search-item class="p-2.5 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[44px] flex items-center gap-2 text-xs font-semibold text-charcoal">
                            <span class="w-6 h-6 rounded-md bg-cream text-muted inline-flex items-center justify-center shrink-0" aria-hidden="true">S</span>
                            <span class="truncate">Short Plan</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Loading State -->
            <div id="advSearchLoadingState" class="hidden py-10 text-center" role="status" aria-live="polite">
                <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl border border-line bg-sidebar/60 text-xs font-semibold text-charcoal">
                    <svg class="animate-spin w-4 h-4 text-forest dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mencari data di ruang {{ $currentSpace?->name }}...</span>
                </div>
            </div>

            <!-- 3. Error State -->
            <div id="advSearchErrorState" class="hidden p-4 rounded-xl border border-red/30 bg-red/10 text-xs text-red flex flex-col sm:flex-row sm:items-center justify-between gap-3" role="alert">
                <div>
                    <div class="font-bold">Gagal memuat hasil pencarian.</div>
                    <div id="advSearchErrorDetail" class="text-[11px] opacity-90 mt-0.5">Periksa koneksi atau coba jalankan pencarian ulang.</div>
                </div>
                <button type="button" id="advSearchRetryBtn" class="btn-arus-secondary min-h-[38px] px-3.5 text-xs font-bold shrink-0 cursor-pointer">
                    Coba Lagi
                </button>
            </div>

            <!-- 4. Empty State -->
            <div id="advSearchEmptyState" class="hidden py-8 px-4 text-center rounded-xl border border-dashed border-line bg-sidebar/40">
                <div class="text-sm font-bold text-charcoal">Tidak ada hasil yang cocok</div>
                <p id="advSearchEmptyReason" class="text-xs text-muted mt-1 max-w-md mx-auto">
                    Coba ubah kata kunci atau longgarkan filter tanggal, rekening, atau rentang nominal.
                </p>
                <div class="mt-4 flex items-center justify-center gap-2 flex-wrap">
                    <button type="button" id="advSearchEmptyResetBtn" class="btn-arus-secondary min-h-[40px] px-4 text-xs font-bold cursor-pointer">
                        Reset Pencarian &amp; Filter
                    </button>
                    <a href="{{ route('transactions.index') }}" id="advSearchEmptyTxLink" class="btn-arus-primary min-h-[40px] px-4 text-xs font-bold">
                        Buka Daftar Transaksi
                    </a>
                </div>
            </div>

            <!-- 5. Results Content -->
            <div id="advSearchResultsContent" class="hidden space-y-4"></div>
        </div>

        <!-- Footer Pintasan Keyboard -->
        <div class="hidden sm:flex items-center justify-between gap-3 px-4 py-2.5 border-t border-line bg-sidebar/50 text-[11px] text-muted shrink-0">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-panel border border-line font-mono text-[10px]">&uarr;&darr;</kbd> Pilih</span>
                <span class="inline-flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-panel border border-line font-mono text-[10px]">Enter</kbd> Buka</span>
                <span class="inline-flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-panel border border-line font-mono text-[10px]">Esc</kbd> Tutup</span>
            </div>
            <div class="text-[11px] text-muted">
                Ruang aktif: <strong class="text-charcoal">{{ $currentSpace?->name }}</strong>
            </div>
        </div>
    </div>
</div>
