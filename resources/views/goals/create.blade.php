<x-layouts.app title="Buat Target Tabungan Baru">
    <div class="w-full">
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / TARGET & BERSAMA</div>
            <h1 class="font-serif text-3xl text-forest dark:text-emerald-400 font-normal tracking-tight mt-1 mb-0.5">
                Buat Target Tabungan Baru
            </h1>
            <p class="text-xs text-muted">
                Tentukan impian finansial, target nominal, dan estimasi waktu pencapaian di ruang <strong>{{ $space?->name }}</strong>.
            </p>
        </div>

        <form method="POST" action="{{ route('goals.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="goalForm">
            @csrf

            <!-- Kolom Kiri: Form Input (2 Cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Utama Target -->
                <div class="panel-card p-5 sm:p-6 space-y-4">
                    <h2 class="text-sm font-bold text-forest dark:text-emerald-400 border-b border-line pb-2">
                        1. Informasi Target Finansial
                    </h2>

                    <div>
                        <label for="name" class="block text-xs font-bold text-charcoal mb-1">
                            Nama Target <span class="text-red">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="nameInput" 
                            value="{{ old('name') }}" 
                            required 
                            placeholder="Contoh: Dana Darurat 6 Bulan, Liburan ke Jepang, DP Rumah" 
                            class="field-input h-10 px-3 text-xs w-full @error('name') border-red @enderror"
                            oninput="updatePreview()"
                        />
                        @error('name')
                            <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Target Amount & Start Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="target_amount" class="block text-xs font-bold text-charcoal mb-1">
                                Nominal Target (Rp) <span class="text-red">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="target_amount" 
                                id="amountInput" 
                                value="{{ old('target_amount') }}" 
                                required 
                                placeholder="Contoh: 15.000.000" 
                                class="field-input h-10 px-3 text-sm font-mono font-bold w-full @error('target_amount') border-red @enderror"
                                oninput="updatePreview()"
                            />
                            @error('target_amount')
                                <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="start_date" class="block text-xs font-bold text-charcoal mb-1">
                                Tanggal Mulai <span class="text-red">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="start_date" 
                                id="startDateInput" 
                                value="{{ old('start_date', now()->toDateString()) }}" 
                                required 
                                class="field-input h-10 px-3 text-xs w-full @error('start_date') border-red @enderror"
                                onchange="updatePreview()"
                            />
                            @error('start_date')
                                <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Target Date (Tenggat Waktu) -->
                    <div>
                        <label for="target_date" class="block text-xs font-bold text-charcoal mb-1">
                            Target Tanggal Tercapai (Opsional)
                        </label>
                        <input 
                            type="date" 
                            name="target_date" 
                            id="targetDateInput" 
                            value="{{ old('target_date') }}" 
                            class="field-input h-10 px-3 text-xs w-full @error('target_date') border-red @enderror"
                            onchange="updatePreview()"
                        />
                        <p class="text-[10px] text-muted mt-1">
                            Bila diisi, ARUS akan menghitung rekomendasi nominal yang harus Anda sisihkan tiap bulannya secara otomatis.
                        </p>
                        @error('target_date')
                            <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ikon Emoji & Warna Tema -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1.5">Pilih Ikon Target</label>
                            <div class="flex flex-wrap gap-1.5">
                                @php
                                    $icons = ['🎯', '🏠', '✈️', '🚗', '💻', '🛡️', '💍', '🎓', '👶', '🏖️', '📱', '🏍️', '💵', '🎁'];
                                @endphp
                                @foreach ($icons as $ic)
                                    <button 
                                        type="button" 
                                        onclick="selectIcon('{{ $ic }}')" 
                                        class="w-8 h-8 rounded-lg bg-cream dark:bg-line/40 hover:bg-forest/20 text-lg flex items-center justify-center transition cursor-pointer select-icon-btn border border-transparent"
                                        data-icon="{{ $ic }}"
                                    >
                                        {{ $ic }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="icon" id="selectedIconInput" value="{{ old('icon', '🎯') }}" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1.5">Warna Tema Kartu</label>
                            <div class="flex flex-wrap gap-2 pt-1">
                                @php
                                    $colors = ['#174C40', '#256353', '#0284c7', '#7c3aed', '#db2777', '#ea580c', '#ca8a04', '#475569'];
                                @endphp
                                @foreach ($colors as $col)
                                    <button 
                                        type="button" 
                                        onclick="selectColor('{{ $col }}')" 
                                        class="w-7 h-7 rounded-full border-2 transition cursor-pointer select-color-btn"
                                        style="background-color: {{ $col }}; border-color: {{ old('color', '#174C40') === $col ? '#ffffff' : 'transparent' }};"
                                        data-color="{{ $col }}"
                                    ></button>
                                @endforeach
                            </div>
                            <input type="hidden" name="color" id="selectedColorInput" value="{{ old('color', '#174C40') }}" />
                        </div>
                    </div>
                </div>

                <!-- Alokasi Rekening & Setoran Awal -->
                <div class="panel-card p-5 sm:p-6 space-y-4">
                    <h2 class="text-sm font-bold text-forest dark:text-emerald-400 border-b border-line pb-2">
                        2. Rekening Penampung & Setoran Awal
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="account_id" class="block text-xs font-bold text-charcoal mb-1">
                                Rekening Penampung (Opsional)
                            </label>
                            <select name="account_id" id="accountSelect" class="field-select h-10 px-3 text-xs w-full" onchange="updatePreview()">
                                <option value="">Semua Rekening / Virtual</option>
                                @foreach ($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[10px] text-muted">Rekening bank / e-wallet tempat dana target ini disimpan.</span>
                        </div>

                        <div>
                            <label for="initial_deposit" class="block text-xs font-bold text-charcoal mb-1">
                                Setoran Awal (Rp, Opsional)
                            </label>
                            <input 
                                type="text" 
                                name="initial_deposit" 
                                id="initialDepositInput" 
                                value="{{ old('initial_deposit') }}" 
                                placeholder="Contoh: 1.000.000" 
                                class="field-input h-10 px-3 text-xs font-mono font-semibold w-full"
                                oninput="updatePreview()"
                            />
                            <span class="text-[10px] text-muted">Saldo awal yang sudah Anda miliki untuk target ini.</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal select-none">
                            <input type="checkbox" name="deduct_from_account" value="1" {{ old('deduct_from_account') ? 'checked' : '' }} class="rounded border-line text-forest focus:ring-forest" />
                            <span>Potong saldo rekening kas di atas otomatis untuk nominal setoran awal</span>
                        </label>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold text-charcoal mb-1">
                            Catatan / Rencana Target (Opsional)
                        </label>
                        <textarea 
                            name="notes" 
                            id="notesInput" 
                            rows="2" 
                            placeholder="Catatan tambahan, strategi menabung, atau alasan mengapa target ini penting..."
                            class="field-input p-3 text-xs w-full"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('goals.index') }}" class="btn-arus-secondary text-xs h-10 px-5">
                        Batal
                    </a>
                    <button type="submit" class="btn-arus-primary text-xs h-10 px-6">
                        Simpan Target Tabungan
                    </button>
                </div>
            </div>

            <!-- Kolom Kanan: Live Preview Card (1 Col) -->
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-4">
                    <div class="text-xs font-bold uppercase tracking-wider text-muted">Pratinjau Kartu Target</div>
                    
                    <div class="panel-card p-0 overflow-hidden border border-line shadow-sm">
                        <!-- Preview Color Bar -->
                        <div id="previewColorBar" class="h-2 w-full bg-[#174C40]"></div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <div id="previewIcon" class="w-12 h-12 rounded-xl bg-cream dark:bg-line/40 flex items-center justify-center text-2xl shadow-2xs">
                                        🎯
                                    </div>
                                    <div class="min-w-0">
                                        <h3 id="previewName" class="font-bold text-base text-charcoal truncate">
                                            Nama Target Anda
                                        </h3>
                                        <div id="previewAccount" class="text-[11px] text-muted flex items-center gap-1 mt-0.5">
                                            <span>💳</span>
                                            <span>Rekening: Belum dipilih</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-forest/10 text-forest dark:bg-forest/25 dark:text-emerald-300 border border-forest/20">
                                    Aktif
                                </span>
                            </div>

                            <!-- Preview Amount & Progress -->
                            <div class="mt-4 pt-3 border-t border-line/60">
                                <div class="flex items-baseline justify-between gap-2 mb-1.5 text-xs">
                                    <span class="text-muted">Terkumpul:</span>
                                    <div class="text-right">
                                        <span id="previewCurrent" class="font-mono text-base font-bold text-forest dark:text-emerald-400">
                                            Rp 0
                                        </span>
                                        <span id="previewTarget" class="text-[11px] text-muted"> / Rp 0</span>
                                    </div>
                                </div>

                                <div class="w-full h-2.5 rounded-full bg-cream dark:bg-line/60 overflow-hidden mb-2">
                                    <div id="previewProgressBar" class="h-full rounded-full transition-all duration-300 bg-[#174C40]" style="width: 0%;"></div>
                                </div>

                                <div class="flex items-center justify-between text-xs text-muted">
                                    <span id="previewPct" class="font-bold font-mono text-charcoal">0%</span>
                                    <span id="previewRemaining">Sisa: Rp 0</span>
                                </div>
                            </div>

                            <!-- Preview Smart Recommendation -->
                            <div id="previewRecBox" class="mt-4 p-3 rounded-xl bg-cream/40 dark:bg-line/30 border border-line text-xs space-y-1 hidden">
                                <div class="text-[10px] uppercase font-bold text-muted">Rekomendasi Cerdas</div>
                                <div class="font-mono text-sm font-bold text-forest dark:text-emerald-400" id="previewMonthlyRec">
                                    Rp 0 / bln
                                </div>
                                <div class="text-[10px] text-muted" id="previewRecSubtitle">
                                    Berdasarkan estimasi tenggat waktu
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-forest-light/30 dark:bg-forest-light/10 border border-forest/15 text-xs text-muted leading-relaxed">
                        <strong class="text-forest dark:text-emerald-400 block mb-1">💡 Tips Menabung:</strong>
                        Menetapkan tenggat tanggal akan membantu ARUS membagi target menjadi nominal menabung bulanan yang terukur dan tidak membebani arus kas bulanan Anda.
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function selectIcon(icon) {
            document.getElementById('selectedIconInput').value = icon;
            document.getElementById('previewIcon').textContent = icon;
            document.querySelectorAll('.select-icon-btn').forEach(btn => {
                if (btn.getAttribute('data-icon') === icon) {
                    btn.classList.add('border-forest', 'bg-forest/20');
                } else {
                    btn.classList.remove('border-forest', 'bg-forest/20');
                }
            });
        }

        function selectColor(color) {
            document.getElementById('selectedColorInput').value = color;
            document.getElementById('previewColorBar').style.backgroundColor = color;
            document.getElementById('previewProgressBar').style.backgroundColor = color;
            document.querySelectorAll('.select-color-btn').forEach(btn => {
                btn.style.borderColor = btn.getAttribute('data-color') === color ? '#ffffff' : 'transparent';
            });
        }

        function parseRupiah(val) {
            if (!val) return 0;
            var clean = val.toString().replace(/[^0-9]/g, '');
            return parseInt(clean, 10) || 0;
        }

        function updatePreview() {
            var name = document.getElementById('nameInput').value.trim() || 'Nama Target Anda';
            document.getElementById('previewName').textContent = name;

            var target = parseRupiah(document.getElementById('amountInput').value);
            var deposit = parseRupiah(document.getElementById('initialDepositInput').value);

            document.getElementById('previewTarget').textContent = ' / Rp ' + target.toLocaleString('id-ID');
            document.getElementById('previewCurrent').textContent = 'Rp ' + deposit.toLocaleString('id-ID');

            var pct = target > 0 ? Math.min(100, Math.round((deposit / target) * 100)) : 0;
            var rem = Math.max(0, target - deposit);

            document.getElementById('previewPct').textContent = pct + '%';
            document.getElementById('previewProgressBar').style.width = pct + '%';
            document.getElementById('previewRemaining').textContent = 'Sisa: Rp ' + rem.toLocaleString('id-ID');

            var accSelect = document.getElementById('accountSelect');
            var accText = accSelect.selectedIndex > 0 ? accSelect.options[accSelect.selectedIndex].text.split('(')[0].trim() : 'Semua Rekening / Virtual';
            document.getElementById('previewAccount').innerHTML = '<span>💳</span> <span>' + accText + '</span>';

            var targetDateVal = document.getElementById('targetDateInput').value;
            var recBox = document.getElementById('previewRecBox');

            if (targetDateVal && rem > 0) {
                var tDate = new Date(targetDateVal);
                var now = new Date();
                var diffMonths = (tDate.getFullYear() - now.getFullYear()) * 12 + (tDate.getMonth() - now.getMonth());
                if (diffMonths <= 0) diffMonths = 1;

                var monthly = Math.round(rem / diffMonths);
                document.getElementById('previewMonthlyRec').textContent = 'Rp ' + monthly.toLocaleString('id-ID') + ' / bln';
                document.getElementById('previewRecSubtitle').textContent = 'Dibutuhkan selama ~' + diffMonths + ' bulan ke depan';
                recBox.classList.remove('hidden');
            } else {
                recBox.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            selectIcon(document.getElementById('selectedIconInput').value || '🎯');
            selectColor(document.getElementById('selectedColorInput').value || '#174C40');
            updatePreview();
        });
    </script>
</x-layouts.app>
