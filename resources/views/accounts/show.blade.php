<x-layouts.app title="Mutasi Rekening: {{ $account->name }}">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <a href="{{ route('accounts.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1">
                &larr; Kembali ke daftar rekening
            </a>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / MUTASI REKENING</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                {{ $account->name }}
            </h1>
            <p class="text-xs text-muted">
                Riwayat seluruh pencatatan mutasi transaksi yang terhubung ke rekening ini.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'account-detail-metrics\']')" 
                class="privacy-card-toggle inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Saldo Rekening Ini"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Saldo Rekening</span>
            </button>
            <a href="{{ route('transactions.create') }}" class="btn-arus-primary">
                + Catat Transaksi
            </a>
            @if (! $account->isArchived())
                <button 
                    type="button" 
                    onclick="openAdjustModal({{ $account->id }}, '{{ addslashes($account->name) }}', {{ $account->currentBalance() }})" 
                    class="btn-arus-secondary inline-flex items-center gap-1.5 cursor-pointer"
                    title="Sesuaikan saldo rekening ini agar sesuai dengan saldo fisik riil"
                >
                    <span>⚖️</span>
                    <span>Penyesuaian Saldo</span>
                </button>
            @endif
            <a href="{{ route('accounts.edit', $account) }}" class="btn-arus-secondary">
                Ubah
            </a>
        </div>
    </div>

    @php
        $typeLabel = match($account->type) {
            'bank' => 'Rekening Bank',
            'e-wallet' => 'Dompet Digital',
            'cash' => 'Kas Tunai',
            'investment' => 'Investasi / RDN',
            default => strtoupper($account->type),
        };
    @endphp

    <div data-privacy-scope="account-detail-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-metric-card 
            label="SALDO SAAT INI" 
            :value="'Rp ' . number_format($account->currentBalance(), 0, ',', '.')" 
            :dark="true" 
            :sub="'Tipe: ' . $typeLabel" 
        />
        <x-metric-card 
            label="SALDO AWAL" 
            :value="'Rp ' . number_format($account->opening_balance, 0, ',', '.')" 
            :sub="'Buka: ' . $account->opened_on->format('d M Y')" 
        />
        <x-metric-card 
            label="TOTAL MUTASI" 
            :value="$entries->total() . ' Entri'" 
            :sub="$account->isArchived() ? 'Status: Diarsipkan' : 'Status: Aktif'" 
        />
    </div>

    <div class="panel-card p-0 overflow-hidden" data-privacy-scope="account-mutations-table">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-forest">Histori Mutasi Buku</span>
                <button 
                    type="button" 
                    onclick="toggleCardPrivacy(this)" 
                    class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs" 
                    title="Sembunyikan / Tampilkan Mutasi Rekening"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            </div>
            <span class="text-xs text-muted">{{ $entries->total() }} entri ditemukan</span>
        </div>

        @if ($entries->isEmpty())
            <div class="p-8 text-center text-xs text-muted">
                Belum ada catatan mutasi transaksi pada rekening ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                            <th class="py-3 px-6">Tanggal</th>
                            <th class="py-3 px-4">Keterangan</th>
                            <th class="py-3 px-4">Jenis</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-6 text-right">Mutasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($entries as $entry)
                            @php
                                $trx = $entry->transaction;
                                $isPositive = $entry->amount_signed > 0;
                            @endphp
                            <tr class="hover:bg-cream/30 transition-colors {{ $trx->isVoid() ? 'opacity-50 line-through' : '' }}">
                                <td class="py-4 px-6 whitespace-nowrap text-muted">
                                    {{ $trx->transaction_date->format('d M Y') }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-sm text-charcoal">
                                        <a href="{{ route('transactions.show', $trx) }}" class="hover:text-forest hover:underline">
                                            {{ $trx->description }}
                                        </a>
                                    </div>
                                    @if ($trx->category)
                                        <div class="text-xs text-muted mt-0.5">
                                            Kategori: {{ $trx->category->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <x-badge variant="default">
                                        {{ strtoupper($trx->type) }}
                                    </x-badge>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($trx->isVoid())
                                        <x-badge variant="inactive">VOID</x-badge>
                                    @else
                                        <span class="text-emerald-700 dark:text-emerald-400 font-bold text-[11px]">POSTED</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-right font-bold text-sm font-mono {{ $isPositive ? 'text-forest' : 'text-red' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}">
                                        {{ $isPositive ? '+' : '' }} Rp {{ number_format($entry->amount_signed, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="p-4 border-t border-line">
                    {{ $entries->links() }}
                </div>
            @endif
        @endif
    </div>

    @if ($account->openingBalanceLogs->isNotEmpty())
        <div class="mt-6 panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-sidebar border border-line flex items-center justify-center text-xs select-none">
                        📜
                    </span>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-charcoal">
                        Jejak Audit Perubahan Saldo Awal
                    </h3>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-cream text-muted border border-line">
                    {{ $account->openingBalanceLogs->count() }} Perubahan
                </span>
            </div>

            <div class="divide-y divide-line/60 text-xs">
                @foreach ($account->openingBalanceLogs as $log)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <div class="font-bold text-charcoal flex items-center gap-2">
                                <span class="font-mono text-muted line-through">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($log->old_opening_balance, 0, ',', '.') }}">Rp {{ number_format($log->old_opening_balance, 0, ',', '.') }}</span>
                                </span>
                                <span class="text-muted">&rarr;</span>
                                <span class="font-mono text-forest">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($log->new_opening_balance, 0, ',', '.') }}">Rp {{ number_format($log->new_opening_balance, 0, ',', '.') }}</span>
                                </span>
                            </div>
                            @if ($log->old_opened_on->format('Y-m-d') !== $log->new_opened_on->format('Y-m-d'))
                                <div class="text-[11px] text-muted mt-0.5">
                                    Tanggal Efektif: {{ $log->old_opened_on->format('d/m/Y') }} &rarr; {{ $log->new_opened_on->format('d/m/Y') }}
                                </div>
                            @endif
                            @if ($log->reason)
                                <div class="text-xs text-charcoal/90 mt-1 italic">
                                    "{{ $log->reason }}"
                                </div>
                            @endif
                        </div>
                        <div class="text-[11px] text-muted sm:text-right flex-shrink-0">
                            <div>Oleh: <span class="font-semibold text-charcoal">{{ $log->actor?->name ?? 'Pengguna' }}</span></div>
                            <div class="font-mono mt-0.5">{{ $log->created_at->format('d M Y H:i') }} WIB</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <x-adjust-balance-modal />
</x-layouts.app>
