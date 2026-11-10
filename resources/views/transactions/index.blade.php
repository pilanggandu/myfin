<x-layouts.app title="Daftar Transaksi">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / KEUANGAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Catatan Transaksi
            </h1>
            <p class="text-xs text-muted">Seluruh pencatatan arus kas masuk, pengeluaran, dan mutasi transfer antar-rekening.</p>
        </div>
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'transactions-table\']')" 
                class="privacy-card-toggle inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal Transaksi"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Transaksi</span>
            </button>
            <a href="{{ route('transactions.create') }}" class="btn-arus-primary w-full sm:w-auto">
                + Catat Transaksi Baru
            </a>
        </div>
    </div>

    <!-- Filter & Search Panel -->
    <div class="panel-card p-4 sm:p-5 mb-6">
        @php
            $advancedKeys = ['type', 'account_id', 'category_id', 'member_id', 'start_date', 'end_date'];
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
        @endphp

        <form method="GET" action="{{ route('transactions.index') }}" class="space-y-3" id="transactionFilterForm">
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
                        value="{{ request('search') }}" 
                        placeholder="Cari transaksi berdasarkan keterangan..." 
                        class="field-input h-10 pl-9 pr-3 text-xs w-full" 
                        style="padding-left: 36px !important;"
                    />
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <button 
                        type="button" 
                        id="toggleAdvancedBtn"
                        onclick="toggleAdvancedFilters()"
                        class="btn-arus-secondary h-10 min-h-[40px] px-3.5 text-xs font-bold inline-flex items-center gap-2 cursor-pointer transition select-none flex-1 sm:flex-initial justify-center {{ $isAdvancedOpen ? 'border-forest text-forest bg-forest-light/40' : '' }}"
                        aria-expanded="{{ $isAdvancedOpen ? 'true' : 'false' }}"
                    >
                        <svg class="w-4 h-4 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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

                    <button type="submit" class="btn-arus-primary h-10 min-h-[40px] px-4 text-xs font-bold cursor-pointer">
                        Terapkan
                    </button>

                    @if (request()->hasAny(['search', 'type', 'account_id', 'category_id', 'member_id', 'start_date', 'end_date']) || (request()->has('status') && request('status') !== 'posted') || (request()->has('sort') && request('sort') !== 'date_desc'))
                        <a href="{{ route('transactions.index') }}" class="btn-arus-secondary h-10 min-h-[40px] px-3 text-xs font-bold inline-flex items-center justify-center text-muted hover:text-red transition cursor-pointer" title="Reset Semua Filter">
                            Reset
                        </a>
                    @endif
                </div>
            </div>

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
                        <label class="block text-xs font-bold text-charcoal mb-1">Rekening</label>
                        <select name="account_id" class="field-select h-9 py-1 text-xs w-full">
                            <option value="">Semua Rekening</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ (string)request('account_id') === (string)$acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Kategori</label>
                        <select name="category_id" class="field-select h-9 py-1 text-xs w-full">
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
                    <button type="button" onclick="setDateFilterPreset('clear')" class="text-[11px] text-muted hover:text-red underline transition cursor-pointer">
                        Hapus Tanggal
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table & List Panel -->
    <div class="panel-card p-0 overflow-hidden" data-privacy-scope="transactions-table">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-forest block">Daftar Transaksi ({{ $transactions->total() }})</span>
                <button 
                    type="button" 
                    onclick="toggleCardPrivacy(this)" 
                    class="privacy-card-toggle p-1 text-muted hover:text-charcoal transition-colors rounded cursor-pointer" 
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
                <span class="text-4xl block">📝</span>
                <div class="font-bold text-base text-charcoal">Tidak Ada Transaksi Ditemukan</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Belum ada data transaksi yang sesuai dengan kriteria filter, atau Anda belum mencatat mutasi keuangan.
                </p>
                <div class="pt-2">
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
                                        + fee Rp {{ number_format($trx->fee_amount, 0, ',', '.') }}
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
                        <div class="p-2.5 rounded-lg bg-sidebar/50 border border-line text-xs flex items-center justify-between">
                            <span class="text-muted">Rekening:</span>
                            @if ($trx->isTransfer())
                                <span class="font-bold text-charcoal">
                                    {{ $trx->sourceAccount()?->name }} &rarr; {{ $trx->destinationAccount()?->name }}
                                </span>
                            @elseif ($trx->isExpense())
                                <span class="font-bold text-charcoal">{{ $trx->sourceAccount()?->name }}</span>
                            @else
                                <span class="font-bold text-charcoal">{{ $trx->destinationAccount()?->name }}</span>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-2 border-t border-line/60">
                            <a href="{{ route('transactions.show', $trx) }}" class="flex-1 min-h-[40px] px-3 py-2 rounded-lg text-xs font-semibold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center gap-1.5" title="Lihat Detail Transaksi">
                                <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <span>Detail</span>
                            </a>
                            @if (! $isVoid)
                                <a href="{{ route('transactions.edit', $trx) }}" class="min-h-[40px] px-3.5 py-2 rounded-lg text-xs font-semibold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center gap-1.5" title="Koreksi Transaksi">
                                    <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    <span class="hidden sm:inline">Koreksi</span>
                                </a>
                                <form method="POST" action="{{ route('transactions.void', $trx) }}" onsubmit="return confirm('Batalkan transaksi ini (void)? Catatan entri akan dinonaktifkan dari saldo kas.')">
                                    @csrf
                                    <button type="submit" class="min-h-[40px] px-3.5 py-2 rounded-lg text-xs font-semibold text-red hover:bg-red/10 border border-line hover:border-red/40 bg-panel transition cursor-pointer inline-flex items-center justify-center gap-1.5" title="Batalkan Transaksi (Void)">
                                        <svg class="w-4 h-4 text-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span class="hidden sm:inline">Void</span>
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
                                            + fee Rp {{ number_format($trx->fee_amount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a 
                                            href="{{ route('transactions.show', $trx) }}" 
                                            class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
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
                                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
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
                                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
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

    <script>
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
            } else if (preset === 'clear') {
                startInput.value = '';
                endInput.value = '';
            }
        }
    </script>
</x-layouts.app>
