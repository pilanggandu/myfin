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
            $advancedKeys = ['type', 'account_id', 'category_id', 'member_id', 'start_date', 'end_date', 'include_future'];
            $activeCount = 0;
            foreach ($advancedKeys as $k) {
                if (request()->filled($k)) {
                    $activeCount++;
                }
            }
            if (request()->has('status') && request('status') !== 'posted') {
                $activeCount++;
            }
            if (request('sort') === 'date_asc') {
                $activeCount++;
            }
            $isAdvancedOpen = $activeCount > 0;
            $hasAnyFilter = request()->hasAny(['search', 'type', 'account_id', 'category_id', 'member_id', 'start_date', 'end_date', 'include_future'])
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
                        class="field-input h-10 pl-9 pr-9 text-xs w-full" 
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
                        class="btn-arus-secondary h-11 sm:h-10 min-h-[44px] sm:min-h-[40px] px-3.5 text-xs font-bold inline-flex items-center gap-2 cursor-pointer transition select-none flex-1 sm:flex-initial justify-center {{ $isAdvancedOpen ? 'border-forest text-forest dark:text-blue-300 bg-forest-light/40' : '' }}"
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

                    <button type="submit" class="btn-arus-primary h-11 sm:h-10 min-h-[44px] sm:min-h-[40px] px-4 text-xs font-bold cursor-pointer">
                        Terapkan
                    </button>

                    <a
                        href="{{ route('transactions.index') }}"
                        id="transactionResetLink"
                        class="{{ $hasAnyFilter ? 'inline-flex' : 'hidden' }} btn-arus-secondary h-11 sm:h-10 min-h-[44px] sm:min-h-[40px] px-3 text-xs font-bold items-center justify-center text-muted hover:text-red transition cursor-pointer"
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
                <div class="flex items-center gap-1.5 flex-wrap pt-2.5 border-t border-line/60" id="recentCategoryFilterBar">
                    <span class="text-[11px] font-semibold text-muted mr-1">Kategori terakhir:</span>
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
            <div id="advancedFilterPanel" class="{{ $isAdvancedOpen ? '' : 'hidden' }} pt-4 mt-3 border-t border-line transition-all duration-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Jenis Transaksi</label>
                        <select name="type" class="field-select h-9 py-1 text-xs w-full">
                            <option value="">Semua Jenis</option>
                            <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Pemasukan (Income)</option>
                            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran (Expense)</option>
                            <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer Antar-Rekening</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterAccountSelect" class="block text-xs font-bold text-charcoal mb-1">Rekening</label>
                        <select
                            name="account_id"
                            id="filterAccountSelect"
                            class="field-select h-9 py-1 text-xs w-full"
                            data-searchable-picker="true"
                            data-picker-kind="account"
                            data-allow-empty="true"
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
                        <label for="filterCategorySelect" class="block text-xs font-bold text-charcoal mb-1">Kategori</label>
                        <select
                            name="category_id"
                            id="filterCategorySelect"
                            class="field-select h-9 py-1 text-xs w-full"
                            data-category-picker="true"
                            data-allow-empty="true"
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
                        <label class="block text-xs font-bold text-charcoal mb-1">Member Terkait</label>
                        <select name="member_id" class="field-select h-9 py-1 text-xs w-full">
                            <option value="">Semua Member</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}" {{ (string)request('member_id') === (string)$m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Status Transaksi</label>
                        <select name="status" class="field-select h-9 py-1 text-xs w-full">
                            <option value="posted" {{ request('status', 'posted') === 'posted' ? 'selected' : '' }}>Posted (Aktif - Default)</option>
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status (Termasuk Void)</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void (Dibatalkan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Urutan Tanggal</label>
                        <select name="sort" class="field-select h-9 py-1 text-xs w-full">
                            <option value="date_desc" {{ request('sort', 'date_desc') === 'date_desc' ? 'selected' : '' }}>Terbaru (Tanggal ↓)</option>
                            <option value="date_asc" {{ request('sort') === 'date_asc' ? 'selected' : '' }}>Terlama (Tanggal ↑)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Mulai Tanggal</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            id="filterStartDate"
                            value="{{ request('start_date') }}" 
                            class="field-input h-9 py-1 text-xs w-full" 
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Sampai Tanggal</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="filterEndDate"
                            value="{{ request('end_date') }}" 
                            class="field-input h-9 py-1 text-xs w-full" 
                        />
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn-arus-primary h-9 min-h-[36px] px-4 text-xs font-bold flex-1 cursor-pointer">
                            Terapkan Filter
                        </button>
                        <button type="button" onclick="clearAdvancedFilters()" class="btn-arus-secondary h-9 min-h-[36px] px-3 text-xs font-bold text-muted hover:text-charcoal cursor-pointer">
                            Bersihkan
                        </button>
                    </div>
                </div>

                <!-- Pilihan Tanggal Cepat -->
                <div class="flex items-center gap-2 mt-3 pt-2.5 border-t border-line/60 flex-wrap text-xs">
                    <span class="text-[10px] font-bold text-muted uppercase">Pilihan Tanggal Cepat:</span>
                    <button type="button" onclick="setDateFilterPreset('today')" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
                        Hari Ini
                    </button>
                    <span class="text-line">&bull;</span>
                    <button type="button" onclick="setDateFilterPreset('this_month')" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
                        Bulan Ini
                    </button>
                    <span class="text-line">&bull;</span>
                    <button type="button" onclick="setDateFilterPreset('last_30_days')" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
                        30 Hari Terakhir
                    </button>
                    <span class="text-line">&bull;</span>
                    <button type="button" onclick="setDateFilterPreset('upcoming')" class="text-[11px] text-muted hover:text-forest underline transition cursor-pointer">
                        Mendatang
                    </button>
                    <span class="text-line">&bull;</span>
                    <button type="button" onclick="setDateFilterPreset('clear')" class="text-[11px] text-muted hover:text-red underline transition cursor-pointer">
                        Hapus Tanggal
                    </button>
                </div>
            </div>
        </form>
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
                @foreach ($transactions as $trx)
                    @php
                        $isVoid = $trx->isVoid();
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
                    <div class="p-5 space-y-3 hover:bg-cream/20 transition-colors {{ $isVoid ? 'opacity-55' : '' }}">
                        <div class="flex items-start justify-between gap-3">
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
                            <th class="py-3 px-6">
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
                            <tr class="hover:bg-cream/30 transition-colors {{ $isVoid ? 'opacity-55' : '' }}">
                                <td class="py-4 px-6 whitespace-nowrap text-muted">
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
            return qs ? `${form.action}?${qs}` : form.action;
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

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('transactionSearchInput');
            const clearBtn = document.getElementById('clearTransactionSearchBtn');
            const catSelect = document.getElementById('filterCategorySelect');
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
                    btn.classList.add('border-forest', 'text-forest', 'bg-forest-light/40');
                    btn.setAttribute('aria-expanded', 'true');
                }
            } else {
                panel.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
                if (btn) {
                    btn.classList.remove('border-forest', 'text-forest', 'bg-forest-light/40');
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
            form.querySelectorAll('#advancedFilterPanel input[type="date"]').forEach(i => i.value = '');
            const futureInput = form.querySelector('input[name="include_future"]');
            if (futureInput) futureInput.remove();
            form.submit();
        }

        function setDateFilterPreset(preset) {
            const startInput = document.getElementById('filterStartDate');
            const endInput = document.getElementById('filterEndDate');
            if (!startInput || !endInput) return;
            
            const now = new Date();
            const toIso = (d) => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };
            
            if (preset === 'today') {
                const todayStr = toIso(now);
                startInput.value = todayStr;
                endInput.value = todayStr;
            } else if (preset === 'this_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                startInput.value = toIso(firstDay);
                endInput.value = toIso(lastDay);
            } else if (preset === 'last_30_days') {
                const past = new Date();
                past.setDate(past.getDate() - 30);
                startInput.value = toIso(past);
                endInput.value = toIso(now);
            } else if (preset === 'upcoming') {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                startInput.value = toIso(tomorrow);
                endInput.value = '';
            } else if (preset === 'clear') {
                startInput.value = '';
                endInput.value = '';
            }
        }
    </script>
</x-layouts.app>
