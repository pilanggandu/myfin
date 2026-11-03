<x-layouts.app title="Rekening & Wallet">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / REKENING</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Rekening & Wallet
            </h1>
            <p class="text-xs text-muted">Kelola akun bank, dompet digital, dan kas tunai pada ruang aktif ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="toggleBalanceVisibility()" 
                class="privacy-toggle-btn btn-arus-secondary inline-flex items-center gap-1.5 cursor-pointer text-xs font-bold" 
                title="Sembunyikan / Tampilkan Saldo"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Saldo</span>
            </button>
            <a href="{{ route('accounts.create') }}" class="btn-arus-primary w-full sm:w-auto">
                + Tambah Rekening
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-metric-card 
            label="TOTAL SALDO TERSEDIA" 
            :value="'Rp ' . number_format($totalBalance, 0, ',', '.')" 
            :dark="true"
            sub="Seluruh rekening aktif & arsip" 
        />
        <x-metric-card 
            label="REKENING BANK" 
            :value="$accounts->where('type', 'bank')->count() . ' Rekening'" 
            :sub="'Saldo: Rp ' . number_format($accounts->where('type', 'bank')->sum(fn($a) => $a->currentBalance()), 0, ',', '.')" 
        />
        <x-metric-card 
            label="E-WALLET & KAS TUNAI" 
            :value="$accounts->whereIn('type', ['e-wallet', 'cash'])->count() . ' Akun'" 
            :sub="'Saldo: Rp ' . number_format($accounts->whereIn('type', ['e-wallet', 'cash'])->sum(fn($a) => $a->currentBalance()), 0, ',', '.')" 
        />
    </div>

    <div class="panel-card p-0 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div>
                    <span class="text-sm font-bold text-forest block">Daftar Rekening Terdaftar ({{ $accounts->count() }})</span>
                    <span class="text-xs text-muted">Saldo dihitung dari saldo awal ditambah seluruh mutasi transaksi yang sah</span>
                </div>
                <button 
                    type="button" 
                    onclick="toggleBalanceVisibility()" 
                    class="privacy-toggle-btn w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs flex-shrink-0" 
                    title="Sembunyikan / Tampilkan Saldo"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            </div>
            <div class="text-xs text-muted">
                {{ $activeAccounts->count() }} Aktif &bull; {{ $accounts->whereNotNull('archived_at')->count() }} Diarsipkan
            </div>
        </div>

        @if ($accounts->isEmpty())
            <div class="p-10 text-center space-y-3">
                <span class="text-4xl block">💳</span>
                <div class="font-bold text-base text-charcoal">Belum Ada Rekening atau Wallet</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Tambahkan rekening bank, dompet digital, atau kas tunai pertama Anda untuk mulai mencatat arus kas.
                </p>
                <div class="pt-2">
                    <a href="{{ route('accounts.create') }}" class="btn-arus-primary">
                        + Tambah Rekening Sekarang
                    </a>
                </div>
            </div>
        @else
            <div class="md:hidden divide-y divide-line">
                @foreach ($accounts as $account)
                    @php
                        $balance = $account->currentBalance();
                        $typeIcon = match($account->type) {
                            'bank' => '🏦',
                            'e-wallet' => '📱',
                            'cash' => '💵',
                            default => '💳',
                        };
                        $typeLabel = match($account->type) {
                            'bank' => 'Bank',
                            'e-wallet' => 'E-Wallet',
                            'cash' => 'Kas Tunai',
                            default => $account->type,
                        };
                    @endphp
                    <div class="p-5 space-y-4 hover:bg-cream/20 transition-colors {{ $account->isArchived() ? 'opacity-70 bg-sidebar/30' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-forest-light text-forest flex items-center justify-center text-lg flex-shrink-0">
                                    {{ $typeIcon }}
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base text-charcoal">{{ $account->name }}</span>
                                        <x-badge variant="default">{{ $typeLabel }}</x-badge>
                                        @if ($account->isDefault())
                                            <span class="text-[10px] font-bold text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                Default
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted mt-0.5">
                                        Saldo awal: <span class="privacy-balance font-mono" data-raw-value="Rp {{ number_format($account->opening_balance, 0, ',', '.') }}">Rp {{ number_format($account->opening_balance, 0, ',', '.') }}</span> ({{ $account->opened_on->format('d M Y') }})
                                    </div>
                                </div>
                            </div>
                            <div>
                                @if ($account->isArchived())
                                    <x-badge variant="inactive">Diarsipkan</x-badge>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                                        Aktif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="p-3 rounded-lg bg-sidebar/50 border border-line flex items-center justify-between">
                            <span class="text-xs text-muted">Saldo Saat Ini:</span>
                            <span class="font-bold text-base font-mono {{ $balance < 0 ? 'text-red' : 'text-charcoal' }}">
                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($balance, 0, ',', '.') }}">
                                    Rp {{ number_format($balance, 0, ',', '.') }}
                                </span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2 pt-1 flex-wrap">
                            @if (! $account->isDefault() && ! $account->isArchived())
                                <form method="POST" action="{{ route('accounts.setDefault', $account) }}">
                                    @csrf
                                    <button type="submit" class="min-h-[44px] px-3 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 transition cursor-pointer inline-flex items-center justify-center">
                                        Set Default
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('accounts.show', $account) }}" class="flex-1 min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center">
                                Riwayat Mutasi
                            </a>
                            <a href="{{ route('accounts.edit', $account) }}" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center">
                                Ubah
                            </a>
                            @if ($account->isArchived())
                                <form method="POST" action="{{ route('accounts.unarchive', $account) }}">
                                    @csrf
                                    <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-forest hover:bg-forest-light transition cursor-pointer inline-flex items-center justify-center">
                                        Aktifkan
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('accounts.archive', $account) }}" onsubmit="return confirm('Arsipkan rekening ini? Rekening arsip tidak dapat digunakan untuk transaksi baru, namun saldo dan riwayat tetap tersimpan.')">
                                    @csrf
                                    <button type="submit" class="min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold text-muted hover:text-red border border-line bg-panel hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer inline-flex items-center justify-center">
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
                                $typeIcon = match($account->type) {
                                    'bank' => '🏦',
                                    'e-wallet' => '📱',
                                    'cash' => '💵',
                                    default => '💳',
                                };
                                $typeLabel = match($account->type) {
                                    'bank' => 'Bank',
                                    'e-wallet' => 'E-Wallet',
                                    'cash' => 'Kas Tunai',
                                    default => $account->type,
                                };
                            @endphp
                            <tr class="hover:bg-cream/30 transition-colors {{ $account->isArchived() ? 'opacity-65' : '' }}">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-lg bg-forest-light text-forest flex items-center justify-center text-sm flex-shrink-0">
                                            {{ $typeIcon }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-charcoal">{{ $account->name }}</span>
                                                @if ($account->isDefault())
                                                    <span class="text-[10px] font-bold text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full uppercase tracking-wider">
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
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                                            Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-right font-bold text-sm font-mono {{ $balance < 0 ? 'text-red' : 'text-charcoal' }}">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($balance, 0, ',', '.') }}">
                                        Rp {{ number_format($balance, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if ($account->isDefault())
                                            <span 
                                                class="w-8 h-8 rounded-lg border border-amber-500/30 bg-amber-500/10 text-amber-500 inline-flex items-center justify-center cursor-default"
                                                title="Rekening Default Transaksi"
                                                aria-label="Rekening Default Transaksi"
                                            >
                                                <svg class="w-4 h-4 fill-amber-500 text-amber-500" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                                </svg>
                                            </span>
                                        @elseif (! $account->isArchived())
                                            <form method="POST" action="{{ route('accounts.setDefault', $account) }}" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-amber-500 hover:border-amber-500/40 hover:bg-amber-500/5 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Jadikan Rekening Default Transaksi"
                                                    aria-label="Jadikan Rekening Default Transaksi"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <a 
                                            href="{{ route('accounts.show', $account) }}" 
                                            class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
                                            title="Lihat Riwayat Mutasi"
                                            aria-label="Lihat Riwayat Mutasi"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </a>
                                        <a 
                                            href="{{ route('accounts.edit', $account) }}" 
                                            class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
                                            title="Ubah Data Rekening"
                                            aria-label="Ubah Data Rekening"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        @if ($account->isArchived())
                                            <form method="POST" action="{{ route('accounts.unarchive', $account) }}" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Aktifkan Kembali Rekening"
                                                    aria-label="Aktifkan Kembali Rekening"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('accounts.archive', $account) }}" class="inline" onsubmit="return confirm('Arsipkan rekening ini?')">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Arsipkan Rekening"
                                                    aria-label="Arsipkan Rekening"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
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
        @endif
    </div>
</x-layouts.app>
