<x-layouts.app title="Analisis Kekayaan Bersih">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / LAPORAN &amp; NERACA</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Analisis Kekayaan Bersih
            </h1>
            <p class="text-xs text-muted">
                Pantau akumulasi aset dikurangi seluruh kewajiban dari waktu ke waktu, lengkap dengan riwayat pelunasan utang.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Horizon Waktu -->
            <form method="GET" action="{{ route('reports.net-worth') }}" class="flex items-center gap-2">
                <select name="horizon" class="field-select text-xs h-10 py-1.5 min-w-[130px]" onchange="this.form.submit()">
                    <option value="3" {{ $horizon === 3 ? 'selected' : '' }}>3 Bulan Terakhir</option>
                    <option value="6" {{ $horizon === 6 ? 'selected' : '' }}>6 Bulan Terakhir</option>
                    <option value="12" {{ $horizon === 12 ? 'selected' : '' }}>12 Bulan (1 Thn)</option>
                    <option value="24" {{ $horizon === 24 ? 'selected' : '' }}>24 Bulan (2 Thn)</option>
                </select>
            </form>

            <a href="{{ route('reports.net-worth.export-csv', ['horizon' => $horizon]) }}" class="btn-arus-secondary text-xs h-10 px-3 inline-flex items-center gap-1.5">
                <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Unduh CSV</span>
            </a>

            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'net-worth-scope\']')" 
                class="privacy-card-toggle inline-flex items-center justify-center gap-1.5 h-10 px-3 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal Kekayaan"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal</span>
            </button>
        </div>
    </div>

    <!-- Tab Navigasi Utama Laporan -->
    <div class="flex items-center gap-2 mb-6 border-b border-line overflow-x-auto whitespace-nowrap">
        <a 
            href="{{ route('reports.index', ['tab' => 'daily']) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 border-transparent text-muted hover:text-charcoal"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <span>Arus Kas Harian (Raw Data)</span>
        </a>

        <a 
            href="{{ route('reports.index', ['tab' => 'analytics']) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 border-transparent text-muted hover:text-charcoal"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
            </svg>
            <span>Ringkasan & Analisis</span>
        </a>

        <a 
            href="{{ route('reports.index', ['tab' => 'advanced']) }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 border-transparent text-muted hover:text-charcoal"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            <span>Analisis Lanjutan</span>
        </a>

        <a 
            href="{{ route('reports.net-worth') }}" 
            class="min-h-[44px] px-4 py-2.5 text-xs font-bold border-b-2 transition inline-flex items-center gap-2 shrink-0 border-forest text-forest"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" />
            </svg>
            <span>Kekayaan Bersih</span>
        </a>
    </div>

    <div data-privacy-scope="net-worth-scope" class="privacy-scope space-y-6">
        <!-- 1. Top Metric Cards: Neraca Saat Ini -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Net Worth Focal Dark Card -->
            <div class="p-5 rounded-2xl bg-forest dark:bg-emerald-950/80 text-white shadow-sm flex flex-col justify-between border border-forest/40">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[10px] font-bold tracking-[1.2px] uppercase text-emerald-100">KEKAYAAN BERSIH</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-white/15 text-white">
                            {{ $balanceSheet['solvency_status']['label'] }}
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold font-mono tracking-tight mt-1 text-white">
                        <span class="privacy-balance" data-raw-value="{{ $balanceSheet['net_worth'] < 0 ? '-' : '' }}Rp {{ number_format(abs($balanceSheet['net_worth']), 0, ',', '.') }}">
                            {{ $balanceSheet['net_worth'] < 0 ? '-' : '' }}Rp {{ number_format(abs($balanceSheet['net_worth']), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                <div class="pt-3 mt-4 border-t border-white/15 text-xs text-emerald-100 flex items-center justify-between">
                    <span>Pertumbuhan:</span>
                    <span class="font-bold font-mono {{ ($trends['summary']['total_growth'] ?? 0) >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                        {{ ($trends['summary']['total_growth'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($trends['summary']['total_growth'] ?? 0, 0, ',', '.') }} ({{ $trends['summary']['total_growth_percentage'] ?? 0 }}%)
                    </span>
                </div>
            </div>

            <!-- Card 2: Total Aset Bruto -->
            <x-metric-card 
                label="TOTAL ASET BRUTO" 
                :value="'Rp ' . number_format($balanceSheet['gross_assets'], 0, ',', '.')" 
                :sub="'Kas: Rp ' . number_format($balanceSheet['liquid_cash'], 0, ',', '.') . ' • Inv: Rp ' . number_format($balanceSheet['investment_assets'], 0, ',', '.')" 
            />

            <!-- Card 3: Total Kewajiban / Liabilitas -->
            <x-metric-card 
                label="TOTAL KEWAJIBAN &amp; UTANG" 
                :value="'Rp ' . number_format($balanceSheet['total_liabilities'], 0, ',', '.')" 
                :sub="'Cicilan: Rp ' . number_format($balanceSheet['installment_debt'], 0, ',', '.') . ' • Tagihan: Rp ' . number_format($balanceSheet['credit_card_debt'], 0, ',', '.')" 
            />

            <!-- Card 4: Debt-to-Asset Ratio -->
            <x-metric-card 
                label="DEBT-TO-ASSET RATIO (D/A)" 
                :value="($balanceSheet['debt_to_asset_ratio'] !== null ? $balanceSheet['debt_to_asset_ratio'] . '%' : '0%')" 
                :sub="$balanceSheet['debt_to_asset_ratio'] <= 20 ? 'Sangat Aman (&le; 20% prima)' : ($balanceSheet['debt_to_asset_ratio'] <= 40 ? 'Sehat (&le; 40% terkendali)' : 'Waspada (Beban utang tinggi)')" 
            />
        </div>

        <!-- 2. Visualisasi Tren Kekayaan Bersih & Riwayat Utang dari Waktu ke Waktu -->
        <div class="panel-card p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4 mb-6">
                <div>
                    <h2 class="font-serif text-lg text-forest font-normal">
                        Tren Kekayaan Bersih &amp; Riwayat Utang ({{ $horizon }} Bulan Terakhir)
                    </h2>
                    <p class="text-xs text-muted mt-0.5">
                        Perbandingan saldo akhir bulan antara total aset, total kewajiban, dan kekayaan bersih.
                    </p>
                </div>

                <div class="flex items-center gap-4 text-xs font-semibold">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm bg-forest"></span>
                        <span class="text-charcoal">Aset</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm bg-red"></span>
                        <span class="text-charcoal">Kewajiban</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 border border-white"></span>
                        <span class="text-charcoal">Kekayaan Bersih</span>
                    </div>
                </div>
            </div>

            <!-- Visual Bar Chart -->
            @php
                $maxVal = $trends['summary']['max_chart_val'] > 0 ? $trends['summary']['max_chart_val'] : 1;
            @endphp
            <div class="overflow-x-auto pb-2">
                <div class="min-w-[500px] grid grid-cols-{{ count($trends['trend']) }} gap-3 sm:gap-6 items-end pt-6 pb-2 border-b border-line">
                    @foreach ($trends['trend'] as $item)
                        @php
                            $assetHeight = min(100, max(4, round(($item['assets'] / $maxVal) * 100)));
                            $liabHeight = min(100, max(2, round(($item['liabilities'] / $maxVal) * 100)));
                            $isNetPositive = $item['net_worth'] >= 0;
                        @endphp
                        <div class="flex flex-col items-center gap-2 group">
                            <!-- Popover / Tooltip Nilai -->
                            <div class="text-[10px] font-mono font-bold text-center {{ $isNetPositive ? 'text-forest' : 'text-red' }}">
                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($item['net_worth'], 0, ',', '.') }}">
                                    {{ $isNetPositive ? '' : '-' }}Rp {{ number_format(abs($item['net_worth'] / 1000000), 1) }}M
                                </span>
                            </div>

                            <!-- Batang Visual Vertikal -->
                            <div class="h-44 w-full max-w-[64px] flex items-end justify-center gap-1 bg-sidebar/40 p-1.5 rounded-xl border border-line/60">
                                <!-- Batang Aset -->
                                <div 
                                    class="w-1/2 bg-forest hover:bg-forest/80 rounded-t transition-all duration-300" 
                                    style="height: {{ $assetHeight }}%"
                                    title="Aset {{ $item['label'] }}: Rp {{ number_format($item['assets'], 0, ',', '.') }}"
                                ></div>

                                <!-- Batang Kewajiban -->
                                <div 
                                    class="w-1/2 bg-red hover:bg-red/80 rounded-t transition-all duration-300" 
                                    style="height: {{ $liabHeight }}%"
                                    title="Kewajiban {{ $item['label'] }}: Rp {{ number_format($item['liabilities'], 0, ',', '.') }}"
                                ></div>
                            </div>

                            <!-- Label Bulan -->
                            <div class="text-center">
                                <span class="text-xs font-bold text-charcoal block">{{ $item['label'] }}</span>
                                <span class="text-[10px] font-mono text-muted block">
                                    {{ $item['debt_to_asset_ratio'] }}% D/A
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Ringkasan Deleveraging & Akumulasi Aset -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-xs">
                <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                    <span class="text-muted block text-[10px] font-bold uppercase">Pertumbuhan Kekayaan Bersih</span>
                    <span class="font-bold text-sm font-mono {{ ($trends['summary']['total_growth'] ?? 0) >= 0 ? 'text-forest' : 'text-red' }}">
                        {{ ($trends['summary']['total_growth'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($trends['summary']['total_growth'] ?? 0, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">Dari awal periode {{ $horizon }} bulan lalu</span>
                </div>

                <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                    <span class="text-muted block text-[10px] font-bold uppercase">Perubahan Total Aset</span>
                    <span class="font-bold text-sm font-mono {{ ($trends['summary']['asset_change'] ?? 0) >= 0 ? 'text-forest' : 'text-red' }}">
                        {{ ($trends['summary']['asset_change'] ?? 0) >= 0 ? '+' : '' }}Rp {{ number_format($trends['summary']['asset_change'] ?? 0, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">Akumulasi tabungan &amp; investasi</span>
                </div>

                <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                    <span class="text-muted block text-[10px] font-bold uppercase">Status Pelunasan Utang (Deleveraging)</span>
                    <span class="font-bold text-sm font-mono {{ ($trends['summary']['debt_change'] ?? 0) <= 0 ? 'text-forest' : 'text-red' }}">
                        {{ ($trends['summary']['debt_change'] ?? 0) <= 0 ? 'Utang Berkurang ' : 'Utang Bertambah ' }}
                        Rp {{ number_format(abs($trends['summary']['debt_change'] ?? 0), 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">
                        {{ ($trends['summary']['debt_change'] ?? 0) <= 0 ? 'Tren deleveraging sehat' : 'Waspada penambahan utang baru' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Tabel Detail Riwayat dari Waktu ke Waktu -->
        <div class="panel-card p-0 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-forest">Tabel Perubahan Neraca &amp; Riwayat Utang Bulanan</h3>
                    <p class="text-xs text-muted">Rekapitulasi posisi aset dan liabilitas pada setiap penutupan bulan</p>
                </div>
                <span class="text-xs text-muted">{{ count($trends['trend']) }} periode tercatat</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                            <th class="py-3 px-6">Periode Bulan</th>
                            <th class="py-3 px-4 text-right">Total Aset</th>
                            <th class="py-3 px-4 text-right">Total Kewajiban</th>
                            <th class="py-3 px-4 text-right">Kekayaan Bersih</th>
                            <th class="py-3 px-4 text-right">Perubahan Bulanan</th>
                            <th class="py-3 px-4 text-right">D/A Ratio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line font-mono">
                        @foreach ($trends['trend'] as $row)
                            <tr class="hover:bg-cream/20 transition-colors">
                                <td class="py-3.5 px-6 font-sans font-bold text-charcoal">
                                    {{ $row['full_label'] }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-charcoal">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($row['assets'], 0, ',', '.') }}">Rp {{ number_format($row['assets'], 0, ',', '.') }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-red">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($row['liabilities'], 0, ',', '.') }}">Rp {{ number_format($row['liabilities'], 0, ',', '.') }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold {{ $row['net_worth'] >= 0 ? 'text-forest' : 'text-red' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $row['net_worth'] < 0 ? '-' : '' }}Rp {{ number_format(abs($row['net_worth']), 0, ',', '.') }}">{{ $row['net_worth'] < 0 ? '-' : '' }}Rp {{ number_format(abs($row['net_worth']), 0, ',', '.') }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold {{ $row['delta'] >= 0 ? 'text-forest' : 'text-red' }}">
                                    {{ $row['delta'] >= 0 ? '+' : '' }}<span class="privacy-balance" data-raw-value="Rp {{ number_format($row['delta'], 0, ',', '.') }}">Rp {{ number_format($row['delta'], 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-muted">({{ $row['delta_percentage'] }}%)</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-sans font-bold {{ $row['debt_to_asset_ratio'] <= 20 ? 'text-forest' : ($row['debt_to_asset_ratio'] <= 40 ? 'text-charcoal' : 'text-red') }}">
                                    {{ $row['debt_to_asset_ratio'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Rincian Komposisi Aset & Kewajiban Saat Ini (2 Kolom) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Kolom Kiri: Komposisi Aset -->
            <div class="panel-card p-5">
                <div class="flex items-center justify-between border-b border-line pb-3 mb-4">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-forest">
                        Rincian Komposisi Aset
                    </h3>
                    <span class="text-xs font-mono font-bold text-charcoal">
                        Total: <span class="privacy-balance" data-raw-value="Rp {{ number_format($balanceSheet['gross_assets'], 0, ',', '.') }}">Rp {{ number_format($balanceSheet['gross_assets'], 0, ',', '.') }}</span>
                    </span>
                </div>

                @if (empty($balanceSheet['asset_composition']))
                    <p class="text-xs text-muted py-4 text-center">Belum ada akun aset aktif tercatat.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($balanceSheet['asset_composition'] as $asset)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-charcoal">{{ $asset['name'] }}</span>
                                        <span class="text-[10px] text-muted bg-cream px-1.5 py-0.2 rounded border border-line">{{ $asset['type_label'] }}</span>
                                    </div>
                                    <div class="font-mono text-right">
                                        <span class="font-bold text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($asset['balance'], 0, ',', '.') }}">Rp {{ number_format($asset['balance'], 0, ',', '.') }}</span>
                                        <span class="text-[10px] text-muted ml-1">({{ $asset['percentage'] }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-cream/60 dark:bg-sidebar/80 rounded-full h-1.5 overflow-hidden border border-line/40">
                                    <div class="bg-forest h-1.5 rounded-full" style="width: {{ $asset['percentage'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Kolom Kanan: Rincian Kewajiban & Progres Pelunasan Cicilan -->
            <div class="panel-card p-5">
                <div class="flex items-center justify-between border-b border-line pb-3 mb-4">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-forest">
                        Rincian Utang &amp; Progres Pelunasan
                    </h3>
                    <span class="text-xs font-mono font-bold text-red">
                        Total: <span class="privacy-balance" data-raw-value="Rp {{ number_format($balanceSheet['total_liabilities'], 0, ',', '.') }}">Rp {{ number_format($balanceSheet['total_liabilities'], 0, ',', '.') }}</span>
                    </span>
                </div>

                @if (empty($balanceSheet['credit_card_composition']) && empty($balanceSheet['installment_composition']))
                    <div class="p-6 text-center text-xs text-muted">
                        <span class="text-forest font-bold block mb-1">Bebas Utang! 🎉</span>
                        Tidak ada kewajiban cicilan atau tagihan kartu kredit aktif dalam ruang ini.
                    </div>
                @else
                    <div class="space-y-4">
                        <!-- Kartu Kredit / Paylater -->
                        @if (! empty($balanceSheet['credit_card_composition']))
                            <div class="space-y-2">
                                <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Tagihan Kartu Kredit &amp; Paylater</span>
                                @foreach ($balanceSheet['credit_card_composition'] as $card)
                                    <div class="p-2.5 rounded-xl bg-sidebar/50 border border-line text-xs flex items-center justify-between">
                                        <div>
                                            <span class="font-bold text-charcoal block">{{ $card['name'] }}</span>
                                            <span class="text-[10px] text-muted">Limit: Rp {{ number_format($card['credit_limit'], 0, ',', '.') }}</span>
                                        </div>
                                        <div class="text-right font-mono">
                                            <span class="font-bold text-red block privacy-balance" data-raw-value="Rp {{ number_format($card['liability'], 0, ',', '.') }}">Rp {{ number_format($card['liability'], 0, ',', '.') }}</span>
                                            <span class="text-[10px] text-muted font-sans">Utilisasi: {{ $card['utilization'] }}%</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Cicilan Berjalan -->
                        @if (! empty($debtHistory['plans']))
                            <div class="space-y-2.5">
                                <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Riwayat Pokok Cicilan</span>
                                @foreach ($debtHistory['plans'] as $plan)
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <div>
                                                <span class="font-bold text-charcoal">{{ $plan['name'] }}</span>
                                                <span class="text-[10px] text-muted ml-1">({{ $plan['category'] }})</span>
                                            </div>
                                            <div class="text-right font-mono text-[11px]">
                                                <span class="font-bold text-charcoal">Sisa Rp {{ number_format($plan['remaining_principal'], 0, ',', '.') }}</span>
                                                <span class="text-[10px] text-muted font-sans ml-1">{{ $plan['progress_percentage'] }}% lunas</span>
                                            </div>
                                        </div>
                                        <div class="w-full bg-cream/60 dark:bg-sidebar/80 rounded-full h-1.5 overflow-hidden border border-line/40">
                                            <div class="bg-forest h-1.5 rounded-full" style="width: {{ $plan['progress_percentage'] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- 5. Wawasan & Rekomendasi Ahli Finansial -->
        <div class="panel-card p-5">
            <h3 class="font-bold text-xs uppercase tracking-wider text-forest mb-3 flex items-center gap-1.5">
                <span>💡 Wawasan &amp; Analisis Solvabilitas Keuangan</span>
            </h3>
            <ul class="space-y-2 text-xs text-charcoal list-disc pl-4 leading-relaxed">
                @foreach ($insights as $insight)
                    <li>{{ $insight }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts.app>
