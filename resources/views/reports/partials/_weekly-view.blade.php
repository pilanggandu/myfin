@if (empty($dailyCashflows))
    <div class="panel-card p-12 text-center space-y-3">
        <span class="text-4xl block">📊</span>
        <div class="font-bold text-base text-charcoal">Belum Ada Transaksi Arus Kas</div>
        <p class="text-xs text-muted max-w-md mx-auto">
            Tidak ditemukan pencatatan mutasi kas (pemasukan/pengeluaran) pada periode {{ $periodLabel }}.
        </p>
    </div>
@else
    <!-- Ringkasan Statistik Mingguan (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
        <!-- Card 1: Rata-Rata Kas Keluar per Pekan -->
        <div class="panel-card p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Rata-Rata Keluar / Pekan</span>
            <div class="mt-2">
                <div class="text-base sm:text-lg font-mono font-bold text-red dark:text-rose-400">
                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($weeklyData['averageWeeklyOutflow'], 0, ',', '.') }}">
                        Rp {{ number_format($weeklyData['averageWeeklyOutflow'], 0, ',', '.') }}
                    </span>
                </div>
                <span class="text-[11px] text-muted">Dari total {{ $weeklyData['totalWeeksCount'] }} pekan</span>
            </div>
        </div>

        <!-- Card 2: Pekan Pengeluaran Tertinggi -->
        <div class="panel-card p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-red dark:text-rose-400">Pengeluaran Tertinggi</span>
            <div class="mt-2">
                <div class="text-base sm:text-lg font-mono font-bold text-red dark:text-rose-400">
                    @if ($weeklyData['highestExpenseWeek'] && $weeklyData['highestExpenseWeek']->outflow > 0)
                        <span class="privacy-balance" data-raw-value="-Rp {{ number_format($weeklyData['highestExpenseWeek']->outflow, 0, ',', '.') }}">
                            -Rp {{ number_format($weeklyData['highestExpenseWeek']->outflow, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-muted text-sm font-sans font-normal">-</span>
                    @endif
                </div>
                <span class="text-[11px] text-muted truncate block">
                    {{ $weeklyData['highestExpenseWeek'] && $weeklyData['highestExpenseWeek']->outflow > 0 ? $weeklyData['highestExpenseWeek']->title . ' (' . $weeklyData['highestExpenseWeek']->range_label . ')' : 'Belum ada pengeluaran' }}
                </span>
            </div>
        </div>

        <!-- Card 3: Pekan Kas Terbaik (Surplus Tertinggi) -->
        <div class="panel-card p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Pekan Arus Kas Terbaik</span>
            <div class="mt-2">
                <div class="text-base sm:text-lg font-mono font-bold {{ ($weeklyData['highestNetWeek']?->net ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red dark:text-rose-400' }}">
                    @if ($weeklyData['highestNetWeek'])
                        <span class="privacy-balance" data-raw-value="{{ $weeklyData['highestNetWeek']->net >= 0 ? '+' : '' }}Rp {{ number_format($weeklyData['highestNetWeek']->net, 0, ',', '.') }}">
                            {{ $weeklyData['highestNetWeek']->net >= 0 ? '+' : '' }}Rp {{ number_format($weeklyData['highestNetWeek']->net, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-muted text-sm font-sans font-normal">-</span>
                    @endif
                </div>
                <span class="text-[11px] text-muted truncate block">
                    {{ $weeklyData['highestNetWeek'] ? $weeklyData['highestNetWeek']->title . ' (' . $weeklyData['highestNetWeek']->range_label . ')' : 'Belum ada data' }}
                </span>
            </div>
        </div>

        <!-- Card 4: Pekan Aktif -->
        <div class="panel-card p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Aktivitas Pekan</span>
            <div class="mt-2">
                <div class="text-base sm:text-lg font-mono font-bold text-charcoal">
                    {{ $weeklyData['activeWeeksCount'] }} <span class="text-xs font-normal text-muted">/ {{ $weeklyData['totalWeeksCount'] }} pekan</span>
                </div>
                <span class="text-[11px] text-muted">{{ $activeDaysCount }} hari ada transaksi</span>
            </div>
        </div>
    </div>

    <!-- Daftar Kartu Pekan (Weekly Cards) -->
    <div class="space-y-4 mb-6">
        @foreach ($weeklyData['weeks'] as $week)
            <details class="group panel-card p-0 overflow-hidden border border-line shadow-2xs" open>
                <summary class="px-4 sm:px-6 py-3.5 bg-cream/40 dark:bg-line/25 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 cursor-pointer list-none select-none transition-colors hover:bg-cream/70 dark:hover:bg-line/40">
                    <!-- Kiri: Info Pekan & Tanggal -->
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-lg {{ $week->is_current_week ? 'bg-forest text-cream' : 'bg-forest/10 dark:bg-forest/20 text-forest' }} flex items-center justify-center flex-shrink-0 transition-transform">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </span>

                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-sm text-charcoal">
                                    {{ $week->title }}
                                </span>
                                <span class="text-xs text-muted font-medium">
                                    ({{ $week->range_label }})
                                </span>

                                @if ($week->is_current_week)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-forest/15 text-forest border border-forest/30">
                                        Pekan Ini
                                    </span>
                                @endif

                                @if ($week->tx_count > 0)
                                    <span class="text-[11px] text-muted font-mono font-medium">
                                        {{ $week->tx_count }} transaksi &bull; {{ $week->active_days_count }} hari aktif
                                    </span>
                                @else
                                    <span class="text-[11px] text-muted italic">
                                        (Tidak ada transaksi)
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Inflow, Outflow, Net, Saldo Akhir Pekan, Chevron -->
                    <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4 flex-wrap text-xs">
                        <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
                            @if ($week->inflow > 0)
                                <div class="flex items-center gap-1 font-mono font-bold text-forest" title="Pemasukan Pekan Ini">
                                    <span class="text-[10px] font-sans font-medium text-muted">Masuk:</span>
                                    <span class="privacy-balance" data-raw-value="+Rp {{ number_format($week->inflow, 0, ',', '.') }}">+Rp {{ number_format($week->inflow, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            @if ($week->outflow > 0)
                                <div class="flex items-center gap-1 font-mono font-bold text-red dark:text-rose-400" title="Pengeluaran Pekan Ini">
                                    <span class="text-[10px] font-sans font-medium text-muted">Keluar:</span>
                                    <span class="privacy-balance" data-raw-value="-Rp {{ number_format($week->outflow, 0, ',', '.') }}">-Rp {{ number_format($week->outflow, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            <div class="flex items-center gap-1 font-mono font-bold {{ $week->net >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }}" title="Arus Kas Bersih Pekan Ini">
                                <span class="text-[10px] font-sans font-medium text-muted">Net:</span>
                                <span class="privacy-balance" data-raw-value="{{ $week->net >= 0 ? '+' : '' }}Rp {{ number_format($week->net, 0, ',', '.') }}">
                                    {{ $week->net >= 0 ? '+' : '' }}Rp {{ number_format($week->net, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="px-2 py-1 sm:py-0.5 rounded bg-panel dark:bg-line/40 border border-line text-[11px] font-mono text-charcoal flex items-center gap-1.5" title="Saldo Kas Berjalan Akhir Pekan">
                                <span class="text-muted">Saldo Akhir:</span>
                                <span class="font-bold privacy-balance" data-raw-value="Rp {{ number_format($week->closing_balance, 0, ',', '.') }}">
                                    Rp {{ number_format($week->closing_balance, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Chevron Indicator for Details -->
                        <div class="w-6 h-6 rounded flex items-center justify-center text-muted group-hover:text-charcoal transition-transform duration-200 group-open:rotate-180">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>
                </summary>

                <div class="p-4 sm:p-5 space-y-4">
                    @if (($week->inflow + $week->outflow) > 0)
                        @php
                            $totalFlow = $week->inflow + $week->outflow;
                            $inflowPct = $totalFlow > 0 ? round(($week->inflow / $totalFlow) * 100, 1) : 0;
                            $outflowPct = $totalFlow > 0 ? (100 - $inflowPct) : 0;
                        @endphp
                        <!-- Bar Proporsi Arus Kas Pekan -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[11px] text-muted">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-forest"></span>
                                    <span>Kas Masuk: {{ $inflowPct }}%</span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-red dark:bg-rose-400"></span>
                                    <span>Kas Keluar: {{ $outflowPct }}%</span>
                                </span>
                            </div>
                            <div class="w-full h-1.5 bg-line/60 rounded-full overflow-hidden flex">
                                @if ($week->inflow > 0)
                                    <div class="bg-forest h-full" style="width: {{ $inflowPct }}%"></div>
                                @endif
                                @if ($week->outflow > 0)
                                    <div class="bg-red dark:bg-rose-400 h-full" style="width: {{ $outflowPct }}%"></div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($week->active_days_count > 0)
                        <!-- Daftar Hari dalam Pekan Tersebut -->
                        <div class="space-y-3">
                            @foreach ($week->days as $day)
                                @php
                                    $dayCarbon = $day->carbon;
                                    $spaceNow = $space ? $space->now() : now();
                                    $isToday = $day->date === $spaceNow->toDateString();
                                    $isYesterday = $day->date === $spaceNow->copy()->subDay()->toDateString();
                                @endphp
                                <div class="rounded-xl border border-line bg-cream/20 dark:bg-line/10 overflow-hidden">
                                    <!-- Sub-header Hari -->
                                    <div class="px-3.5 py-2.5 bg-cream/40 dark:bg-line/20 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-charcoal">
                                               {{ $day->day_name }}, {{ $day->date_formatted }}
                                            </span>
                                            @if ($isToday)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                                                    Hari Ini
                                                </span>
                                            @elseif ($isYesterday)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-cream dark:bg-line/50 text-muted">
                                                    Kemarin
                                                </span>
                                            @endif
                                            <span class="text-muted font-mono text-[11px]">
                                                ({{ $day->count }} transaksi)
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-3 font-mono text-[11px]">
                                            @if ($day->inflow > 0)
                                                <span class="text-forest font-semibold privacy-balance" data-raw-value="+Rp {{ number_format($day->inflow, 0, ',', '.') }}">
                                                    +Rp {{ number_format($day->inflow, 0, ',', '.') }}
                                                </span>
                                            @endif
                                            @if ($day->outflow > 0)
                                                <span class="text-red dark:text-rose-400 font-semibold privacy-balance" data-raw-value="-Rp {{ number_format($day->outflow, 0, ',', '.') }}">
                                                    -Rp {{ number_format($day->outflow, 0, ',', '.') }}
                                                </span>
                                            @endif
                                            <span class="font-bold {{ $day->net >= 0 ? 'text-forest' : 'text-red dark:text-rose-400' }} privacy-balance" data-raw-value="{{ $day->net >= 0 ? '+' : '' }}Rp {{ number_format($day->net, 0, ',', '.') }}">
                                                Net: {{ $day->net >= 0 ? '+' : '' }}Rp {{ number_format($day->net, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Daftar Transaksi Desktop -->
                                    <div class="hidden md:block overflow-x-auto">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-cream/30 dark:bg-line/10 text-[10px] uppercase font-bold text-muted border-b border-line">
                                                <tr>
                                                    <th class="py-2 px-3.5">Waktu</th>
                                                    <th class="py-2 px-3.5">Keterangan</th>
                                                    <th class="py-2 px-3.5">Kategori</th>
                                                    <th class="py-2 px-3.5">Rekening</th>
                                                    <th class="py-2 px-3.5 text-right">Nominal Kas</th>
                                                    <th class="py-2 px-3 text-center w-12">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-line/60">
                                                @foreach ($day->items as $item)
                                                    @php
                                                        $tx = $item->transaction;
                                                        $src = $tx->sourceAccount();
                                                        $dst = $tx->destinationAccount();
                                                        $isInc = $tx->isIncome();
                                                        $isExp = $tx->isExpense();
                                                        $isTrans = $tx->isTransfer();
                                                    @endphp
                                                    <tr class="hover:bg-cream/40 dark:hover:bg-line/20 transition-colors">
                                                        <td class="py-2 px-3.5 font-mono text-[11px] text-muted whitespace-nowrap">
                                                            {{ $tx->formattedTime() ?: '-' }}
                                                        </td>
                                                        <td class="py-2 px-3.5 font-semibold text-charcoal">
                                                            <a href="{{ route('transactions.show', $tx) }}" class="hover:text-forest hover:underline">
                                                                {{ $tx->description }}
                                                            </a>
                                                        </td>
                                                        <td class="py-2 px-3.5 whitespace-nowrap">
                                                            @if ($isTrans)
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300">
                                                                    Transfer
                                                                </span>
                                                            @elseif ($tx->category)
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-cream dark:bg-line/40 text-charcoal">
                                                                    {{ $tx->category->name }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted text-[11px]">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2 px-3.5 text-[11px] text-muted whitespace-nowrap">
                                                            @if ($isTrans)
                                                                {{ $src?->name ?? '?' }} &rarr; {{ $dst?->name ?? '?' }}
                                                            @elseif ($isInc)
                                                                {{ $dst?->name ?? '-' }}
                                                            @else
                                                                {{ $src?->name ?? '-' }}
                                                            @endif
                                                        </td>
                                                        <td class="py-2 px-3.5 text-right font-mono font-bold whitespace-nowrap">
                                                            @if ($item->inflow > 0)
                                                                <span class="text-forest privacy-balance" data-raw-value="+Rp {{ number_format($item->inflow, 0, ',', '.') }}">
                                                                    +Rp {{ number_format($item->inflow, 0, ',', '.') }}
                                                                </span>
                                                            @elseif ($item->outflow > 0)
                                                                <span class="text-red dark:text-rose-400 privacy-balance" data-raw-value="-Rp {{ number_format($item->outflow, 0, ',', '.') }}">
                                                                    -Rp {{ number_format($item->outflow, 0, ',', '.') }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted">Rp 0</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2 px-3 text-center">
                                                            <a href="{{ route('transactions.show', $tx) }}" class="inline-flex items-center justify-center w-7 h-7 rounded text-muted hover:text-forest hover:bg-cream dark:hover:bg-line/40 transition" title="Lihat Detail Transaksi">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                                </svg>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Daftar Transaksi Mobile -->
                                    <div class="md:hidden divide-y divide-line/60">
                                        @foreach ($day->items as $item)
                                            @php
                                                $tx = $item->transaction;
                                                $src = $tx->sourceAccount();
                                                $dst = $tx->destinationAccount();
                                                $isInc = $tx->isIncome();
                                                $isExp = $tx->isExpense();
                                                $isTrans = $tx->isTransfer();
                                            @endphp
                                            <div class="p-3 space-y-1.5 text-xs">
                                                <div class="flex items-start justify-between gap-2.5">
                                                    <div class="min-w-0 flex-1">
                                                        <a href="{{ route('transactions.show', $tx) }}" class="font-bold text-charcoal hover:text-forest hover:underline block truncate">
                                                            {{ $tx->description }}
                                                        </a>
                                                        <div class="flex items-center gap-1.5 text-[11px] text-muted flex-wrap mt-0.5">
                                                            @if ($tx->formattedTime())
                                                                <span class="font-mono">{{ $tx->formattedTime() }}</span>
                                                                <span>&bull;</span>
                                                            @endif
                                                            @if ($isTrans)
                                                                <span>{{ $src?->name ?? '?' }} &rarr; {{ $dst?->name ?? '?' }}</span>
                                                            @elseif ($isInc)
                                                                <span>{{ $dst?->name ?? '-' }}</span>
                                                            @else
                                                                <span>{{ $src?->name ?? '-' }}</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="text-right font-mono font-bold shrink-0">
                                                        @if ($item->inflow > 0)
                                                            <span class="text-forest privacy-balance" data-raw-value="+Rp {{ number_format($item->inflow, 0, ',', '.') }}">+Rp {{ number_format($item->inflow, 0, ',', '.') }}</span>
                                                        @elseif ($item->outflow > 0)
                                                            <span class="text-red dark:text-rose-400 privacy-balance" data-raw-value="-Rp {{ number_format($item->outflow, 0, ',', '.') }}">-Rp {{ number_format($item->outflow, 0, ',', '.') }}</span>
                                                        @else
                                                            <span class="text-muted">Rp 0</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center text-xs text-muted">
                            Tidak ada mutasi arus kas yang tercatat pada pekan ini.
                        </div>
                    @endif
                </div>
            </details>
        @endforeach
    </div>
@endif
