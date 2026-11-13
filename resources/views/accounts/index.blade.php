@php
    $viewMode = $viewMode ?? 'list';
    $hasExplicitView = $hasExplicitView ?? false;
    $sort = $sort ?? 'name_asc';
    $groupByType = $groupByType ?? false;
    $accountGroups = $accountGroups ?? collect();
    $accountBalances = $accountBalances ?? [];
    $accountBalanceFor = static fn ($acc): float => (float) ($accountBalances[$acc->id] ?? $acc->currentBalance());

    $displaySections = $groupByType
        ? $accountGroups
        : collect([[
            'type' => null,
            'label' => null,
            'accounts' => $accounts,
            'active_count' => $activeAccounts->count(),
            'archived_count' => $accounts->whereNotNull('archived_at')->count(),
            'total_amount' => null,
            'summary_label' => null,
        ]]);

    $groupToggleUrl = request()->fullUrlWithQuery([
        'group' => $groupByType ? null : 'type',
    ]);
    $nameSortUrl = request()->fullUrlWithQuery([
        'sort' => $sort === 'name_asc' ? 'name_desc' : 'name_asc',
    ]);
    $typeSortUrl = request()->fullUrlWithQuery([
        'sort' => $sort === 'type_asc' ? 'type_desc' : 'type_asc',
    ]);
    $balanceSortUrl = request()->fullUrlWithQuery([
        'sort' => $sort === 'balance_desc' ? 'name_asc' : 'balance_desc',
    ]);
@endphp

<x-layouts.app title="Rekening & Wallet">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / REKENING</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest dark:text-blue-400 font-normal tracking-tight mt-1 mb-0.5">
                Rekening &amp; Wallet
            </h1>
            <p class="text-xs text-muted">Kelola akun bank, dompet digital, dan kas tunai pada ruang aktif ini.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button
                type="button"
                onclick="toggleBalanceVisibility()"
                class="privacy-toggle-btn btn-arus-secondary min-h-[44px] inline-flex items-center gap-1.5 cursor-pointer text-xs font-bold focus:ring-2 focus:ring-forest"
                title="Sembunyikan / Tampilkan Saldo"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Saldo</span>
            </button>
            <a href="{{ route('accounts.create') }}" class="btn-arus-primary min-h-[44px] w-full sm:w-auto focus:ring-2 focus:ring-forest">
                + Tambah Rekening
            </a>
        </div>
    </div>

    <div data-privacy-scope="accounts-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card
            label="TOTAL SALDO TERSEDIA"
            :value="'Rp ' . number_format($totalBalance, 0, ',', '.')"
            :dark="$totalBalance >= 0 ? 'green' : 'red'"
            sub="Seluruh rekening aktif & arsip"
            :toggle="true"
        />
        <x-metric-card
            label="REKENING BANK"
            :value="$accounts->where('type', 'bank')->count() . ' Rekening'"
            :sub="'Saldo: Rp ' . number_format($accounts->where('type', 'bank')->sum($accountBalanceFor), 0, ',', '.')"
            :toggle="true"
        />
        <x-metric-card
            label="E-WALLET & KAS TUNAI"
            :value="$accounts->whereIn('type', ['e-wallet', 'cash'])->count() . ' Akun'"
            :sub="'Saldo: Rp ' . number_format($accounts->whereIn('type', ['e-wallet', 'cash'])->sum($accountBalanceFor), 0, ',', '.')"
            :toggle="true"
        />
        <x-metric-card
            label="INVESTASI & RDN"
            :value="$accounts->where('type', 'investment')->count() . ' Akun'"
            :sub="'Saldo: Rp ' . number_format($accounts->where('type', 'investment')->sum($accountBalanceFor), 0, ',', '.')"
            :toggle="true"
        />
    </div>

    <div class="panel-card p-0 overflow-hidden" data-privacy-scope="accounts-table">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="min-w-0">
                    <span class="text-sm font-bold text-forest dark:text-blue-400 block">Daftar Rekening Terdaftar ({{ $accounts->count() }})</span>
                    <span class="text-xs text-muted block">Saldo dihitung dari saldo awal ditambah seluruh mutasi transaksi yang sah</span>
                </div>
                <button
                    type="button"
                    onclick="toggleCardPrivacy(this)"
                    class="privacy-card-toggle w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs flex-shrink-0 focus:ring-2 focus:ring-forest"
                    title="Sembunyikan / Tampilkan Saldo Daftar Rekening"
                    aria-label="Sembunyikan atau tampilkan saldo daftar rekening"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-between lg:justify-end gap-2.5">
                <div class="text-xs text-muted mr-1">
                    {{ $activeAccounts->count() }} Aktif &bull; {{ $accounts->whereNotNull('archived_at')->count() }} Diarsipkan
                </div>

                @if ($accounts->isNotEmpty())
                    <form id="accountsSortForm" method="GET" action="{{ route('accounts.index') }}" class="flex flex-wrap items-center gap-2">
                        @if ($groupByType)
                            <input type="hidden" name="group" value="type">
                        @endif
                        <input
                            type="hidden"
                            id="accountsSortViewInput"
                            name="view"
                            value="{{ $hasExplicitView ? $viewMode : '' }}"
                            @disabled(! $hasExplicitView)
                        >

                        <label for="accountsSortSelect" class="sr-only">Urutkan daftar rekening</label>
                        <select
                            id="accountsSortSelect"
                            name="sort"
                            onchange="this.form.submit()"
                            class="field-input min-h-[44px] w-auto py-2 pl-3 pr-8 text-xs font-semibold text-charcoal bg-panel border-line rounded-lg cursor-pointer focus:ring-2 focus:ring-forest"
                            aria-label="Urutkan rekening berdasarkan"
                        >
                            <option value="name_asc" @selected($sort === 'name_asc')>Urut: Nama (A-Z)</option>
                            <option value="name_desc" @selected($sort === 'name_desc')>Urut: Nama (Z-A)</option>
                            <option value="type_asc" @selected($sort === 'type_asc')>Urut: Tipe Akun (Bank - Pinjaman)</option>
                            <option value="type_desc" @selected($sort === 'type_desc')>Urut: Tipe Akun (Terbalik)</option>
                            <option value="balance_desc" @selected($sort === 'balance_desc')>Urut: Saldo Tertinggi</option>
                        </select>
                        <noscript>
                            <button type="submit" class="btn-arus-secondary min-h-[44px] text-xs font-semibold">
                                Urutkan
                            </button>
                        </noscript>
                    </form>

                    <a
                        id="accountsGroupToggleBtn"
                        href="{{ $groupToggleUrl }}"
                        data-account-query-link
                        aria-pressed="{{ $groupByType ? 'true' : 'false' }}"
                        class="min-h-[44px] px-3.5 py-2 rounded-lg border text-xs inline-flex items-center gap-1.5 transition focus:ring-2 focus:ring-forest {{ $groupByType ? 'bg-forest-light border-forest text-forest dark:text-blue-300 font-bold' : 'bg-panel border-line text-charcoal hover:bg-cream font-semibold' }}"
                        title="Kelompokkan daftar rekening berdasarkan tipe akun"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h10.5m-10.5 5.25h16.5" />
                        </svg>
                        <span>Kelompokkan Tipe</span>
                    </a>

                    <div
                        role="group"
                        aria-label="Pilih mode tampilan daftar rekening"
                        class="inline-flex items-center rounded-lg border border-line bg-sidebar p-1 gap-1"
                    >
                        <button
                            type="button"
                            id="accountsViewListBtn"
                            data-account-view-btn="list"
                            onclick="setAccountViewMode('list')"
                            aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                            class="min-h-[44px] px-3.5 py-2 rounded-md text-xs inline-flex items-center gap-1.5 transition cursor-pointer focus:ring-2 focus:ring-forest {{ $viewMode === 'list' ? 'bg-forest text-white shadow-xs font-bold' : 'font-semibold text-charcoal/80 hover:text-charcoal hover:bg-panel' }}"
                            title="Tampilan Mode List"
                        >
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <span>List</span>
                        </button>
                        <button
                            type="button"
                            id="accountsViewCardBtn"
                            data-account-view-btn="card"
                            onclick="setAccountViewMode('card')"
                            aria-pressed="{{ $viewMode === 'card' ? 'true' : 'false' }}"
                            class="min-h-[44px] px-3.5 py-2 rounded-md text-xs inline-flex items-center gap-1.5 transition cursor-pointer focus:ring-2 focus:ring-forest {{ $viewMode === 'card' ? 'bg-forest text-white shadow-xs font-bold' : 'font-semibold text-charcoal/80 hover:text-charcoal hover:bg-panel' }}"
                            title="Tampilan Mode Card"
                        >
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <span>Card</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        @if ($accounts->isEmpty())
            <div class="p-10 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-xl bg-forest-light text-forest dark:text-blue-400 border border-forest/20 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                </div>
                <div class="font-bold text-base text-charcoal">Belum Ada Rekening atau Wallet</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Tambahkan rekening bank, dompet digital, atau kas tunai pertama Anda untuk mulai mencatat arus kas.
                </p>
                <div class="pt-2">
                    <a href="{{ route('accounts.create') }}" class="btn-arus-primary min-h-[44px]">
                        + Tambah Rekening Sekarang
                    </a>
                </div>
            </div>
        @else
            <div id="accountsListView" class="{{ $viewMode === 'card' ? 'hidden' : '' }}">
                <div class="md:hidden divide-y divide-line">
                    @foreach ($displaySections as $section)
                        @if ($groupByType && $section['label'])
                            <div data-account-group-mobile="{{ $section['type'] }}" class="px-5 py-3 bg-cream/65 dark:bg-sidebar flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-forest-light text-forest dark:text-blue-300 border border-forest/20 flex items-center justify-center flex-shrink-0">
                                        @include('accounts.partials._type-icon', ['type' => $section['type']])
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-charcoal truncate">{{ $section['label'] }}</div>
                                        <div class="text-[11px] text-muted">
                                            {{ $section['accounts']->count() }} Akun
                                            @if ($section['archived_count'] > 0)
                                                ({{ $section['archived_count'] }} arsip)
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-muted">{{ $section['summary_label'] }}</span>
                                    <span class="font-mono text-xs font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($section['total_amount'], 0, ',', '.') }}">
                                        Rp {{ number_format($section['total_amount'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endif
                        @foreach ($section['accounts'] as $account)
                            @php
                                $balance = $accountBalanceFor($account);
                                $typeLabel = $account->typeLabel();
                                $availableLimit = $account->availableLimit();
                            @endphp
                            <div class="p-5 space-y-4 hover:bg-cream/20 transition-colors {{ $account->isArchived() ? 'opacity-70 bg-sidebar/30' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <span class="w-10 h-10 rounded-xl bg-forest-light text-forest dark:text-blue-400 flex items-center justify-center flex-shrink-0">
                                        @include('accounts.partials._type-icon', ['type' => $account->type])
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-base text-charcoal line-clamp-2">{{ $account->name }}</span>
                                            <x-badge variant="default">{{ $typeLabel }}</x-badge>
                                            @if ($account->isCreditCard() && $availableLimit !== null)
                                                <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    Sisa Limit
                                                </span>
                                            @elseif ($account->isLoan())
                                                <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    Kewajiban
                                                </span>
                                            @endif
                                            @if ($account->isDefault())
                                                <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    Default
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-muted mt-0.5">
                                            Saldo awal: <span class="privacy-balance font-mono" data-raw-value="Rp {{ number_format($account->opening_balance, 0, ',', '.') }}">Rp {{ number_format($account->opening_balance, 0, ',', '.') }}</span> ({{ $account->opened_on->format('d M Y') }})
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    @if ($account->isArchived())
                                        <x-badge variant="inactive">Diarsipkan</x-badge>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest dark:text-blue-400 border border-forest/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                            Aktif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-sidebar/50 border border-line flex items-center justify-between gap-2">
                                @if ($account->isCreditCard() && $availableLimit !== null)
                                    <div>
                                        <span class="text-xs text-charcoal/80 block">Sisa Limit Tersedia:</span>
                                        <span class="text-[11px] text-charcoal/80">Terpakai: <span class="privacy-balance font-mono" data-raw-value="Rp {{ number_format($account->liabilityAmount(), 0, ',', '.') }}">Rp {{ number_format($account->liabilityAmount(), 0, ',', '.') }}</span></span>
                                    </div>
                                    <span class="font-bold text-base font-mono text-emerald-700 dark:text-emerald-400">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($availableLimit, 0, ',', '.') }}">
                                            Rp {{ number_format($availableLimit, 0, ',', '.') }}
                                        </span>
                                    </span>
                                @elseif ($account->isLoan())
                                    <div>
                                        <span class="text-xs text-charcoal/80 block">Sisa Kewajiban:</span>
                                        <span class="text-[11px] text-charcoal/80">Total Hutang Berjalan</span>
                                    </div>
                                    <span class="font-bold text-base font-mono text-charcoal">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format(abs($balance), 0, ',', '.') }}">
                                            Rp {{ number_format(abs($balance), 0, ',', '.') }}
                                        </span>
                                    </span>
                                @else
                                    <span class="text-xs text-charcoal/80">Saldo Saat Ini:</span>
                                    <span class="font-bold text-base font-mono {{ $balance < 0 ? 'text-red' : 'text-charcoal' }}">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($balance, 0, ',', '.') }}">
                                            Rp {{ number_format($balance, 0, ',', '.') }}
                                        </span>
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 pt-1 flex-wrap">
                                @if (! $account->isDefault() && ! $account->isArchived())
                                    <form method="POST" action="{{ route('accounts.setDefault', $account) }}">
                                        @csrf
                                        <button type="submit" class="min-h-[44px] px-3 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-amber-700 dark:text-amber-300 hover:bg-amber-500/10 transition cursor-pointer inline-flex items-center justify-center focus:ring-2 focus:ring-forest">
                                            Set Default
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('accounts.show', $account) }}" class="flex-1 min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center focus:ring-2 focus:ring-forest">
                                    Riwayat Mutasi
                                </a>
                                @if (! $account->isArchived())
                                    <button
                                        type="button"
                                        data-account-id="{{ $account->id }}"
                                        data-account-name="{{ $account->name }}"
                                        data-account-balance="{{ $balance }}"
                                        onclick="openAdjustModal(Number(this.dataset.accountId), this.dataset.accountName, Number(this.dataset.accountBalance))"
                                        class="min-h-[44px] px-3.5 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center gap-1.5 cursor-pointer focus:ring-2 focus:ring-forest"
                                        title="Penyesuaian Saldo"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" />
                                        </svg>
                                        <span>Sesuaikan</span>
                                    </button>
                                @endif
                                <a href="{{ route('accounts.edit', $account) }}" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center focus:ring-2 focus:ring-forest">
                                    Ubah
                                </a>
                                @if ($account->isArchived())
                                    <form method="POST" action="{{ route('accounts.unarchive', $account) }}">
                                        @csrf
                                        <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-forest dark:text-blue-400 hover:bg-forest-light transition cursor-pointer inline-flex items-center justify-center focus:ring-2 focus:ring-forest">
                                            Aktifkan
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('accounts.archive', $account) }}" onsubmit="return confirm('Arsipkan rekening ini? Rekening arsip tidak dapat digunakan untuk transaksi baru, namun saldo dan riwayat tetap tersimpan.')">
                                        @csrf
                                        <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold text-charcoal/80 hover:text-red border border-line bg-panel hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer inline-flex items-center justify-center focus:ring-2 focus:ring-forest">
                                            Arsipkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                <th class="py-3 px-6">
                                    <a href="{{ $nameSortUrl }}" data-account-query-link class="inline-flex items-center gap-1.5 hover:text-forest transition cursor-pointer select-none focus:ring-2 focus:ring-forest rounded">
                                        <span>Nama Rekening</span>
                                        @if (in_array($sort, ['name_asc', 'name_desc'], true))
                                            <span class="text-forest dark:text-blue-400 font-bold">{{ $sort === 'name_asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="py-3 px-4">
                                    <a href="{{ $typeSortUrl }}" data-account-query-link class="inline-flex items-center gap-1.5 hover:text-forest transition cursor-pointer select-none focus:ring-2 focus:ring-forest rounded" title="Urutkan berdasarkan tipe akun">
                                        <span>Tipe</span>
                                        @if (in_array($sort, ['type_asc', 'type_desc'], true))
                                            <span class="text-forest dark:text-blue-400 font-bold">{{ $sort === 'type_asc' ? '↑' : '↓' }}</span>
                                        @else
                                            <span class="text-muted/60">↕</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="py-3 px-4">Saldo Awal</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">
                                    <a href="{{ $balanceSortUrl }}" data-account-query-link class="inline-flex items-center justify-end gap-1.5 hover:text-forest transition cursor-pointer select-none focus:ring-2 focus:ring-forest rounded" title="Urutkan berdasarkan saldo tertinggi">
                                        <span>Saldo Saat Ini</span>
                                        @if ($sort === 'balance_desc')
                                            <span class="text-forest dark:text-blue-400 font-bold">↓</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="py-3 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        @foreach ($displaySections as $section)
                            <tbody class="divide-y divide-line">
                                @if ($groupByType && $section['label'])
                                    <tr data-account-group="{{ $section['type'] }}" class="bg-cream/65 dark:bg-sidebar/90 border-y border-line">
                                        <td colspan="6" class="py-2.5 px-6">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="w-7 h-7 rounded-lg bg-forest-light text-forest dark:text-blue-300 border border-forest/20 inline-flex items-center justify-center flex-shrink-0">
                                                        @include('accounts.partials._type-icon', ['type' => $section['type']])
                                                    </span>
                                                    <span class="text-xs font-bold text-charcoal">{{ $section['label'] }}</span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-panel border border-line text-muted">
                                                        {{ $section['accounts']->count() }} Akun
                                                    </span>
                                                </div>
                                                <div class="text-xs font-semibold text-muted">
                                                    <span>{{ $section['summary_label'] }}:</span>
                                                    <span class="font-mono font-bold text-charcoal ml-1 privacy-balance" data-raw-value="Rp {{ number_format($section['total_amount'], 0, ',', '.') }}">
                                                        Rp {{ number_format($section['total_amount'], 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($section['accounts'] as $account)
                                    @php
                                        $balance = $accountBalanceFor($account);
                                        $typeLabel = $account->typeLabel();
                                        $availableLimit = $account->availableLimit();
                                    @endphp
                                    <tr data-account-row="{{ $account->id }}" class="hover:bg-cream/30 transition-colors {{ $account->isArchived() ? 'opacity-65' : '' }}">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="w-8 h-8 rounded-lg bg-forest-light text-forest dark:text-blue-400 flex items-center justify-center flex-shrink-0">
                                                @include('accounts.partials._type-icon', ['type' => $account->type])
                                            </span>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-bold text-sm text-charcoal">{{ $account->name }}</span>
                                                    @if ($account->isCreditCard() && $availableLimit !== null)
                                                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                            Sisa Limit
                                                        </span>
                                                    @elseif ($account->isLoan())
                                                        <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                            Kewajiban
                                                        </span>
                                                    @endif
                                                    @if ($account->isDefault())
                                                        <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-muted">Buka: {{ $account->opened_on->format('d M Y') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <x-badge variant="default">{{ $typeLabel }}</x-badge>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap text-muted font-mono">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($account->opening_balance, 0, ',', '.') }}">
                                            Rp {{ number_format($account->opening_balance, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        @if ($account->isArchived())
                                            <x-badge variant="inactive">Diarsipkan</x-badge>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest dark:text-blue-400 border border-forest/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                                Aktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap text-right font-mono">
                                        @if ($account->isCreditCard() && $availableLimit !== null)
                                            <div class="font-bold text-sm text-emerald-700 dark:text-emerald-400">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($availableLimit, 0, ',', '.') }}">
                                                    Rp {{ number_format($availableLimit, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-muted font-sans">
                                                Terpakai: <span class="privacy-balance font-mono" data-raw-value="Rp {{ number_format($account->liabilityAmount(), 0, ',', '.') }}">Rp {{ number_format($account->liabilityAmount(), 0, ',', '.') }}</span>
                                            </div>
                                        @elseif ($account->isLoan())
                                            <div class="font-bold text-sm text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format(abs($balance), 0, ',', '.') }}">
                                                    Rp {{ number_format(abs($balance), 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-muted font-sans">
                                                Sisa Kewajiban
                                            </div>
                                        @else
                                            <div class="font-bold text-sm {{ $balance < 0 ? 'text-red dark:text-rose-400' : ($balance > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-charcoal') }}">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($balance, 0, ',', '.') }}">
                                                    Rp {{ number_format($balance, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            @if ($account->isDefault())
                                                <span
                                                    class="w-8 h-8 rounded-lg border border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300 inline-flex items-center justify-center cursor-default"
                                                    title="Rekening Default Transaksi"
                                                    aria-label="Rekening Default Transaksi"
                                                >
                                                    <svg class="w-4 h-4 fill-amber-500 text-amber-500" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                                    </svg>
                                                </span>
                                            @elseif (! $account->isArchived())
                                                <form method="POST" action="{{ route('accounts.setDefault', $account) }}" class="inline">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-amber-500 hover:border-amber-500/40 hover:bg-amber-500/5 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                                                        title="Jadikan Rekening Default Transaksi"
                                                        aria-label="Jadikan Rekening Default Transaksi"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            <a
                                                href="{{ route('accounts.show', $account) }}"
                                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center focus:ring-2 focus:ring-forest"
                                                title="Lihat Riwayat Mutasi"
                                                aria-label="Lihat Riwayat Mutasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            </a>
                                            @if (! $account->isArchived())
                                                <button
                                                    type="button"
                                                    data-account-id="{{ $account->id }}"
                                                    data-account-name="{{ $account->name }}"
                                                    data-account-balance="{{ $balance }}"
                                                    onclick="openAdjustModal(Number(this.dataset.accountId), this.dataset.accountName, Number(this.dataset.accountBalance))"
                                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                                                    title="Penyesuaian Saldo (Opname / Rekonsiliasi)"
                                                    aria-label="Penyesuaian Saldo"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <a
                                                href="{{ route('accounts.edit', $account) }}"
                                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center focus:ring-2 focus:ring-forest"
                                                title="Ubah Data Rekening"
                                                aria-label="Ubah Data Rekening"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                </svg>
                                            </a>
                                            @if ($account->isArchived())
                                                <form method="POST" action="{{ route('accounts.unarchive', $account) }}" class="inline">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                                                        title="Aktifkan Kembali Rekening"
                                                        aria-label="Aktifkan Kembali Rekening"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('accounts.archive', $account) }}" class="inline" onsubmit="return confirm('Arsipkan rekening ini?')">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                                                        title="Arsipkan Rekening"
                                                        aria-label="Arsipkan Rekening"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </div>

            <div id="accountsCardView" class="p-4 sm:p-5 bg-cream/20 dark:bg-sidebar/40 {{ $viewMode === 'card' ? '' : 'hidden' }}">
                @if ($groupByType)
                    <div class="space-y-6">
                        @foreach ($accountGroups as $section)
                            <section data-account-card-group="{{ $section['type'] }}" class="space-y-3">
                                <div class="px-4 py-3 rounded-xl border border-line bg-panel flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-8 h-8 rounded-lg bg-forest-light text-forest dark:text-blue-300 border border-forest/20 inline-flex items-center justify-center flex-shrink-0">
                                            @include('accounts.partials._type-icon', ['type' => $section['type']])
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-charcoal">{{ $section['label'] }}</div>
                                            <div class="text-[11px] text-muted">
                                                {{ $section['active_count'] }} Aktif
                                                @if ($section['archived_count'] > 0)
                                                    &bull; {{ $section['archived_count'] }} Diarsipkan
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[10px] font-bold uppercase tracking-wider text-muted">{{ $section['summary_label'] }}</span>
                                        <span class="font-mono text-sm font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($section['total_amount'], 0, ',', '.') }}">
                                            Rp {{ number_format($section['total_amount'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($section['accounts'] as $account)
                                        @include('accounts.partials._account-card', [
                                            'account' => $account,
                                            'precomputedBalance' => $accountBalanceFor($account),
                                        ])
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($accounts as $account)
                            @include('accounts.partials._account-card', [
                                'account' => $account,
                                'precomputedBalance' => $accountBalanceFor($account),
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    <x-adjust-balance-modal />

    <script>
        (function() {
            const STORAGE_KEY = 'arus_accounts_view_mode';
            const hasExplicitView = @json($hasExplicitView);
            const initialView = @json($viewMode);

            function applyAccountViewMode(mode, persist) {
                if (mode !== 'list' && mode !== 'card') {
                    return;
                }

                const listView = document.getElementById('accountsListView');
                const cardView = document.getElementById('accountsCardView');
                const listBtn = document.getElementById('accountsViewListBtn');
                const cardBtn = document.getElementById('accountsViewCardBtn');

                if (listView && cardView) {
                    listView.classList.toggle('hidden', mode !== 'list');
                    cardView.classList.toggle('hidden', mode !== 'card');
                }

                const activeClasses = ['bg-forest', 'text-white', 'shadow-xs', 'font-bold'];
                const inactiveClasses = ['font-semibold', 'text-charcoal/80', 'hover:text-charcoal', 'hover:bg-panel'];

                [
                    { btn: listBtn, active: mode === 'list' },
                    { btn: cardBtn, active: mode === 'card' },
                ].forEach(function(item) {
                    if (!item.btn) {
                        return;
                    }
                    item.btn.setAttribute('aria-pressed', item.active ? 'true' : 'false');
                    if (item.active) {
                        item.btn.classList.remove(...inactiveClasses);
                        item.btn.classList.add(...activeClasses);
                    } else {
                        item.btn.classList.remove(...activeClasses);
                        item.btn.classList.add(...inactiveClasses);
                    }
                });

                const sortViewInput = document.getElementById('accountsSortViewInput');
                if (sortViewInput && (persist || hasExplicitView)) {
                    sortViewInput.value = mode;
                    sortViewInput.disabled = false;
                }

                if (persist) {
                    try {
                        localStorage.setItem(STORAGE_KEY, mode);
                    } catch (e) {}

                    document.querySelectorAll('[data-account-query-link]').forEach(function(link) {
                        try {
                            const nextUrl = new URL(link.href, window.location.origin);
                            nextUrl.searchParams.set('view', mode);
                            link.href = nextUrl.toString();
                        } catch (e) {}
                    });

                    try {
                        const currentUrl = new URL(window.location.href);
                        if (currentUrl.searchParams.has('view')) {
                            currentUrl.searchParams.set('view', mode);
                            window.history.replaceState(null, '', currentUrl.toString());
                        }
                    } catch (e) {}
                }
            }

            window.setAccountViewMode = function(mode) {
                applyAccountViewMode(mode, true);
            };

            if (hasExplicitView) {
                try {
                    localStorage.setItem(STORAGE_KEY, initialView);
                } catch (e) {}
                applyAccountViewMode(initialView, false);
            } else {
                let savedMode = null;
                try {
                    savedMode = localStorage.getItem(STORAGE_KEY);
                } catch (e) {}

                if (savedMode === 'list' || savedMode === 'card') {
                    applyAccountViewMode(savedMode, false);
                }
            }
        })();
    </script>
</x-layouts.app>
