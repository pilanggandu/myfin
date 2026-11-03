<x-layouts.app title="Laporan Keuangan">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / LAPORAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Laporan Arus Kas & Analisis
            </h1>
            <p class="text-xs text-muted">
                Ringkasan arus kas masuk, pengeluaran, dan sebaran per kategori pada periode <strong>{{ $periodLabel }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="toggleBalanceVisibility()" 
                class="privacy-toggle-btn inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Nominal</span>
            </button>

            <!-- Filter Periode Laporan -->
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="month" class="field-select text-xs h-10 py-1.5 min-w-[120px]" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>

                <select name="year" class="field-select text-xs h-10 py-1.5 min-w-[90px]" onchange="this.form.submit()">
                    @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    <!-- Ringkasan Arus Kas Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="PEMASUKAN BERSIH" 
            :value="'Rp ' . number_format($totalIncome, 0, ',', '.')" 
            :dark="true" 
            :sub="$incomeCount . ' transaksi kas masuk'" 
        />

        <x-metric-card 
            label="PENGELUARAN BERSIH" 
            :value="'Rp ' . number_format($totalExpense, 0, ',', '.')" 
            :sub="$expenseCount . ' transaksi kas keluar'" 
        />

        <x-metric-card 
            label="ARUS KAS BERSIH (NET)" 
            :value="($netCashFlow >= 0 ? '+' : '') . 'Rp ' . number_format($netCashFlow, 0, ',', '.')" 
            :sub="$netCashFlow >= 0 ? 'Surplus kas periode ini' : 'Defisit kas periode ini'" 
        />

        <x-metric-card 
            label="TOTAL AKTIVITAS MUTASI" 
            :value="($incomeCount + $expenseCount) . ' Transaksi'" 
            sub="Pencatatan posted aktual" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Grafik & Tabel Sebaran Pengeluaran per Kategori -->
        <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                    <span class="text-sm font-bold text-forest">Sebaran Pengeluaran per Kategori</span>
                    <span class="text-xs text-muted">{{ $categoryBreakdown->count() }} kategori</span>
                </div>

                @if ($categoryBreakdown->isEmpty())
                    <div class="p-8 text-center text-xs text-muted">
                        Belum ada transaksi pengeluaran pada periode {{ $periodLabel }}.
                    </div>
                @else
                    <div class="p-5">
                        <!-- Canvas Chart.js -->
                        <div class="max-w-[260px] mx-auto mb-6">
                            <canvas id="categoryChart" width="260" height="260"></canvas>
                        </div>

                        <!-- Accessible Alternative Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                        <th class="py-2.5 px-3">Kategori</th>
                                        <th class="py-2.5 px-3 text-right">Jumlah Trx</th>
                                        <th class="py-2.5 px-3 text-right">Nominal</th>
                                        <th class="py-2.5 px-3 text-right">Porsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($categoryBreakdown as $cat)
                                        <tr class="hover:bg-cream/20 transition-colors">
                                            <td class="py-2.5 px-3 font-semibold text-charcoal">
                                                {{ $cat->name }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right text-muted">
                                                {{ $cat->count }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-charcoal">
                                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($cat->amount, 0, ',', '.') }}">Rp {{ number_format($cat->amount, 0, ',', '.') }}</span>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-bold text-forest">
                                                {{ $cat->percentage }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="p-3 bg-cream/20 border-t border-line text-[11px] text-muted text-center">
                Mutasi transfer antar-rekening dan transaksi dibatalkan (void) otomatis dikecualikan.
            </div>
        </div>

        <!-- Grafik & Tabel Tren Arus Kas 6 Bulan Terakhir -->
        <div class="panel-card p-0 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
                    <span class="text-sm font-bold text-forest">Tren Arus Kas (6 Bulan Terakhir)</span>
                    <span class="text-xs text-muted">Pemasukan vs Pengeluaran</span>
                </div>

                <div class="p-5">
                    <!-- Canvas Chart.js -->
                    <div class="h-56 mb-6">
                        <canvas id="trendChart"></canvas>
                    </div>

                    <!-- Accessible Alternative Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                                    <th class="py-2.5 px-3">Periode</th>
                                    <th class="py-2.5 px-3 text-right text-forest">Pemasukan</th>
                                    <th class="py-2.5 px-3 text-right text-red">Pengeluaran</th>
                                    <th class="py-2.5 px-3 text-right">Net Cash Flow</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($monthlyTrend as $m)
                                    <tr class="hover:bg-cream/20 transition-colors">
                                        <td class="py-2.5 px-3 font-semibold text-charcoal">
                                            {{ $m->label }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-semibold text-forest">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($m->income, 0, ',', '.') }}">Rp {{ number_format($m->income, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-semibold text-red">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($m->expense, 0, ',', '.') }}">Rp {{ number_format($m->expense, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold {{ $m->net >= 0 ? 'text-forest' : 'text-red' }}">
                                            <span class="privacy-balance" data-raw-value="{{ $m->net >= 0 ? '+' : '' }}Rp {{ number_format($m->net, 0, ',', '.') }}">{{ $m->net >= 0 ? '+' : '' }}Rp {{ number_format($m->net, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-cream/20 border-t border-line text-[11px] text-muted text-center">
                Angka riil berdasarkan tanggal transaksi pada zona waktu ruang aktif ({{ $space?->timezone }}).
            </div>
        </div>
    </div>

    <!-- Script Chart.js Loaded Only on This Page -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isDark = document.documentElement.classList.contains('dark');
            var textColor = isDark ? '#BAC8BC' : '#4A5B52';
            var gridColor = isDark ? 'rgba(52, 72, 61, 0.4)' : 'rgba(226, 232, 227, 0.6)';

            @if ($categoryBreakdown->isNotEmpty())
                var catLabels = @json($categoryBreakdown->pluck('name'));
                var catData = @json($categoryBreakdown->pluck('amount'));
                var catColors = [
                    '#24483B', '#3E6B5A', '#5A8E7A', '#7BAE99', '#9CCDB8',
                    '#BFDEC3', '#D6E8D8', '#C75C5C', '#D88A8A', '#E8BABA'
                ];

                var ctxCat = document.getElementById('categoryChart');
                if (ctxCat) {
                    new Chart(ctxCat, {
                        type: 'doughnut',
                        data: {
                            labels: catLabels,
                            datasets: [{
                                data: catData,
                                backgroundColor: catColors.slice(0, catLabels.length),
                                borderWidth: 1,
                                borderColor: isDark ? '#1C2A24' : '#FFFFFF'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            var val = context.raw || 0;
                                            return ' Rp ' + val.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            @endif

            var trendLabels = @json(collect($monthlyTrend)->pluck('label'));
            var trendIncome = @json(collect($monthlyTrend)->pluck('income'));
            var trendExpense = @json(collect($monthlyTrend)->pluck('expense'));

            var ctxTrend = document.getElementById('trendChart');
            if (ctxTrend) {
                new Chart(ctxTrend, {
                    type: 'bar',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: trendIncome,
                                backgroundColor: '#24483B',
                                borderRadius: 4
                            },
                            {
                                label: 'Pengeluaran',
                                data: trendExpense,
                                backgroundColor: '#C75C5C',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: { color: textColor, font: { size: 10 } },
                                grid: { display: false }
                            },
                            y: {
                                ticks: { 
                                    color: textColor, 
                                    font: { size: 10 },
                                    callback: function(value) {
                                        if (value >= 1000000) return (value / 1000000) + ' jt';
                                        if (value >= 1000) return (value / 1000) + ' rb';
                                        return value;
                                    }
                                },
                                grid: { color: gridColor }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: textColor, font: { size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var val = context.raw || 0;
                                        return context.dataset.label + ': Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.app>
