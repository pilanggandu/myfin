<x-layouts.app title="Catat Belanja Cicilan">
    <div class="w-full">
        <!-- Back Link & Heading -->
        <div class="mb-6">
            <a href="{{ route('credit.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-charcoal transition mb-3">
                <span>←</span>
                <span>Kembali ke Kredit & Cicilan</span>
            </a>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / TRANSAKSI KREDIT</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Catat Belanja Cicilan
            </h1>
            <p class="text-xs text-muted">
                Pengeluaran dicatat langsung pada tanggal transaksi belanja sesuai kategori; jadwal cicilan pokok dibuat tanpa menduplikasi beban di masa depan.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl border border-red/20 bg-red/10 text-red text-xs">
                <div class="font-bold mb-1">Periksa kembali data Anda:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($creditAccounts->isEmpty())
            <div class="panel-card p-8 text-center">
                <div class="text-4xl mb-3">💳</div>
                <h3 class="font-serif text-lg text-charcoal font-medium mb-1">Belum Ada Akun Kredit Aktif</h3>
                <p class="text-xs text-muted max-w-md mx-auto mb-5">
                    Anda perlu menambahkan minimal satu kartu kredit atau akun paylater sebelum dapat mencatat transaksi belanja cicilan.
                </p>
                <a 
                    href="{{ route('credit.index', ['tab' => 'accounts']) }}" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs"
                >
                    + Buat Akun Kredit Baru
                </a>
            </div>
        @else
            <form id="purchaseForm" method="POST" action="{{ route('credit.purchases.store') }}" class="space-y-6">
                @csrf

                <!-- Section 1: Akun Kredit & Kategori -->
                <div class="panel-card p-6 space-y-4">
                    <h3 class="font-serif text-base text-charcoal font-medium border-b border-line pb-2">
                        1. Sumber Pembiayaan & Kategori
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="credit_account_id" class="block text-xs font-semibold text-charcoal mb-1">
                                Kartu Kredit / PayLater <span class="text-red">*</span>
                            </label>
                            <select 
                                name="credit_account_id" 
                                id="credit_account_id" 
                                required 
                                onchange="onCreditAccountChange()"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                            >
                                <option value="">-- Pilih Kartu / Akun Kredit --</option>
                                @foreach ($creditAccounts as $acc)
                                    <option 
                                        value="{{ $acc->id }}" 
                                        data-available="{{ $acc->availableLimit() }}"
                                        data-limit="{{ $acc->credit_limit }}"
                                        data-billing-day="{{ $acc->billing_cycle_day ?? '' }}"
                                        data-due-day="{{ $acc->due_day ?? '' }}"
                                        {{ old('credit_account_id') == $acc->id ? 'selected' : '' }}
                                    >
                                        {{ $acc->name }} (Sisa Limit: {{ $acc->formatted_available_limit }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="creditLimitHint" class="text-[11px] text-muted mt-1"></div>
                        </div>

                        <div>
                            <label for="category_id" class="block text-xs font-semibold text-charcoal mb-1">
                                Kategori Belanja (Pengeluaran) <span class="text-red">*</span>
                            </label>
                            <select 
                                name="category_id" 
                                id="category_id" 
                                required 
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                            >
                                <option value="">-- Pilih Kategori Pengeluaran --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[10px] text-muted">Beban pengeluaran diakui pada kategori ini.</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-charcoal mb-1">
                                Nama Barang / Transaksi <span class="text-red">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                required 
                                value="{{ old('name') }}"
                                placeholder="Contoh: Beli Smartphone Baru, Tiket Pesawat"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                        </div>

                        <div>
                            <label for="member_id" class="block text-xs font-semibold text-charcoal mb-1">
                                Member Penanggung Jawab (Opsional)
                            </label>
                            <select 
                                name="member_id" 
                                id="member_id" 
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                            >
                                <option value="">-- Tanpa Member Tertentu --</option>
                                @foreach ($members as $m)
                                    <option value="{{ $m->id }}" {{ old('member_id') == $m->id ? 'selected' : '' }}>
                                        {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Nominal & Rencana Tenor -->
                <div class="panel-card p-6 space-y-4">
                    <h3 class="font-serif text-base text-charcoal font-medium border-b border-line pb-2">
                        2. Nominal & Rencana Tenor Cicilan
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="total_amount" class="block text-xs font-semibold text-charcoal mb-1">
                                Total Nominal Transaksi (Rp) <span class="text-red">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="total_amount" 
                                id="total_amount" 
                                required 
                                min="0.01"
                                step="any"
                                value="{{ old('total_amount', 1200000) }}"
                                placeholder="Contoh: 1200000 atau 150166.67"
                                oninput="updateSimulation()"
                                onfocus="this.select()"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono font-bold text-base focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                            <div id="amountLimitWarning" class="hidden text-xs text-red font-semibold mt-1">
                                ⚠️ Nominal melebihi sisa limit kartu yang tersedia!
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-charcoal mb-1">
                                Pilihan Tenor (Bulan) <span class="text-red">*</span>
                            </label>
                            <div class="flex items-center gap-1.5 mb-2">
                                @foreach ([1, 3, 6, 12, 24] as $t)
                                    <button 
                                        type="button" 
                                        onclick="setTenor({{ $t }})"
                                        class="tenor-btn flex-1 py-1.5 px-2 text-xs border border-line rounded-md text-charcoal font-semibold hover:bg-forest-light transition cursor-pointer {{ $t === 3 ? 'bg-forest text-white border-forest' : 'bg-panel' }}"
                                        data-tenor="{{ $t }}"
                                    >
                                        {{ $t }}x
                                    </button>
                                @endforeach
                            </div>
                            <input 
                                type="number" 
                                name="tenor" 
                                id="tenor" 
                                required 
                                min="1" 
                                max="60"
                                value="{{ old('tenor', 3) }}"
                                oninput="onCustomTenorChange()"
                                onfocus="this.select()"
                                class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="interest_rate" class="block text-xs font-semibold text-charcoal mb-1">
                                Suku Bunga Flat per Bulan (%) <span class="text-muted font-normal">(Opsional)</span>
                            </label>
                            <input 
                                type="number" 
                                name="interest_rate" 
                                id="interest_rate" 
                                step="0.01" 
                                min="0" 
                                max="100" 
                                value="{{ old('interest_rate', 0) }}"
                                placeholder="0 untuk promo cicilan 0%"
                                oninput="updateSimulation()"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                            <span class="text-[10px] text-muted">Contoh: 1.5 untuk bunga 1.5%/bulan, atau 0 untuk 0%.</span>
                        </div>

                        <div>
                            <label for="admin_fee" class="block text-xs font-semibold text-charcoal mb-1">
                                Biaya Admin / Penanganan (Rp) <span class="text-muted font-normal">(Opsional)</span>
                            </label>
                            <input 
                                type="number" 
                                name="admin_fee" 
                                id="admin_fee" 
                                min="0" 
                                step="any"
                                value="{{ old('admin_fee', 0) }}"
                                placeholder="Contoh: 15000"
                                oninput="updateSimulation()"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                            <span class="text-[10px] text-muted">Biaya admin dibagi rata ke seluruh tenor cicilan.</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-xs font-semibold text-charcoal mb-1">
                                Tanggal Transaksi Pembelian <span class="text-red">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="start_date" 
                                id="start_date" 
                                required 
                                value="{{ old('start_date', (app('currentSpace') ? app('currentSpace')->now() : now())->toDateString()) }}"
                                onchange="onStartDateChange()"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="first_due_date" class="block text-xs font-semibold text-charcoal">
                                    Tanggal Jatuh Tempo Pertama
                                </label>
                                <button type="button" onclick="autoSetDueDate(true)" class="text-[10px] text-forest dark:text-emerald-400 font-semibold hover:underline cursor-pointer">
                                    Hitung Ulang dari Siklus Kartu
                                </button>
                            </div>
                            <input 
                                type="date" 
                                name="first_due_date" 
                                id="first_due_date" 
                                value="{{ old('first_due_date') }}"
                                onchange="onManualDueDateChange()"
                                class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                            >
                            <span id="firstDueHelperText" class="text-[10px] text-muted block mt-1">
                                Dihitung otomatis sesuai siklus cetak tagihan & jatuh tempo kartu.
                            </span>
                        </div>
                    </div>

                    <!-- Checkbox Lunas Pra-Pencatatan -->
                    <div class="p-3.5 rounded-xl border border-line bg-cream/40">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="mark_past_paid" 
                                id="mark_past_paid" 
                                value="1" 
                                {{ old('mark_past_paid') ? 'checked' : '' }}
                                class="mt-0.5 rounded border-line text-forest focus:ring-forest cursor-pointer"
                            >
                            <div>
                                <span class="text-xs font-bold text-charcoal block">Tandai Cicilan Lampau Sebagai Lunas (Pra-Pencatatan ARUS)</span>
                                <span class="text-[11px] text-muted block leading-relaxed mt-0.5">
                                    Centang jika transaksi ini dimulai beberapa bulan lalu dan cicilan sebelum bulan ini sebenarnya telah Anda lunasi di dunia nyata. Cicilan lampau akan langsung berstatus lunas tanpa mengurangi saldo rekening kas/bank Anda saat ini.
                                </span>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold text-charcoal mb-1">Catatan Tambahan (Opsional)</label>
                        <textarea 
                            name="notes" 
                            id="notes" 
                            rows="2" 
                            placeholder="Keterangan toko, nomor invoice, atau detail promo tanpa bunga..."
                            class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Section 3: Live Preview Simulasi Cicilan -->
                <div class="panel-card p-6">
                    <div class="flex items-center justify-between border-b border-line pb-3 mb-4">
                        <div>
                            <h3 class="font-serif text-base text-charcoal font-medium">
                                3. Simulasi Jadwal Pelunasan Pokok & Biaya
                            </h3>
                            <p class="text-xs text-muted">
                                Rincian pembagian cicilan pokok, bunga flat, dan biaya admin presisi per bulan.
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] text-muted block uppercase">Estimasi Angsuran / Bln</span>
                            <span id="monthlyEstimateText" class="font-serif text-lg font-bold text-forest">Rp 400.000 / bln</span>
                        </div>
                    </div>

                    <!-- Breakdown Mini Badges -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 text-xs">
                        <div class="p-2.5 rounded-lg bg-cream/40 border border-line">
                            <span class="text-muted block text-[10px] uppercase font-bold">Total Pokok</span>
                            <span id="previewTotalPrincipal" class="font-mono font-bold text-charcoal">Rp 0</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-cream/40 border border-line">
                            <span class="text-muted block text-[10px] uppercase font-bold">Total Bunga</span>
                            <span id="previewTotalInterest" class="font-mono font-bold text-amber-600 dark:text-amber-400">Rp 0</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-cream/40 border border-line">
                            <span class="text-muted block text-[10px] uppercase font-bold">Total Biaya Admin</span>
                            <span id="previewTotalFee" class="font-mono font-bold text-charcoal">Rp 0</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-forest-light/60 border border-forest/20">
                            <span class="text-forest block text-[10px] uppercase font-bold">Grand Total</span>
                            <span id="previewGrandTotal" class="font-mono font-bold text-forest">Rp 0</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="border-b border-line text-muted">
                                    <th class="py-2 px-3 font-semibold">Cicilan Ke-</th>
                                    <th class="py-2 px-3 font-semibold">Jatuh Tempo</th>
                                    <th class="py-2 px-3 font-semibold text-right">Pokok</th>
                                    <th class="py-2 px-3 font-semibold text-right">Bunga</th>
                                    <th class="py-2 px-3 font-semibold text-right">Biaya Admin</th>
                                    <th class="py-2 px-3 font-semibold text-right">Total Tagihan</th>
                                </tr>
                            </thead>
                            <tbody id="simulationTableBody" class="divide-y divide-line/60">
                                <!-- Generated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a 
                        href="{{ route('credit.index') }}" 
                        class="min-h-[44px] px-5 py-2.5 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition flex items-center justify-center"
                    >
                        Batal
                    </a>
                    <button 
                        type="submit" 
                        id="btnSubmitPurchase"
                        class="min-h-[44px] px-6 py-2.5 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer flex items-center gap-2 shadow-xs"
                    >
                        <span id="btnSubmitPurchaseText">Simpan Transaksi Belanja & Rencana Cicilan</span>
                    </button>
                </div>
            </form>
        @endif
    </div>

    <script>
        let isDueDateManuallyEdited = {{ old('first_due_date') ? 'true' : 'false' }};

        function onStartDateChange() {
            autoSetDueDate(false);
            updateSimulation();
        }

        function onManualDueDateChange() {
            isDueDateManuallyEdited = true;
            updateSimulation();
        }

        function calculateDueDate(startDateStr, billingDay, dueDay) {
            if (!startDateStr) return '';
            const parts = startDateStr.split('-');
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);

            const bDay = billingDay ? parseInt(billingDay, 10) : null;
            const dDay = dueDay ? parseInt(dueDay, 10) : null;

            let targetYear = year;
            let targetMonth = month;
            let targetDay = dDay || day;

            if (bDay) {
                if (day <= bDay) {
                    if (dDay && dDay > bDay) {
                        targetMonth = month;
                        targetDay = dDay;
                    } else {
                        targetMonth = month + 1;
                        targetDay = dDay || bDay;
                    }
                } else {
                    if (dDay && dDay > bDay) {
                        targetMonth = month + 1;
                        targetDay = dDay;
                    } else {
                        targetMonth = month + 2;
                        targetDay = dDay || bDay;
                    }
                }
            } else if (dDay) {
                targetMonth = month + 1;
                targetDay = dDay;
            } else {
                targetMonth = month + 1;
                targetDay = day;
            }

            const calculatedDate = new Date(targetYear, targetMonth, 1);
            const daysInTargetMonth = new Date(calculatedDate.getFullYear(), calculatedDate.getMonth() + 1, 0).getDate();
            const finalDay = Math.min(targetDay, daysInTargetMonth);
            const finalDate = new Date(calculatedDate.getFullYear(), calculatedDate.getMonth(), finalDay);

            const y = finalDate.getFullYear();
            const m = String(finalDate.getMonth() + 1).padStart(2, '0');
            const d = String(finalDate.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function autoSetDueDate(force = false) {
            if (force) {
                isDueDateManuallyEdited = false;
            }
            const select = document.getElementById('credit_account_id');
            const selected = select ? select.options[select.selectedIndex] : null;
            const startDateVal = document.getElementById('start_date')?.value;
            const firstDueInput = document.getElementById('first_due_date');
            const helperText = document.getElementById('firstDueHelperText');

            if (!selected || !selected.value || !startDateVal || !firstDueInput) {
                return;
            }

            const billingDay = selected.getAttribute('data-billing-day');
            const dueDay = selected.getAttribute('data-due-day');

            if (billingDay || dueDay) {
                let cycleInfo = [];
                if (billingDay) cycleInfo.push('Cetak tgl ' + billingDay);
                if (dueDay) cycleInfo.push('Jatuh tempo tgl ' + dueDay);
                if (helperText) {
                    helperText.textContent = 'Siklus kartu: ' + cycleInfo.join(' • ') + ' (Dihitung otomatis).';
                }
            } else {
                if (helperText) {
                    helperText.textContent = 'Kartu belum diset tanggal cetak & jatuh tempo default. Tanggal dihitung 1 bulan berikutnya.';
                }
            }

            if (!isDueDateManuallyEdited || force || !firstDueInput.value) {
                const calculated = calculateDueDate(startDateVal, billingDay, dueDay);
                if (calculated) {
                    firstDueInput.value = calculated;
                }
            }
        }

        function onCreditAccountChange() {
            const select = document.getElementById('credit_account_id');
            const selected = select.options[select.selectedIndex];
            const hint = document.getElementById('creditLimitHint');

            if (!selected || !selected.value) {
                hint.textContent = '';
                checkLimit();
                return;
            }

            const available = parseInt(selected.getAttribute('data-available') || '0', 10);
            const billingDay = selected.getAttribute('data-billing-day');
            const dueDay = selected.getAttribute('data-due-day');

            let hintText = 'Sisa limit kartu tersedia: Rp ' + available.toLocaleString('id-ID');
            if (billingDay || dueDay) {
                hintText += ' • Siklus: Cetak tgl ' + (billingDay || '-') + ', Jatuh tempo tgl ' + (dueDay || '-');
            }
            hint.textContent = hintText;

            checkLimit();
            autoSetDueDate(false);
            updateSimulation();
        }

        function checkLimit() {
            const select = document.getElementById('credit_account_id');
            const selected = select.options[select.selectedIndex];
            const warning = document.getElementById('amountLimitWarning');
            const btn = document.getElementById('btnSubmitPurchase');

            if (!selected || !selected.value) {
                warning.classList.add('hidden');
                btn.disabled = false;
                return;
            }

            const available = parseInt(selected.getAttribute('data-available') || '0', 10);
            const total = parseInt(document.getElementById('total_amount').value || '0', 10);

            if (total > available) {
                warning.classList.remove('hidden');
            } else {
                warning.classList.add('hidden');
            }
        }

        function setTenor(t) {
            document.getElementById('tenor').value = t;
            document.querySelectorAll('.tenor-btn').forEach(b => {
                if (parseInt(b.getAttribute('data-tenor'), 10) === t) {
                    b.classList.add('bg-forest', 'text-white', 'border-forest');
                    b.classList.remove('bg-panel', 'text-charcoal');
                } else {
                    b.classList.remove('bg-forest', 'text-white', 'border-forest');
                    b.classList.add('bg-panel', 'text-charcoal');
                }
            });
            updateSimulation();
        }

        function onCustomTenorChange() {
            const custom = parseInt(document.getElementById('tenor').value || '1', 10);
            document.querySelectorAll('.tenor-btn').forEach(b => {
                if (parseInt(b.getAttribute('data-tenor'), 10) === custom) {
                    b.classList.add('bg-forest', 'text-white', 'border-forest');
                    b.classList.remove('bg-panel', 'text-charcoal');
                } else {
                    b.classList.remove('bg-forest', 'text-white', 'border-forest');
                    b.classList.add('bg-panel', 'text-charcoal');
                }
            });
            updateSimulation();
        }

        function updateSimulation() {
            checkLimit();
            const total = parseInt(document.getElementById('total_amount').value || '0', 10);
            const tenor = Math.max(1, parseInt(document.getElementById('tenor').value || '1', 10));
            const interestRate = parseFloat(document.getElementById('interest_rate')?.value || '0');
            const adminFee = parseInt(document.getElementById('admin_fee')?.value || '0', 10);
            const startDateVal = document.getElementById('start_date').value;
            const firstDueVal = document.getElementById('first_due_date').value;

            const basePrincipal = Math.floor(total / tenor);
            const remainderPrincipal = total % tenor;

            const monthlyInterest = Math.round(total * (interestRate / 100));
            const totalInterest = monthlyInterest * tenor;

            const baseFee = Math.floor(adminFee / tenor);
            const remainderFee = adminFee % tenor;

            const grandTotal = total + totalInterest + adminFee;
            const avgMonthly = Math.round(grandTotal / tenor);

            document.getElementById('monthlyEstimateText').textContent = 'Rp ' + avgMonthly.toLocaleString('id-ID') + ' / bln';
            document.getElementById('previewTotalPrincipal').textContent = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('previewTotalInterest').textContent = 'Rp ' + totalInterest.toLocaleString('id-ID');
            document.getElementById('previewTotalFee').textContent = 'Rp ' + adminFee.toLocaleString('id-ID');
            document.getElementById('previewGrandTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

            const tbody = document.getElementById('simulationTableBody');
            tbody.innerHTML = '';

            let baseDate = startDateVal ? new Date(startDateVal) : new Date();
            let firstDue = firstDueVal ? new Date(firstDueVal) : new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, baseDate.getDate());

            const select = document.getElementById('credit_account_id');
            const selected = select ? select.options[select.selectedIndex] : null;
            const cardDueDay = selected ? parseInt(selected.getAttribute('data-due-day') || '0', 10) : 0;
            const targetDay = cardDueDay > 0 ? cardDueDay : firstDue.getDate();

            for (let i = 1; i <= tenor; i++) {
                const principal = basePrincipal + (i === 1 ? remainderPrincipal : 0);
                const fee = baseFee + (i === 1 ? remainderFee : 0);
                const totalInst = principal + monthlyInterest + fee;

                let targetMonth = firstDue.getMonth() + (i - 1);
                let tempDate = new Date(firstDue.getFullYear(), targetMonth, 1);
                let maxDays = new Date(tempDate.getFullYear(), tempDate.getMonth() + 1, 0).getDate();
                let actualDay = Math.min(targetDay, maxDays);
                let dueDate = new Date(tempDate.getFullYear(), tempDate.getMonth(), actualDay);

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-panel transition';
                tr.innerHTML = `
                    <td class="py-2.5 px-3 font-semibold text-charcoal">Cicilan #${i}</td>
                    <td class="py-2.5 px-3 text-muted">${dueDate.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                    <td class="py-2.5 px-3 text-right font-mono font-medium text-charcoal">Rp ${principal.toLocaleString('id-ID')}</td>
                    <td class="py-2.5 px-3 text-right font-mono text-muted">Rp ${monthlyInterest.toLocaleString('id-ID')}</td>
                    <td class="py-2.5 px-3 text-right font-mono text-muted">Rp ${fee.toLocaleString('id-ID')}</td>
                    <td class="py-2.5 px-3 text-right font-mono font-bold text-forest">Rp ${totalInst.toLocaleString('id-ID')}</td>
                `;
                tbody.appendChild(tr);
            }
        }

        document.getElementById('purchaseForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitPurchase');
            const txt = document.getElementById('btnSubmitPurchaseText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Menyimpan...';
        });

        document.addEventListener('DOMContentLoaded', function() {
            onCreditAccountChange();
            updateSimulation();
        });
    </script>
</x-layouts.app>
