@php
    $balance = isset($precomputedBalance) ? (float) $precomputedBalance : $account->currentBalance();
    $typeLabel = $account->typeLabel();
    $availableLimit = $account->availableLimit();
    $isArchived = $account->isArchived();
    $isDefault = $account->isDefault();

    $iconBadgeClass = match($account->type) {
        'investment' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20',
        'loan' => 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30',
        default => 'bg-forest-light text-forest dark:text-blue-400 border border-forest/20',
    };
@endphp

<article
    data-account-card="{{ $account->id }}"
    class="min-w-0 rounded-xl border p-4 sm:p-5 flex flex-col justify-between transition-colors {{ $isArchived ? 'bg-sidebar/30 border-line border-dashed opacity-75' : ($isDefault ? 'bg-panel border-forest/40 shadow-sm' : 'bg-panel border-line hover:border-sage shadow-xs') }}"
>
    <div>
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $iconBadgeClass }}">
                    @include('accounts.partials._type-icon', ['type' => $account->type])
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-base text-charcoal leading-snug line-clamp-2">
                        <a
                            href="{{ route('accounts.show', $account) }}"
                            class="hover:text-forest transition rounded focus:ring-2 focus:ring-forest"
                        >
                            {{ $account->name }}
                        </a>
                    </h3>
                    <div class="flex items-center gap-1.5 flex-wrap mt-1">
                        <x-badge variant="default">{{ $typeLabel }}</x-badge>
                        @if ($isDefault)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/15 border border-amber-500/30 uppercase tracking-wider">
                                Default
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0">
                @if ($isArchived)
                    <x-badge variant="inactive">Diarsipkan</x-badge>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest dark:text-blue-400 border border-forest/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                        Aktif
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 p-3.5 rounded-lg bg-sidebar/50 border border-line">
            @if ($account->isCreditCard() && $availableLimit !== null)
                @php
                    $creditLimit = (float) ($account->creditAccount?->credit_limit ?? 0);
                    $usedAmount = $account->liabilityAmount();
                    $usedPct = $creditLimit > 0 ? min(100, round(($usedAmount / $creditLimit) * 100)) : 0;
                @endphp
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-charcoal/80">Sisa Limit Tersedia</span>
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded uppercase tracking-wider">
                        Sisa Limit
                    </span>
                </div>
                <div class="font-mono text-xl sm:text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400 mt-1">
                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($availableLimit, 0, ',', '.') }}">
                        Rp {{ number_format($availableLimit, 0, ',', '.') }}
                    </span>
                </div>
                <div class="mt-2 text-xs text-charcoal/80 flex items-center justify-between gap-2">
                    <span>
                        Terpakai:
                        <span class="privacy-balance font-mono font-semibold text-charcoal" data-raw-value="Rp {{ number_format($usedAmount, 0, ',', '.') }}">
                            Rp {{ number_format($usedAmount, 0, ',', '.') }}
                        </span>
                    </span>
                    @if ($creditLimit > 0)
                        <span class="font-mono text-[11px] font-semibold">{{ $usedPct }}%</span>
                    @endif
                </div>
                @if ($creditLimit > 0)
                    <div class="w-full h-1.5 rounded-full bg-cream border border-line overflow-hidden mt-1.5" aria-hidden="true">
                        <div class="h-full rounded-full {{ $usedPct >= 80 ? 'bg-red' : 'bg-forest' }}" style="width: {{ $usedPct }}%"></div>
                    </div>
                @endif
            @elseif ($account->isLoan())
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-charcoal/80">Sisa Kewajiban</span>
                    <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded uppercase tracking-wider">
                        Kewajiban
                    </span>
                </div>
                <div class="font-mono text-xl sm:text-2xl font-bold tracking-tight text-charcoal mt-1">
                    <span class="privacy-balance" data-raw-value="Rp {{ number_format(abs($balance), 0, ',', '.') }}">
                        Rp {{ number_format(abs($balance), 0, ',', '.') }}
                    </span>
                </div>
                <div class="mt-1.5 text-xs text-charcoal/80">Total hutang berjalan</div>
            @else
                <div class="text-[11px] font-bold uppercase tracking-wider text-charcoal/80">Saldo Saat Ini</div>
                <div class="font-mono text-xl sm:text-2xl font-bold tracking-tight mt-1 {{ $balance < 0 ? 'text-red' : 'text-charcoal' }}">
                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($balance, 0, ',', '.') }}">
                        Rp {{ number_format($balance, 0, ',', '.') }}
                    </span>
                </div>
            @endif
        </div>

        <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
            <div class="min-w-0">
                <dt class="text-[11px] text-muted">Saldo Awal</dt>
                <dd class="font-mono font-semibold text-charcoal mt-0.5 truncate">
                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($account->opening_balance, 0, ',', '.') }}">
                        Rp {{ number_format($account->opening_balance, 0, ',', '.') }}
                    </span>
                </dd>
            </div>
            <div class="min-w-0 text-right">
                <dt class="text-[11px] text-muted">Tanggal Buka</dt>
                <dd class="font-medium text-charcoal mt-0.5">
                    {{ $account->opened_on->format('d M Y') }}
                </dd>
            </div>
        </dl>
    </div>

    <div class="mt-4 pt-3 border-t border-line flex items-center justify-between gap-2 flex-wrap">
        <a
            href="{{ route('accounts.show', $account) }}"
            class="min-h-[44px] px-3.5 py-2 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center focus:ring-2 focus:ring-forest"
        >
            Riwayat Mutasi
        </a>

        <div class="flex items-center gap-1.5 flex-wrap">
            @if (! $isDefault && ! $isArchived)
                <form method="POST" action="{{ route('accounts.setDefault', $account) }}" class="inline">
                    @csrf
                    <button
                        type="submit"
                        class="min-h-[44px] px-3 rounded-lg border border-line bg-panel text-muted hover:text-amber-500 hover:border-amber-500/40 hover:bg-amber-500/5 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                        title="Jadikan Rekening Default Transaksi"
                        aria-label="Jadikan {{ $account->name }} rekening default transaksi"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </button>
                </form>
            @endif

            @if (! $isArchived)
                <button
                    type="button"
                    data-account-id="{{ $account->id }}"
                    data-account-name="{{ $account->name }}"
                    data-account-balance="{{ $balance }}"
                    onclick="openAdjustModal(Number(this.dataset.accountId), this.dataset.accountName, Number(this.dataset.accountBalance))"
                    class="min-h-[44px] px-3 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                    title="Penyesuaian Saldo (Opname / Rekonsiliasi)"
                    aria-label="Penyesuaian saldo {{ $account->name }}"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" />
                    </svg>
                </button>
            @endif

            <a
                href="{{ route('accounts.edit', $account) }}"
                class="min-h-[44px] px-3 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center focus:ring-2 focus:ring-forest"
                title="Ubah Data Rekening"
                aria-label="Ubah data rekening {{ $account->name }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
            </a>

            @if ($isArchived)
                <form method="POST" action="{{ route('accounts.unarchive', $account) }}" class="inline">
                    @csrf
                    <button
                        type="submit"
                        class="min-h-[44px] px-3 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                        title="Aktifkan Kembali Rekening"
                        aria-label="Aktifkan kembali rekening {{ $account->name }}"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('accounts.archive', $account) }}" class="inline" onsubmit="return confirm('Arsipkan rekening ini? Rekening arsip tidak dapat digunakan untuk transaksi baru, namun saldo dan riwayat tetap tersimpan.')">
                    @csrf
                    <button
                        type="submit"
                        class="min-h-[44px] px-3 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer focus:ring-2 focus:ring-forest"
                        title="Arsipkan Rekening"
                        aria-label="Arsipkan rekening {{ $account->name }}"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</article>
