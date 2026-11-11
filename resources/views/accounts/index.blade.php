@php
    $viewMode = $viewMode ?? 'list';
    $hasExplicitView = $hasExplicitView ?? false;
@endphp

<x-layouts.app title="Rekening & Wallet">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / REKENING</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest dark:text-blue-300 font-normal tracking-tight mt-1 mb-0.5">
                Rekening &amp; Wallet
            </h1>
            <p class="text-xs text-muted">Kelola akun bank, dompet digital, dan kas tunai pada ruang aktif ini.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button
                type="button"
                onclick="toggleBalanceVisibility()"
                class="privacy-toggle-btn btn-arus-secondary min-h-[44px] sm:min-h-[38px] inline-flex items-center gap-1.5 cursor-pointer text-xs font-bold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
                title="Sembunyikan / Tampilkan Saldo"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Saldo</span>
            </button>
            <a href="{{ route('accounts.create') }}" class="btn-arus-primary min-h-[44px] sm:min-h-[38px] w-full sm:w-auto focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest">
                + Tambah Rekening
            </a>
        </div>
    </div>

    <div data-privacy-scope="accounts-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card
            label="TOTAL SALDO TERSEDIA"
            :value="'Rp ' . number_format($totalBalance, 0, ',', '.')"
            :dark="true"
            sub="Seluruh rekening aktif & arsip"
            :toggle="true"
        />
        <x-metric-card
            label="REKENING BANK"
            :value="$accounts->where('type', 'bank')->count() . ' Rekening'"
            :sub="'Saldo: Rp ' . number_format($accounts->where('type', 'bank')->sum(fn($a) => $a->currentBalance()), 0, ',', '.')"
            :toggle="true"
        />
        <x-metric-card
            label="E-WALLET & KAS TUNAI"
            :value="$accounts->whereIn('type', ['e-wallet', 'cash'])->count() . ' Akun'"
            :sub="'Saldo: Rp ' . number_format($accounts->whereIn('type', ['e-wallet', 'cash'])->sum(fn($a) => $a->currentBalance()), 0, ',', '.')"
            :toggle="true"
        />
        <x-metric-card
            label="INVESTASI & RDN"
            :value="$accounts->where('type', 'investment')->count() . ' Akun'"
            :sub="'Saldo: Rp ' . number_format($accounts->where('type', 'investment')->sum(fn($a) => $a->currentBalance()), 0, ',', '.')"
            :toggle="true"
        />
    </div>

    <div class="panel-card p-0 overflow-hidden" data-privacy-scope="accounts-table">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="min-w-0">
                    <span class="text-sm font-bold text-forest dark:text-blue-300 block">Daftar Rekening Terdaftar ({{ $accounts->count() }})</span>
                    <span class="text-xs text-muted block">Saldo dihitung dari saldo awal ditambah seluruh mutasi transaksi yang sah</span>
                </div>
                <button
                    type="button"
                    onclick="toggleCardPrivacy(this)"
                    class="privacy-card-toggle min-w-[44px] min-h-[44px] md:min-w-0 md:min-h-0 md:w-7 md:h-7 rounded border border-line bg-panel text-muted hover:text-forest dark:hover:text-blue-300 transition inline-flex items-center justify-center cursor-pointer text-xs flex-shrink-0 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
                    title="Sembunyikan / Tampilkan Saldo Daftar Rekening"
                    aria-label="Sembunyikan atau tampilkan saldo daftar rekening"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-between sm:justify-end gap-3">
                <div class="text-xs text-muted">
                    {{ $activeAccounts->count() }} Aktif &bull; {{ $accounts->whereNotNull('archived_at')->count() }} Diarsipkan
                </div>

                @if ($accounts->isNotEmpty())
                    <div
                        role="group"
                        aria-label="Pilih mode tampilan daftar rekening"
                        class="inline-flex items-center rounded-lg border border-line bg-field-bg p-1 gap-1"
                    >
                        <button
                            type="button"
                            id="accountsViewListBtn"
                            data-account-view-btn="list"
                            onclick="setAccountViewMode('list')"
                            aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                            class="min-h-[44px] md:min-h-[34px] px-3 py-1.5 rounded-md text-xs inline-flex items-center gap-1.5 transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest {{ $viewMode === 'list' ? 'bg-forest-btn text-white shadow-xs font-bold' : 'font-semibold text-slate-600 dark:text-slate-300 hover:text-charcoal hover:bg-panel/80' }}"
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
                            class="min-h-[44px] md:min-h-[34px] px-3 py-1.5 rounded-md text-xs inline-flex items-center gap-1.5 transition cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest {{ $viewMode === 'card' ? 'bg-forest-btn text-white shadow-xs font-bold' : 'font-semibold text-slate-600 dark:text-slate-300 hover:text-charcoal hover:bg-panel/80' }}"
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
                <div class="w-12 h-12 mx-auto rounded-xl bg-forest-light text-forest dark:text-blue-300 border border-forest/20 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                </div>
                <div class="font-bold text-base text-charcoal">Belum Ada Rekening atau Wallet</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Tambahkan rekening bank, dompet digital, atau kas tunai pertama Anda untuk mulai mencatat arus kas.
                </p>
                <div class="pt-2">
                    <a href="{{ route('accounts.create') }}" class="btn-arus-primary min-h-[44px] sm:min-h-[38px]">
                        + Tambah Rekening Sekarang
                    </a>
                </div>
            </div>
        @else
            <div id="accountsListView" class="{{ $viewMode === 'card' ? 'hidden' : '' }}">
                <div class="md:hidden divide-y divide-line">
                    @foreach ($accounts as $account)
                        @php
                            $balance = $account->currentBalance();
                            $typeLabel = $account->typeLabel();
                            $availableLimit = $account->availableLimit();
                        @endphp
                        <div class="p-5 space-y-4 hover:bg-cream/20 transition-colors {{ $account->isArchived() ? 'opacity-70 bg-sidebar/30' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <span class="w-10 h-10 rounded-xl bg-forest-light text-forest dark:text-blue-300 flex items-center justify-center flex-shrink-0">
                                        @include('accounts.partials._type-icon', ['type' => $account->type])
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-base text-charcoal break-words">{{ $account->name }}</span>
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
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest dark:text-blue-300 border border-forest/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-forest dark:bg-blue-400"></span>
                                            Aktif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-field-bg border border-line flex items-center justify-between gap-2">
                                @if ($account->isCreditCard() && $availableLimit !== null)
                                    <div>
                                        <span class="text-xs text-slate-600 dark:text-slate-300 block">Sisa Limit Tersedia:</span>
                                        <span class="text-[11px] text-slate-600 dark:text-slate-300">Terpakai: <span class="privacy-balance font-mono" data-raw-value="Rp {{ number_format($account->liabilityAmount(), 0, ',', '.') }}">Rp {{ number_format($account->liabilityAmount(), 0, ',', '.') }}</span></span>
                                    </div>
                                    <span class="font-bold text-base font-mono text-emerald-700 dark:text-emerald-400">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($availableLimit, 0, ',', '.') }}">
                                            Rp {{ number_format($availableLimit, 0, ',', '.') }}
                                        </span>
                                    </span>
                                @elseif ($account->isLoan())
                                    <div>
                                        <span class="text-xs text-slate-600 dark:text-slate-300 block">Sisa Kewajiban:</span>
                                        <span class="text-[11px] text-slate-600 dark:text-slate-300">Total Hutang Berjalan</span>
                                    </div>
                                    <span class="font-bold text-base font-mono text-charcoal">
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format(abs($balance), 0, ',', '.') }}">
                                            Rp {{ number_format(abs($balance), 0, ',', '.') }}
                                        </span>
                                    </span>
                                @else
                                    <span class="text-xs text-slate-600 dark:text-slate-300">Saldo Saat Ini:</span>
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
                                        <button type="submit" class="min-h-[44px] px-3 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-amber-700 dark:text-amber-300 hover:bg-amber-500/10 transition cursor-pointer inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest">
                                            Set Default
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('accounts.show', $account) }}" class="flex-1 min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest">
                                    Riwayat Mutasi
                                </a>
                                @if (! $account->isArchived())
                                    <button
                                        type="button"
                                        data-account-id="{{ $account->id }}"
                                        data-account-name="{{ $account->name }}"
                                        data-account-balance="{{ $balance }}"
                                        onclick="openAdjustModal(Number(this.dataset.accountId), this.dataset.accountName, Number(this.dataset.accountBalance))"
                                        class="min-h-[44px] px-3.5 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center gap-1.5 cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
                                        title="Penyesuaian Saldo"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" />
                                        </svg>
                                        <span>Sesuaikan</span>
                                    </button>
                                @endif
                                <a href="{{ route('accounts.edit', $account) }}" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest">
                                    Ubah
                                </a>
                                @if ($account->isArchived())
                                    <form method="POST" action="{{ route('accounts.unarchive', $account) }}">
                                        @csrf
                                        <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-forest dark:text-blue-300 hover:bg-forest-light transition cursor-pointer inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest">
                                            Aktifkan
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('accounts.archive', $account) }}" onsubmit="return confirm('Arsipkan rekening ini? Rekening arsip tidak dapat digunakan untuk transaksi baru, namun saldo dan riwayat tetap tersimpan.')">
                                        @csrf
                                        <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-red border border-line bg-panel hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest">
                                            Arsipkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                <th class="py-3 px-6">Nama Rekening</th>
                                <th class="py-3 px-4">Tipe</th>
                                <th class="py-3 px-4">Saldo Awal</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Saldo Saat Ini</th>
                                <th class="py-3 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($accounts as $account)
                                @php
                                    $balance = $account->currentBalance();
                                    $typeLabel = $account->typeLabel();
                                    $availableLimit = $account->availableLimit();
                                @endphp
                                <tr class="hover:bg-cream/30 transition-colors {{ $account->isArchived() ? 'opacity-65' : '' }}">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="w-8 h-8 rounded-lg bg-forest-light text-forest dark:text-blue-300 flex items-center justify-center flex-shrink-0">
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
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest dark:text-blue-300 border border-forest/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-forest dark:bg-blue-400"></span>
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
                                            <div class="font-bold text-sm {{ $balance < 0 ? 'text-red' : 'text-charcoal' }}">
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
                                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-amber-700 dark:hover:text-amber-300 hover:border-amber-500/40 hover:bg-amber-500/5 transition inline-flex items-center justify-center cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
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
                                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest dark:hover:text-blue-300 hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
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
                                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest dark:hover:text-blue-300 hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
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
                                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest dark:hover:text-blue-300 hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
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
                                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest dark:hover:text-blue-300 hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
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
                                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest"
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
                    </table>
                </div>
            </div>

            <div id="accountsCardView" class="p-4 sm:p-5 lg:p-6 bg-field-bg/50 dark:bg-sidebar/40 {{ $viewMode === 'card' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($accounts as $account)
                        @include('accounts.partials._account-card', ['account' => $account])
                    @endforeach
                </div>
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

                const activeClasses = ['bg-forest-btn', 'text-white', 'shadow-xs', 'font-bold'];
                const inactiveClasses = ['font-semibold', 'text-slate-600', 'dark:text-slate-300', 'hover:text-charcoal', 'hover:bg-panel/80'];

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

                if (persist) {
                    try {
                        localStorage.setItem(STORAGE_KEY, mode);
                    } catch (e) {}

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
