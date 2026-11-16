<x-layouts.app title="Simulasi Keputusan Keuangan">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / PERENCANAAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Simulasi Keputusan Keuangan
            </h1>
            <p class="text-xs text-muted">
                Uji dampak keputusan besar terhadap arus kas, rasio utang (DSR), dan dana darurat sebelum Anda mengambil komitmen.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="document.getElementById('baseline-custom-section').classList.toggle('hidden')" 
                class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs inline-flex items-center gap-1.5 cursor-pointer"
                title="Sesuaikan data asumsi pemasukan dan pengeluaran"
            >
                <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
                <span>Sesuaikan Baseline</span>
            </button>

            @if ($simulations->isNotEmpty())
                <a href="#saved-simulations-section" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>
                    <span>Tersimpan ({{ $simulations->count() }})</span>
                </a>
            @endif
        </div>
    </div>

    <!-- 1. Baseline Keuangan Riil Saat Ini -->
    <div class="panel-card mb-6">
        <div class="flex items-center justify-between border-b border-line pb-3 mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-forest uppercase tracking-wider">Baseline Finansial Anda Saat Ini</span>
                <span class="text-[10px] text-muted bg-cream px-2 py-0.5 rounded-full border border-line">Ruang: {{ $space->name }}</span>
            </div>
            <div class="text-[11px] text-muted">
                Dihitung dari riwayat transaksi &amp; cicilan aktif
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-3 rounded-xl bg-sidebar/60 border border-line/60">
                <span class="text-[10px] font-bold text-muted uppercase block">Pemasukan Rutin Bulanan</span>
                <span class="text-sm font-bold text-charcoal mt-1 block font-mono" id="disp-base-income">
                    Rp {{ number_format($baseline['monthly_income'], 0, ',', '.') }}
                </span>
                <span class="text-[10px] text-muted block mt-0.5">Rata-rata arus masuk</span>
            </div>

            <div class="p-3 rounded-xl bg-sidebar/60 border border-line/60">
                <span class="text-[10px] font-bold text-muted uppercase block">Pengeluaran Rutin Bulanan</span>
                <span class="text-sm font-bold text-charcoal mt-1 block font-mono" id="disp-base-expense">
                    Rp {{ number_format($baseline['monthly_expense'], 0, ',', '.') }}
                </span>
                <span class="text-[10px] text-muted block mt-0.5">Kebutuhan hidup di luar utang</span>
            </div>

            <div class="p-3 rounded-xl bg-sidebar/60 border border-line/60">
                <span class="text-[10px] font-bold text-muted uppercase block">Beban Cicilan Bulanan (DSR)</span>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-sm font-bold text-charcoal font-mono" id="disp-base-debt">
                        Rp {{ number_format($baseline['monthly_debt'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] font-bold {{ $baseline['dsr'] > 30 ? 'text-amber-600' : 'text-forest' }}" id="disp-base-dsr">
                        ({{ $baseline['dsr'] }}%)
                    </span>
                </div>
                <span class="text-[10px] text-muted block mt-0.5">Batas aman sehat: &le; 30%</span>
            </div>

            <div class="p-3 rounded-xl bg-sidebar/60 border border-line/60">
                <span class="text-[10px] font-bold text-muted uppercase block">Kas Likuid (Runway)</span>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-sm font-bold text-charcoal font-mono" id="disp-base-liquid">
                        Rp {{ number_format($baseline['liquid_cash'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] font-bold text-forest" id="disp-base-runway">
                        ({{ $baseline['runway_months'] }} bln)
                    </span>
                </div>
                <span class="text-[10px] text-muted block mt-0.5">Ketahanan dana darurat</span>
            </div>
        </div>

        <!-- Section Pengaturan Baseline Custom (Collapsible) -->
        <div id="baseline-custom-section" class="hidden mt-4 pt-4 border-t border-line">
            <div class="text-xs font-bold text-charcoal mb-2">Penyesuaian Asumsi Baseline (What-If Override):</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-muted mb-1">Pemasukan Bulanan (Rp)</label>
                    <input type="number" id="input-base-income" value="{{ (int) $baseline['monthly_income'] }}" class="field-input text-xs font-mono font-bold w-full" onchange="runSimulation()">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-muted mb-1">Pengeluaran Rutin (Rp)</label>
                    <input type="number" id="input-base-expense" value="{{ (int) $baseline['monthly_expense'] }}" class="field-input text-xs font-mono font-bold w-full" onchange="runSimulation()">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-muted mb-1">Cicilan Saat Ini (Rp)</label>
                    <input type="number" id="input-base-debt" value="{{ (int) $baseline['monthly_debt'] }}" class="field-input text-xs font-mono font-bold w-full" onchange="runSimulation()">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-muted mb-1">Saldo Kas Likuid (Rp)</label>
                    <input type="number" id="input-base-liquid" value="{{ (int) $baseline['liquid_cash'] }}" class="field-input text-xs font-mono font-bold w-full" onchange="runSimulation()">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-3">
                <button type="button" onclick="resetBaselineToDefault()" class="text-xs text-forest hover:underline font-bold cursor-pointer">
                    Kembalikan ke Data Riil
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Formulir Interaktif & Hasil Analisis -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
        <!-- Kolom Kiri: Formulir Keputusan (5 Kolom) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="panel-card p-4 sm:p-6">
                <!-- Tab Jenis Keputusan -->
                <div class="flex border-b border-line mb-4 pb-2 gap-1 overflow-x-auto">
                    <button 
                        type="button" 
                        onclick="switchScenarioType('asset_purchase')" 
                        id="tab-btn-asset_purchase" 
                        class="scenario-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer whitespace-nowrap bg-forest text-white"
                    >
                        🚗 Beli Kendaraan / Aset
                    </button>
                    <button 
                        type="button" 
                        onclick="switchScenarioType('loan_installment')" 
                        id="tab-btn-loan_installment" 
                        class="scenario-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer whitespace-nowrap text-muted hover:text-charcoal hover:bg-cream"
                    >
                        💳 Cicilan Baru
                    </button>
                    <button 
                        type="button" 
                        onclick="switchScenarioType('saving_target')" 
                        id="tab-btn-saving_target" 
                        class="scenario-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer whitespace-nowrap text-muted hover:text-charcoal hover:bg-cream"
                    >
                        🎯 Target Tabungan
                    </button>
                </div>

                <form id="simulation-form" onsubmit="event.preventDefault(); runSimulation();" class="space-y-4 text-xs">
                    <input type="hidden" id="param-scenario_type" value="asset_purchase">

                    <!-- Skenario A: Beli Kendaraan / Aset -->
                    <div id="fields-asset_purchase" class="space-y-4">
                        <div>
                            <label class="block font-bold text-charcoal mb-1">Harga Beli Aset / Kendaraan (Rp)</label>
                            <input type="number" id="asset-purchase_price" value="35000000" step="500000" min="0" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            <span class="text-[11px] text-muted mt-0.5 block">Contoh: Rp 35.000.000 (Motor/Mobil/Laptop/Elektronik)</span>
                        </div>

                        <div>
                            <label class="block font-bold text-charcoal mb-1">Metode Pembayaran</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-line cursor-pointer bg-sidebar/40 has-[:checked]:border-forest has-[:checked]:bg-forest/5">
                                    <input type="radio" name="asset_payment_method" value="credit" checked onchange="toggleAssetPaymentMethod('credit')" class="text-forest focus:ring-forest/20">
                                    <span class="font-bold text-charcoal">Kredit / Cicilan</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-line cursor-pointer bg-sidebar/40 has-[:checked]:border-forest has-[:checked]:bg-forest/5">
                                    <input type="radio" name="asset_payment_method" value="cash" onchange="toggleAssetPaymentMethod('cash')" class="text-forest focus:ring-forest/20">
                                    <span class="font-bold text-charcoal">Tunai / Cash Lunas</span>
                                </label>
                            </div>
                        </div>

                        <div id="asset-credit-details" class="space-y-3 p-3.5 rounded-xl bg-sidebar/30 border border-line">
                            <div>
                                <label class="block font-bold text-charcoal mb-1">Uang Muka / DP (Rp)</label>
                                <input type="number" id="asset-down_payment" value="7000000" step="500000" min="0" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                                <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                    <button type="button" onclick="setAssetDpPercent(10)" class="text-[10px] px-2 py-0.5 rounded bg-cream border border-line hover:border-forest font-bold">10%</button>
                                    <button type="button" onclick="setAssetDpPercent(20)" class="text-[10px] px-2 py-0.5 rounded bg-cream border border-line hover:border-forest font-bold">20%</button>
                                    <button type="button" onclick="setAssetDpPercent(30)" class="text-[10px] px-2 py-0.5 rounded bg-cream border border-line hover:border-forest font-bold">30%</button>
                                    <button type="button" onclick="setAssetDpPercent(50)" class="text-[10px] px-2 py-0.5 rounded bg-cream border border-line hover:border-forest font-bold">50%</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block font-bold text-charcoal mb-1">Tenor (Bulan)</label>
                                    <select id="asset-tenor_months" class="field-select w-full" onchange="runSimulation()">
                                        <option value="6">6 Bulan</option>
                                        <option value="12">12 Bulan (1 Thn)</option>
                                        <option value="24">24 Bulan (2 Thn)</option>
                                        <option value="36" selected>36 Bulan (3 Thn)</option>
                                        <option value="48">48 Bulan (4 Thn)</option>
                                        <option value="60">60 Bulan (5 Thn)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-charcoal mb-1">Bunga / Margin (% p.a)</label>
                                    <input type="number" id="asset-interest_rate" value="8.5" step="0.1" min="0" max="100" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-muted mb-1">Atau Nominal Cicilan Pasti per Bulan (Opsional)</label>
                                <input type="number" id="asset-manual_installment" placeholder="Kosongkan jika dihitung otomatis" class="field-input w-full font-mono" oninput="runSimulation()">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-charcoal mb-1">Biaya Operasional Rutin Bulanan Baru (Opsional)</label>
                            <input type="number" id="asset-running_cost" value="600000" step="50000" min="0" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            <span class="text-[11px] text-muted mt-0.5 block">Bensin, servis, ganti oli, pajak tahunan/12, asuransi, dll.</span>
                        </div>
                    </div>

                    <!-- Skenario B: Cicilan / Pinjaman Baru -->
                    <div id="fields-loan_installment" class="space-y-4 hidden">
                        <div>
                            <label class="block font-bold text-charcoal mb-1">Plafon Pokok Pinjaman (Rp)</label>
                            <input type="number" id="loan-amount" value="20000000" step="500000" min="0" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            <span class="text-[11px] text-muted mt-0.5 block">KTA, Paylater, Pinjaman Bank, atau Pinjaman Karyawan</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-charcoal mb-1">Tenor (Bulan)</label>
                                <select id="loan-tenor_months" class="field-select w-full" onchange="runSimulation()">
                                    <option value="3">3 Bulan</option>
                                    <option value="6">6 Bulan</option>
                                    <option value="12">12 Bulan (1 Thn)</option>
                                    <option value="24" selected>24 Bulan (2 Thn)</option>
                                    <option value="36">36 Bulan (3 Thn)</option>
                                    <option value="48">48 Bulan (4 Thn)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-charcoal mb-1">Bunga (% Flat p.a)</label>
                                <input type="number" id="loan-interest_rate" value="9.0" step="0.1" min="0" max="100" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-muted mb-1">Atau Cicilan Pasti per Bulan (Opsional)</label>
                            <input type="number" id="loan-manual_installment" placeholder="Kosongkan jika dihitung otomatis" class="field-input w-full font-mono" oninput="runSimulation()">
                        </div>

                        <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-sidebar/30 border border-line">
                            <div>
                                <label class="block font-bold text-charcoal mb-1">Biaya Admin / Provisi</label>
                                <input type="number" id="loan-admin_fee" value="500000" step="50000" min="0" class="field-input w-full font-mono" oninput="runSimulation()">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="loan-admin_deducted" checked onchange="runSimulation()" class="rounded border-line text-forest focus:ring-forest/20">
                                    <span class="text-[11px] font-semibold text-charcoal">Potong dari pencairan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Skenario C: Target Tabungan -->
                    <div id="fields-saving_target" class="space-y-4 hidden">
                        <div>
                            <label class="block font-bold text-charcoal mb-1">Target Nominal yang Ingin Dicapai (Rp)</label>
                            <input type="number" id="saving-target_amount" value="50000000" step="1000000" min="0" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            <span class="text-[11px] text-muted mt-0.5 block">Contoh: Dana Darurat, Dana Pendidikan, Liburan, DP Rumah</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-charcoal mb-1">Jangka Waktu (Bulan)</label>
                                <input type="number" id="saving-target_months" value="24" min="1" max="240" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            </div>
                            <div>
                                <label class="block font-bold text-charcoal mb-1">Imbal Hasil Investasi (% p.a)</label>
                                <input type="number" id="saving-return_rate" value="5.0" step="0.5" min="0" max="50" class="field-input w-full font-mono font-bold" oninput="runSimulation()">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-muted mb-1">Atau Setoran Tetap per Bulan (Rp - Opsional)</label>
                            <input type="number" id="saving-manual_deposit" placeholder="Kosongkan agar dihitung otomatis dari target" class="field-input w-full font-mono" oninput="runSimulation()">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-line flex items-center justify-between gap-2">
                        <button type="button" onclick="runSimulation()" class="btn-arus-secondary text-xs flex-1 min-h-[40px] cursor-pointer">
                            Hitung Ulang
                        </button>
                        <button type="button" onclick="openSaveModal()" class="btn-arus-primary text-xs flex-1 min-h-[40px] cursor-pointer">
                            💾 Simpan Skenario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Hasil Analisis Dampak & Kelayakan (7 Kolom) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Verdict Header Card -->
            <div id="verdict-card" class="panel-card border-l-4 border-l-emerald-500 p-5 bg-panel transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <span id="verdict-icon" class="text-2xl">✅</span>
                        <div>
                            <span id="verdict-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                SANGAT LAYAK &amp; AMAN
                            </span>
                            <h3 id="verdict-title" class="font-bold text-base text-charcoal mt-0.5">
                                Kondisi Finansial Siap &amp; Terkendali
                            </h3>
                        </div>
                    </div>
                    <div class="text-right sm:border-l sm:border-line sm:pl-4 shrink-0">
                        <span class="text-[10px] uppercase font-bold text-muted block">Skor Kelayakan</span>
                        <span id="verdict-score" class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                            95<span class="text-xs text-muted font-normal">/100</span>
                        </span>
                    </div>
                </div>
                <p id="verdict-summary" class="text-xs text-muted leading-relaxed">
                    Keputusan ini tidak mengganggu kebutuhan rutin dan masih menyisakan arus kas bebas yang sehat.
                </p>
            </div>

            <!-- Perbandingan Sebelum vs Sesudah (Side-by-Side Comparison) -->
            <div class="panel-card p-4 sm:p-5">
                <h4 class="font-bold text-xs uppercase tracking-wider text-forest mb-3">
                    Perbandingan Sebelum vs Sesudah Keputusan
                </h4>

                <div class="space-y-3">
                    <!-- 1. Arus Kas Bersih (Free Cashflow) -->
                    <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-charcoal">Sisa Arus Kas Bulanan (Free Cash Flow)</span>
                            <span id="cmp-fcf-delta" class="text-xs font-bold text-rose-600 font-mono">-Rp 1.385.000</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-xs font-mono pt-1">
                            <div>
                                <span class="text-[10px] text-muted block uppercase">Sebelumnya</span>
                                <span id="cmp-fcf-before" class="font-bold text-charcoal">Rp 0</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-muted block uppercase">Setelah Keputusan</span>
                                <span id="cmp-fcf-after" class="font-bold text-emerald-600">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Debt Service Ratio (DSR) -->
                    <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-charcoal">Rasio Beban Cicilan (DSR)</span>
                            <span id="cmp-dsr-delta" class="text-xs font-bold text-amber-600 font-mono">+12.5%</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-xs font-mono pt-1">
                            <div>
                                <span class="text-[10px] text-muted block uppercase">Sebelumnya</span>
                                <span id="cmp-dsr-before" class="font-bold text-charcoal">0.0%</span>
                            </div>
                            <div>
                                <span id="cmp-dsr-after" class="font-bold text-forest">12.5% (Aman &le; 30%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Ketahanan Dana Darurat (Runway) -->
                    <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-charcoal">Ketahanan Dana Darurat (Runway)</span>
                            <span id="cmp-runway-delta" class="text-xs font-bold text-muted font-mono">-1.2 bln</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-xs font-mono pt-1">
                            <div>
                                <span class="text-[10px] text-muted block uppercase">Sebelumnya</span>
                                <span id="cmp-runway-before" class="font-bold text-charcoal">0 bln</span>
                            </div>
                            <div>
                                <span id="cmp-runway-after" class="font-bold text-forest">0 bln</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Saldo Kas Likuid Tersisa -->
                    <div class="p-3 rounded-xl bg-sidebar/50 border border-line">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-charcoal">Saldo Kas Likuid (Setelah DP/Tunai)</span>
                            <span id="cmp-cash-delta" class="text-xs font-bold text-rose-600 font-mono">-Rp 7.000.000</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-xs font-mono pt-1">
                            <div>
                                <span class="text-[10px] text-muted block uppercase">Sebelumnya</span>
                                <span id="cmp-cash-before" class="font-bold text-charcoal">Rp 0</span>
                            </div>
                            <div>
                                <span id="cmp-cash-after" class="font-bold text-charcoal">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rekomendasi Finansial & Mitigasi Risiko -->
            <div class="panel-card p-4 sm:p-5">
                <h4 class="font-bold text-xs uppercase tracking-wider text-forest mb-2 flex items-center gap-1.5">
                    <span>💡 Rekomendasi &amp; Pertimbangan Ahli</span>
                </h4>
                <ul id="recommendations-list" class="space-y-2 text-xs text-charcoal list-disc pl-4 leading-relaxed">
                    <!-- Diisi lewat JS -->
                </ul>
            </div>
        </div>
    </div>

    <!-- 3. Riwayat Skenario Tersimpan -->
    <div id="saved-simulations-section" class="panel-card p-0 overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-4 border-b border-line flex flex-wrap items-center justify-between gap-2">
            <div>
                <span class="text-sm font-bold text-forest">Daftar Skenario Keputusan Tersimpan</span>
                <span class="text-xs text-muted block">Bandingkan atau tinjau kembali simulasi yang pernah Anda buat</span>
            </div>
            <span class="text-xs text-muted">{{ $simulations->count() }} skenario</span>
        </div>

        @if ($simulations->isEmpty())
            <div class="p-8 text-center text-xs text-muted">
                Belum ada simulasi keputusan yang disimpan. Buat kalkulasi di atas dan klik tombol <strong>💾 Simpan Skenario</strong> untuk menyimpannya.
            </div>
        @else
            <div class="divide-y divide-line">
                @foreach ($simulations as $sim)
                    @php
                        $res = $sim->results;
                        $status = $sim->healthStatus();
                        $badgeBg = $status === 'safe' 
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' 
                            : ($status === 'warning' 
                                ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' 
                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300');
                    @endphp
                    <div class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-cream/20 transition">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-sm text-charcoal">{{ $sim->title }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $badgeBg }}">
                                    {{ $sim->healthStatusLabel() }}
                                </span>
                                <span class="text-[10px] text-muted bg-cream px-2 py-0.5 rounded border border-line">
                                    {{ $sim->scenarioTypeLabel() }}
                                </span>
                            </div>
                            @if ($sim->description)
                                <p class="text-xs text-muted line-clamp-1">{{ $sim->description }}</p>
                            @endif
                            <div class="flex items-center gap-4 text-[11px] text-muted pt-0.5 flex-wrap font-mono">
                                <span>Dampak Bulanan: <strong>Rp {{ number_format($res['summary']['total_monthly_impact'] ?? 0, 0, ',', '.') }}</strong></span>
                                <span>DSR Sesudah: <strong>{{ $res['comparison']['after']['dsr'] ?? 0 }}%</strong></span>
                                <span>Sisa FCF: <strong>Rp {{ number_format($res['comparison']['after']['free_cashflow'] ?? 0, 0, ',', '.') }}</strong></span>
                                <span class="text-[10px] text-muted font-sans">{{ $sim->created_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                type="button" 
                                onclick='loadSimulationToForm(@json($sim))' 
                                class="btn-arus-secondary text-xs h-9 px-3 cursor-pointer inline-flex items-center gap-1"
                                title="Muat skenario ini ke kalkulator"
                            >
                                <span>Muat</span>
                            </button>

                            <form method="POST" action="{{ route('simulations.destroy', $sim) }}" class="inline" onsubmit="return confirm('Hapus skenario simulasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    class="w-9 h-9 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 transition inline-flex items-center justify-center cursor-pointer"
                                    title="Hapus Skenario"
                                    aria-label="Hapus Skenario"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal Simpan Skenario -->
    <div id="save-simulation-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-2xl my-3 sm:my-0">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-serif text-xl text-forest font-normal mb-1">Simpan Skenario Keputusan</h3>
                    <p class="text-xs text-muted">Beri judul agar mudah diingat dan dibandingkan nantinya.</p>
                </div>
                <button type="button" onclick="document.getElementById('save-simulation-modal').classList.add('hidden')" class="w-8 h-8 rounded-lg text-muted hover:text-charcoal transition cursor-pointer inline-flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('simulations.store') }}" id="save-simulation-form" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="scenario_type" id="save-scenario_type" value="asset_purchase">
                <input type="hidden" name="baseline_income" id="save-baseline_income">
                <input type="hidden" name="baseline_expense" id="save-baseline_expense">
                <input type="hidden" name="baseline_debt" id="save-baseline_debt">
                <input type="hidden" name="baseline_liquid_cash" id="save-baseline_liquid_cash">
                <div id="save-hidden-parameters"></div>

                <div>
                    <label class="block font-bold text-charcoal mb-1.5 uppercase tracking-wider">Judul Skenario</label>
                    <input type="text" name="title" id="save-title" placeholder="Contoh: Beli Motor NMAX Kredit 3 Tahun" required class="field-input w-full text-xs">
                </div>

                <div>
                    <label class="block font-bold text-charcoal mb-1.5 uppercase tracking-wider">Catatan Tambahan (Opsional)</label>
                    <textarea name="description" id="save-description" rows="3" placeholder="Contoh: Perbandingan DP 20% vs DP 30% dari tabungan gaji ke-13" class="field-input w-full text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="document.getElementById('save-simulation-modal').classList.add('hidden')" class="btn-arus-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary">
                        Simpan Skenario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const defaultBaseline = @json($baseline);
        let currentScenarioType = '{{ $defaultScenarioType }}';
        let latestResult = @json($initialResult);

        function formatRp(val) {
            const num = Math.round(Number(val) || 0);
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        function switchScenarioType(type) {
            currentScenarioType = type;
            document.getElementById('param-scenario_type').value = type;

            // Update tab styles
            document.querySelectorAll('.scenario-tab-btn').forEach(btn => {
                btn.classList.remove('bg-forest', 'text-white');
                btn.classList.add('text-muted');
            });
            const activeBtn = document.getElementById('tab-btn-' + type);
            activeBtn.classList.remove('text-muted');
            activeBtn.classList.add('bg-forest', 'text-white');

            // Toggle fields
            document.getElementById('fields-asset_purchase').classList.add('hidden');
            document.getElementById('fields-loan_installment').classList.add('hidden');
            document.getElementById('fields-saving_target').classList.add('hidden');

            document.getElementById('fields-' + type).classList.remove('hidden');

            runSimulation();
        }

        function toggleAssetPaymentMethod(method) {
            const creditSection = document.getElementById('asset-credit-details');
            if (method === 'cash') {
                creditSection.classList.add('hidden');
            } else {
                creditSection.classList.remove('hidden');
            }
            runSimulation();
        }

        function setAssetDpPercent(percent) {
            const price = Number(document.getElementById('asset-purchase_price').value) || 0;
            const dp = Math.round(price * (percent / 100));
            document.getElementById('asset-down_payment').value = dp;
            runSimulation();
        }

        function getActiveParameters() {
            if (currentScenarioType === 'asset_purchase') {
                const methodRadio = document.querySelector('input[name="asset_payment_method"]:checked');
                const method = methodRadio ? methodRadio.value : 'credit';
                return {
                    purchase_price: Number(document.getElementById('asset-purchase_price').value) || 0,
                    payment_method: method,
                    down_payment: Number(document.getElementById('asset-down_payment').value) || 0,
                    tenor_months: Number(document.getElementById('asset-tenor_months').value) || 12,
                    interest_rate_p_a: Number(document.getElementById('asset-interest_rate').value) || 0,
                    monthly_installment: Number(document.getElementById('asset-manual_installment').value) || 0,
                    running_cost_monthly: Number(document.getElementById('asset-running_cost').value) || 0,
                };
            } else if (currentScenarioType === 'loan_installment') {
                return {
                    loan_amount: Number(document.getElementById('loan-amount').value) || 0,
                    tenor_months: Number(document.getElementById('loan-tenor_months').value) || 12,
                    interest_rate_p_a: Number(document.getElementById('loan-interest_rate').value) || 0,
                    monthly_installment: Number(document.getElementById('loan-manual_installment').value) || 0,
                    admin_fee: Number(document.getElementById('loan-admin_fee').value) || 0,
                    admin_fee_deducted: document.getElementById('loan-admin_deducted').checked,
                };
            } else if (currentScenarioType === 'saving_target') {
                return {
                    target_amount: Number(document.getElementById('saving-target_amount').value) || 0,
                    target_months: Number(document.getElementById('saving-target_months').value) || 12,
                    annual_return_rate: Number(document.getElementById('saving-return_rate').value) || 0,
                    monthly_deposit: Number(document.getElementById('saving-manual_deposit').value) || 0,
                };
            }
            return {};
        }

        function runSimulation() {
            const bIncome = Number(document.getElementById('input-base-income').value) || 0;
            const bExpense = Number(document.getElementById('input-base-expense').value) || 0;
            const bDebt = Number(document.getElementById('input-base-debt').value) || 0;
            const bLiquid = Number(document.getElementById('input-base-liquid').value) || 0;

            // Update baseline overview badges
            document.getElementById('disp-base-income').innerText = formatRp(bIncome);
            document.getElementById('disp-base-expense').innerText = formatRp(bExpense);
            document.getElementById('disp-base-debt').innerText = formatRp(bDebt);
            const dsrVal = bIncome > 0 ? (bDebt / bIncome * 100).toFixed(1) : '0.0';
            document.getElementById('disp-base-dsr').innerText = '(' + dsrVal + '%)';
            document.getElementById('disp-base-liquid').innerText = formatRp(bLiquid);

            const params = getActiveParameters();

            fetch('{{ route("simulations.simulate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    scenario_type: currentScenarioType,
                    baseline_income: bIncome,
                    baseline_expense: bExpense,
                    baseline_debt: bDebt,
                    baseline_liquid_cash: bLiquid,
                    parameters: params
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.result) {
                    latestResult = data.result;
                    renderResult(data.result);
                }
            })
            .catch(err => console.error(err));
        }

        function renderResult(res) {
            const verdictCard = document.getElementById('verdict-card');
            const verdictBadge = document.getElementById('verdict-badge');
            const verdictScore = document.getElementById('verdict-score');
            const verdictTitle = document.getElementById('verdict-title');
            const verdictSummary = document.getElementById('verdict-summary');
            const verdictIcon = document.getElementById('verdict-icon');

            verdictCard.classList.remove('border-l-emerald-500', 'border-l-amber-500', 'border-l-rose-500');
            verdictBadge.classList.remove('bg-emerald-100', 'text-emerald-800', 'bg-amber-100', 'text-amber-800', 'bg-rose-100', 'text-rose-800', 'dark:bg-emerald-950/60', 'dark:text-emerald-300', 'dark:bg-amber-950/60', 'dark:text-amber-300', 'dark:bg-rose-950/60', 'dark:text-rose-300');
            verdictScore.classList.remove('text-emerald-600', 'text-amber-600', 'text-rose-600', 'dark:text-emerald-400', 'dark:text-amber-400', 'dark:text-rose-400');

            if (res.health_status === 'safe') {
                verdictCard.classList.add('border-l-emerald-500');
                verdictBadge.classList.add('bg-emerald-100', 'text-emerald-800', 'dark:bg-emerald-950/60', 'dark:text-emerald-300');
                verdictBadge.innerText = 'SANGAT LAYAK & AMAN';
                verdictScore.classList.add('text-emerald-600', 'dark:text-emerald-400');
                verdictIcon.innerText = '✅';
            } else if (res.health_status === 'warning') {
                verdictCard.classList.add('border-l-amber-500');
                verdictBadge.classList.add('bg-amber-100', 'text-amber-800', 'dark:bg-amber-950/60', 'dark:text-amber-300');
                verdictBadge.innerText = 'CUKUP LAYAK (WASPADA)';
                verdictScore.classList.add('text-amber-600', 'dark:text-amber-400');
                verdictIcon.innerText = '⚠️';
            } else {
                verdictCard.classList.add('border-l-rose-500');
                verdictBadge.classList.add('bg-rose-100', 'text-rose-800', 'dark:bg-rose-950/60', 'dark:text-rose-300');
                verdictBadge.innerText = 'BERISIKO TINGGI';
                verdictScore.classList.add('text-rose-600', 'dark:text-rose-400');
                verdictIcon.innerText = '⛔';
            }

            verdictScore.innerHTML = res.health_score + '<span class="text-xs text-muted font-normal">/100</span>';
            verdictTitle.innerText = res.verdict_title;
            verdictSummary.innerText = res.verdict_summary;

            // Comparators
            const cmp = res.comparison;
            const delta = cmp.delta;

            // FCF
            const fcfAfter = cmp.after.free_cashflow;
            document.getElementById('cmp-fcf-before').innerText = formatRp(cmp.before.free_cashflow);
            const elFcfAfter = document.getElementById('cmp-fcf-after');
            elFcfAfter.innerText = formatRp(fcfAfter);
            elFcfAfter.className = 'font-bold ' + (fcfAfter < 0 ? 'text-red font-mono' : 'text-forest font-mono');
            document.getElementById('cmp-fcf-delta').innerText = (delta.free_cashflow >= 0 ? '+' : '') + formatRp(delta.free_cashflow);

            // DSR
            const dsrAfter = cmp.after.dsr;
            document.getElementById('cmp-dsr-before').innerText = cmp.before.dsr + '%';
            const elDsrAfter = document.getElementById('cmp-dsr-after');
            elDsrAfter.innerText = dsrAfter + '% ' + (dsrAfter <= 30 ? '(Aman)' : (dsrAfter <= 40 ? '(Waspada)' : '(Overleveraged)'));
            elDsrAfter.className = 'font-bold font-mono ' + (dsrAfter <= 30 ? 'text-forest' : (dsrAfter <= 40 ? 'text-amber-600' : 'text-red'));
            document.getElementById('cmp-dsr-delta').innerText = (delta.dsr >= 0 ? '+' : '') + delta.dsr + '%';

            // Runway
            document.getElementById('cmp-runway-before').innerText = cmp.before.runway_months + ' bln';
            document.getElementById('cmp-runway-after').innerText = cmp.after.runway_months + ' bln';
            document.getElementById('cmp-runway-delta').innerText = (delta.runway_months >= 0 ? '+' : '') + delta.runway_months + ' bln';

            // Cash
            document.getElementById('cmp-cash-before').innerText = formatRp(cmp.before.liquid_cash);
            document.getElementById('cmp-cash-after').innerText = formatRp(cmp.after.liquid_cash);
            document.getElementById('cmp-cash-delta').innerText = (delta.liquid_cash >= 0 ? '+' : '') + formatRp(delta.liquid_cash);

            // Recommendations List
            const listEl = document.getElementById('recommendations-list');
            listEl.innerHTML = '';
            const allPoints = [...(res.recommendations || []), ...(res.considerations || [])];
            if (allPoints.length === 0) {
                listEl.innerHTML = '<li class="text-muted">Tidak ada catatan risiko kritis. Rencana ini aman untuk dieksekusi.</li>';
            } else {
                allPoints.forEach(pt => {
                    const li = document.createElement('li');
                    li.innerText = pt;
                    listEl.appendChild(li);
                });
            }
        }

        function resetBaselineToDefault() {
            document.getElementById('input-base-income').value = Math.round(defaultBaseline.monthly_income);
            document.getElementById('input-base-expense').value = Math.round(defaultBaseline.monthly_expense);
            document.getElementById('input-base-debt').value = Math.round(defaultBaseline.monthly_debt);
            document.getElementById('input-base-liquid').value = Math.round(defaultBaseline.liquid_cash);
            runSimulation();
        }

        function openSaveModal() {
            document.getElementById('save-scenario_type').value = currentScenarioType;
            document.getElementById('save-baseline_income').value = document.getElementById('input-base-income').value;
            document.getElementById('save-baseline_expense').value = document.getElementById('input-base-expense').value;
            document.getElementById('save-baseline_debt').value = document.getElementById('input-base-debt').value;
            document.getElementById('save-baseline_liquid_cash').value = document.getElementById('input-base-liquid').value;

            // Inject parameters as hidden inputs
            const container = document.getElementById('save-hidden-parameters');
            container.innerHTML = '';
            const params = getActiveParameters();
            for (const [key, val] of Object.entries(params)) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'parameters[' + key + ']';
                hiddenInput.value = val;
                container.appendChild(hiddenInput);
            }

            document.getElementById('save-simulation-modal').classList.remove('hidden');
        }

        function loadSimulationToForm(sim) {
            // Set scenario type
            switchScenarioType(sim.scenario_type);

            // Set baselines if stored
            if (sim.baseline_income) document.getElementById('input-base-income').value = Math.round(sim.baseline_income);
            if (sim.baseline_expense) document.getElementById('input-base-expense').value = Math.round(sim.baseline_expense);
            if (sim.baseline_debt) document.getElementById('input-base-debt').value = Math.round(sim.baseline_debt);
            if (sim.baseline_liquid_cash) document.getElementById('input-base-liquid').value = Math.round(sim.baseline_liquid_cash);

            const p = sim.parameters || {};
            if (sim.scenario_type === 'asset_purchase') {
                if (p.purchase_price) document.getElementById('asset-purchase_price').value = p.purchase_price;
                if (p.down_payment) document.getElementById('asset-down_payment').value = p.down_payment;
                if (p.tenor_months) document.getElementById('asset-tenor_months').value = p.tenor_months;
                if (p.interest_rate_p_a) document.getElementById('asset-interest_rate').value = p.interest_rate_p_a;
                if (p.monthly_installment) document.getElementById('asset-manual_installment').value = p.monthly_installment;
                if (p.running_cost_monthly) document.getElementById('asset-running_cost').value = p.running_cost_monthly;

                const isCash = (p.payment_method === 'cash');
                const radios = document.querySelectorAll('input[name="asset_payment_method"]');
                radios.forEach(r => r.checked = (r.value === (isCash ? 'cash' : 'credit')));
                toggleAssetPaymentMethod(isCash ? 'cash' : 'credit');
            } else if (sim.scenario_type === 'loan_installment') {
                if (p.loan_amount) document.getElementById('loan-amount').value = p.loan_amount;
                if (p.tenor_months) document.getElementById('loan-tenor_months').value = p.tenor_months;
                if (p.interest_rate_p_a) document.getElementById('loan-interest_rate').value = p.interest_rate_p_a;
                if (p.monthly_installment) document.getElementById('loan-manual_installment').value = p.monthly_installment;
                if (p.admin_fee) document.getElementById('loan-admin_fee').value = p.admin_fee;
                if (typeof p.admin_fee_deducted !== 'undefined') document.getElementById('loan-admin_deducted').checked = Boolean(p.admin_fee_deducted);
            } else if (sim.scenario_type === 'saving_target') {
                if (p.target_amount) document.getElementById('saving-target_amount').value = p.target_amount;
                if (p.target_months) document.getElementById('saving-target_months').value = p.target_months;
                if (p.annual_return_rate) document.getElementById('saving-return_rate').value = p.annual_return_rate;
                if (p.monthly_deposit) document.getElementById('saving-manual_deposit').value = p.monthly_deposit;
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
            runSimulation();
        }

        // Jalankan render awal saat halaman siap
        document.addEventListener('DOMContentLoaded', function () {
            if (latestResult) {
                renderResult(latestResult);
            }
        });
    </script>
</x-layouts.app>
