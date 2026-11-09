<x-layouts.app title="Ubah Rekening: {{ $account->name }}">
    <div class="mb-6">
        <a href="{{ route('accounts.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            <span>Kembali ke daftar rekening</span>
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / PENGATURAN REKENING</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-1">
            Ubah Rekening: {{ $account->name }}
        </h1>
        <p class="text-xs text-muted max-w-2xl">
            Perbarui data identitas rekening, tipe akun, saldo awal buku, dan status default pembukuan.
        </p>
    </div>

    <!-- Alert Info Saldo Berjalan Saat Ini -->
    <div class="mb-6 p-4 rounded-xl border border-line bg-sidebar/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-panel border border-line flex items-center justify-center text-sm shadow-xs select-none">
                📊
            </span>
            <div>
                <span class="font-bold text-charcoal">Saldo Berjalan Saat Ini:</span>
                <span class="privacy-balance font-mono font-bold text-forest text-sm ml-1" data-raw-value="Rp {{ number_format($account->currentBalance(), 0, ',', '.') }}">
                    Rp {{ number_format($account->currentBalance(), 0, ',', '.') }}
                </span>
                <span class="text-muted block text-[11px] mt-0.5">
                    Total tercatat dari saldo awal ditambah {{ $account->entries()->count() }} mutasi transaksi.
                </span>
            </div>
        </div>
        <a href="{{ route('accounts.show', $account) }}" class="btn-arus-secondary text-xs font-bold self-start sm:self-auto">
            Lihat Riwayat Buku Kas
        </a>
    </div>

    <form method="POST" action="{{ route('accounts.update', $account) }}" id="accountForm" novalidate>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- LEFT COLUMN: FORM EDIT UTAMA -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                <!-- KARTU 1: TIPE & NAMA REKENING -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-5">
                    
                    <!-- Segmented Switcher Tipe Rekening -->
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider text-muted uppercase mb-2">
                            1. Tipe Rekening
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-1.5 rounded-xl bg-sidebar border border-line" id="typeSwitcher">
                            
                            <label class="cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="type" 
                                    value="bank" 
                                    class="sr-only peer" 
                                    {{ old('type', $account->type) === 'bank' ? 'checked' : '' }} 
                                    onchange="onTypeChanged('bank')"
                                >
                                <div class="p-3 rounded-lg text-center transition border border-transparent peer-checked:bg-panel peer-checked:border-forest/40 peer-checked:shadow-xs group">
                                    <div class="text-xl mb-1">🏦</div>
                                    <div class="text-xs font-bold text-charcoal peer-checked:text-forest">Rekening Bank</div>
                                    <div class="text-[10px] text-muted mt-0.5">BCA, Mandiri, BRI</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="type" 
                                    value="e-wallet" 
                                    class="sr-only peer" 
                                    {{ old('type', $account->type) === 'e-wallet' ? 'checked' : '' }} 
                                    onchange="onTypeChanged('e-wallet')"
                                >
                                <div class="p-3 rounded-lg text-center transition border border-transparent peer-checked:bg-panel peer-checked:border-forest/40 peer-checked:shadow-xs group">
                                    <div class="text-xl mb-1">📱</div>
                                    <div class="text-xs font-bold text-charcoal peer-checked:text-forest">Dompet Digital</div>
                                    <div class="text-[10px] text-muted mt-0.5">GoPay, OVO, Dana</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="type" 
                                    value="cash" 
                                    class="sr-only peer" 
                                    {{ old('type', $account->type) === 'cash' ? 'checked' : '' }} 
                                    onchange="onTypeChanged('cash')"
                                >
                                <div class="p-3 rounded-lg text-center transition border border-transparent peer-checked:bg-panel peer-checked:border-forest/40 peer-checked:shadow-xs group">
                                    <div class="text-xl mb-1">💵</div>
                                    <div class="text-xs font-bold text-charcoal peer-checked:text-forest">Kas Tunai</div>
                                    <div class="text-[10px] text-muted mt-0.5">Dompet fisik, tunai</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="type" 
                                    value="investment" 
                                    class="sr-only peer" 
                                    {{ old('type', $account->type) === 'investment' ? 'checked' : '' }} 
                                    onchange="onTypeChanged('investment')"
                                >
                                <div class="p-3 rounded-lg text-center transition border border-transparent peer-checked:bg-panel peer-checked:border-forest/40 peer-checked:shadow-xs group">
                                    <div class="text-xl mb-1">📈</div>
                                    <div class="text-xs font-bold text-charcoal peer-checked:text-forest">Investasi / RDN</div>
                                    <div class="text-[10px] text-muted mt-0.5">Bibit, Saham, Emas</div>
                                </div>
                            </label>
                        </div>
                        @error('type')
                            <div class="field-error mt-1.5 text-xs text-red"><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    <!-- Input Nama Rekening -->
                    <div>
                        <label for="accountNameInput" class="block text-xs font-bold text-charcoal mb-1.5">
                            Nama Rekening
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="accountNameInput" 
                            value="{{ old('name', $account->name) }}" 
                            placeholder="Contoh: BCA Tabungan Utama" 
                            class="field-input w-full text-sm font-semibold"
                            required 
                            autofocus 
                            oninput="updateAccountPreview()"
                        >
                        @error('name')
                            <div class="field-error mt-1.5 text-xs text-red"><span>{{ $message }}</span></div>
                        @enderror
                    </div>
                </div>

                <!-- KARTU 2: SALDO AWAL & TANGGAL EFEKTIF -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-5">
                    <div class="flex items-center justify-between">
                        <label for="openingBalanceDisplay" class="text-[11px] font-bold tracking-wider text-muted uppercase">
                            2. Saldo Awal Pembukuan
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-cream text-muted border border-line">
                            RUPIAH (IDR)
                        </span>
                    </div>

                    <!-- Hero Nominal Box -->
                    <div class="p-4 sm:p-5 rounded-xl bg-sidebar/70 border border-line">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="font-serif text-2xl sm:text-3xl text-muted font-normal select-none flex-shrink-0">Rp</span>
                            <input 
                                type="hidden" 
                                id="openingBalanceInput" 
                                name="opening_balance" 
                                value="{{ old('opening_balance', $account->opening_balance) }}" 
                                required
                            >
                            <input 
                                type="text" 
                                inputmode="numeric" 
                                id="openingBalanceDisplay" 
                                value="{{ old('opening_balance', $account->opening_balance) ? number_format((int)old('opening_balance', $account->opening_balance), 0, ',', '.') : '0' }}" 
                                placeholder="0" 
                                required 
                                class="w-full bg-transparent border-0 p-0 text-3xl sm:text-4xl font-mono font-bold text-charcoal text-right placeholder:text-muted/40 focus:ring-0 focus:outline-none focus:border-0 borderless-amount-input"
                                style="outline: none !important; border: none !important; box-shadow: none !important;"
                                oninput="onOpeningBalanceDisplayInput(this.value)"
                                onfocus="this.select()"
                                onclick="this.select()"
                            >
                        </div>

                        <!-- Live Spelled-out Preview -->
                        <div class="text-xs text-muted font-mono mt-2 pt-2 border-t border-line/50 flex items-center justify-between">
                            <span id="balanceSpelledPreview">Rp {{ number_format($account->opening_balance, 0, ',', '.') }}</span>
                            <span class="text-[11px] text-muted">Saldo Dasar Awal</span>
                        </div>

                        <!-- Quick Amount Chips -->
                        <div class="flex items-center gap-1.5 flex-wrap pt-3 mt-1 border-t border-line/40">
                            <span class="text-[10px] font-bold text-muted uppercase mr-1 select-none">Cepat:</span>
                            <button type="button" onclick="setExactBalance(0)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                Rp 0
                            </button>
                            <button type="button" onclick="addBalance(500000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +500rb
                            </button>
                            <button type="button" onclick="addBalance(1000000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +1jt
                            </button>
                            <button type="button" onclick="addBalance(5000000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +5jt
                            </button>
                            <button type="button" onclick="addBalance(10000000)" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-charcoal hover:bg-cream hover:border-forest/40 transition cursor-pointer">
                                +10jt
                            </button>
                            <button type="button" onclick="setExactBalance({{ $account->opening_balance }})" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-panel border border-line text-muted hover:text-forest hover:border-forest/40 transition cursor-pointer ml-auto" title="Kembalikan ke saldo awal tersimpan">
                                Reset Awal
                            </button>
                        </div>
                    </div>
                    <p class="text-[11px] text-muted leading-relaxed">
                        Perhatian: Perubahan saldo awal akan langsung memperbarui kalkulasi total saldo berjalan rekening ini.
                    </p>
                    @error('opening_balance')
                        <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                    @enderror

                    <!-- Tanggal Buka / Efektif -->
                    <div>
                        <label for="openedOnInput" class="block text-xs font-bold text-charcoal mb-1.5">
                            Tanggal Buka / Efektif Saldo
                        </label>
                        <input 
                            type="date" 
                            name="opened_on" 
                            id="openedOnInput" 
                            value="{{ old('opened_on', $account->opened_on->format('Y-m-d')) }}" 
                            class="field-input w-full"
                            required
                            onchange="updateAccountPreview()"
                        >
                        @error('opened_on')
                            <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    <!-- Alasan Koreksi Saldo Awal -->
                    <div>
                        <label for="reasonInput" class="block text-xs font-bold text-charcoal mb-1.5 flex items-center justify-between">
                            <span>Alasan Koreksi Saldo Awal</span>
                            <span class="text-[10px] text-muted font-normal">Opsional untuk Jejak Audit</span>
                        </label>
                        <input 
                            type="text" 
                            name="reason" 
                            id="reasonInput" 
                            value="{{ old('reason') }}" 
                            placeholder="Contoh: Koreksi penyesuaian rekening koran bank awal periode" 
                            class="field-input w-full"
                        >
                        @error('reason')
                            <div class="field-error mt-1 text-xs text-red"><span>{{ $message }}</span></div>
                        @enderror
                    </div>
                </div>

                <!-- KARTU 3: OPSI REKENING DEFAULT -->
                <div class="panel-card p-4 sm:p-5 rounded-2xl border border-line bg-panel">
                    <label class="flex items-start gap-3.5 cursor-pointer">
                        <div class="pt-0.5">
                            <input 
                                type="checkbox" 
                                name="is_default" 
                                id="isDefaultCheckbox" 
                                value="1" 
                                class="w-4 h-4 rounded text-forest border-line focus:ring-forest cursor-pointer"
                                {{ old('is_default', $account->is_default) ? 'checked' : '' }}
                                onchange="updateAccountPreview()"
                            >
                        </div>
                        <div class="flex-1 select-none">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-charcoal">Jadikan Sebagai Rekening Default</span>
                                <span class="text-xs text-amber-500">★</span>
                            </div>
                            <p class="text-[11px] text-muted mt-0.5 leading-relaxed">
                                Rekening default otomatis dipilih pada formulir transaksi baru agar Anda tidak perlu memilih rekening secara manual setiap kali mencatat arus kas.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- TOMBOL AKSI -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-arus-primary min-h-[46px] px-6 text-sm font-bold flex-1 sm:flex-initial">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>Simpan Perubahan Rekening</span>
                    </button>
                    <a href="{{ route('accounts.index') }}" class="btn-arus-secondary min-h-[46px] px-5 text-sm font-bold">
                        Batal
                    </a>
                </div>
            </div>

            <!-- RIGHT COLUMN: LIVE REKENING CARD MOCKUP -->
            <div class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-[88px] space-y-4">
                
                <!-- Live Payment / Account Card Mockup -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-line pb-3">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('arus-logo.svg') }}" alt="" class="arus-logo arus-logo-small">
                            <div>
                                <div class="text-[10px] font-bold text-muted uppercase tracking-wider">KARTU REKENING</div>
                                <div class="text-xs font-bold text-charcoal">Pratinjau Fisik</div>
                            </div>
                        </div>
                        <span id="previewTypeBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-forest/10 text-forest border border-forest/20">
                            REKENING BANK
                        </span>
                    </div>

                    <!-- Visual Debit / Wallet Card -->
                    <div class="relative w-full rounded-2xl p-5 sm:p-6 text-white overflow-hidden shadow-lg transition-all duration-200" id="visualCard" style="background: linear-gradient(135deg, #162a22 0%, #0d1a14 60%, #1e3a30 100%);">
                        <!-- Card Shimmer & Watermark -->
                        <div class="absolute -right-8 -bottom-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                        <div class="absolute right-4 top-4 text-white/20 font-serif text-3xl select-none">ARUS</div>

                        <div class="relative z-10 flex flex-col justify-between h-44">
                            <!-- Card Top: Chip & Default Badge -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-7 rounded-md bg-amber-200/80 border border-amber-300/40 flex items-center justify-center text-[10px] text-amber-900 font-mono font-bold shadow-inner select-none">
                                        ▮▮
                                    </div>
                                    <span class="text-white/60 text-xs">⚡</span>
                                </div>
                                <div id="previewDefaultBadge" class="{{ $account->is_default ? '' : 'hidden' }} text-[10px] font-bold px-2 py-0.5 rounded bg-amber-400 text-slate-950 flex items-center gap-1 shadow-xs">
                                    <span>★</span>
                                    <span>DEFAULT</span>
                                </div>
                            </div>

                            <!-- Card Middle: Saldo Display -->
                            <div>
                                <div class="text-[10px] uppercase tracking-wider text-white/60 font-semibold">
                                    Saldo Awal Buku
                                </div>
                                <div id="previewBalance" class="font-mono text-2xl font-bold tracking-tight text-white mt-0.5">
                                    Rp {{ number_format($account->opening_balance, 0, ',', '.') }}
                                </div>
                            </div>

                            <!-- Card Bottom: Name & Date -->
                            <div class="flex items-end justify-between pt-2 border-t border-white/10">
                                <div class="min-w-0 flex-1 pr-2">
                                    <div class="text-[9px] uppercase tracking-wider text-white/50">Pemilik & Nama Rekening</div>
                                    <div id="previewCardName" class="font-semibold text-xs text-white truncate mt-0.5">
                                        {{ $account->name }}
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-[9px] uppercase tracking-wider text-white/50">Efektif</div>
                                    <div id="previewCardDate" class="font-mono text-xs text-white/90">
                                        {{ $account->opened_on->format('m/y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slip Rincian Data Rekening -->
                    <div class="divide-y divide-line/60 text-xs pt-1">
                        <div class="py-2.5 flex justify-between items-center">
                            <span class="text-muted">ID Rekening:</span>
                            <span class="font-mono text-muted">
                                #{{ $account->id }}
                            </span>
                        </div>
                        <div class="py-2.5 flex justify-between items-center">
                            <span class="text-muted">Kategori Rekening:</span>
                            <span id="previewDetailCategory" class="font-bold text-charcoal">
                                Perbankan Konvensional
                            </span>
                        </div>
                        <div class="py-2.5 flex justify-between items-center">
                            <span class="text-muted">Ruang Keuangan:</span>
                            <span class="font-bold text-forest">
                                {{ app('currentSpace')->name }}
                            </span>
                        </div>
                        <div class="py-2.5 flex justify-between items-center">
                            <span class="text-muted">Status Rekening:</span>
                            <span class="font-semibold {{ $account->isArchived() ? 'text-muted' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $account->isArchived() ? 'Diarsipkan' : 'Aktif' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Prinsip Pembukuan ARUS Callout -->
                <div class="p-4 rounded-xl border border-line bg-sidebar/50 space-y-2 text-xs">
                    <div class="flex items-center gap-1.5 font-bold text-charcoal">
                        <svg class="w-4 h-4 text-forest flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span>Prinsip Integritas Rekening</span>
                    </div>
                    <p class="text-muted text-[11px] leading-relaxed">
                        Data saldo awal menjadi titik tolak audit pembukuan. Mengubah saldo awal di form ini akan memperbarui angka awal periode tanpa merusak konsistensi riwayat transaksi yang telah dibukukan.
                    </p>
                </div>

            </div>
        </div>
    </form>

    <script>
        function onTypeChanged(type) {
            const visualCard = document.getElementById('visualCard');
            const previewBadge = document.getElementById('previewTypeBadge');
            const previewCategory = document.getElementById('previewDetailCategory');

            if (type === 'bank') {
                visualCard.style.background = 'linear-gradient(135deg, #162a22 0%, #0d1a14 60%, #1e3a30 100%)';
                previewBadge.textContent = 'REKENING BANK';
                previewBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-forest/10 text-forest border border-forest/20';
                previewCategory.textContent = 'Perbankan Konvensional / Digital';
            } else if (type === 'e-wallet') {
                visualCard.style.background = 'linear-gradient(135deg, #1e293b 0%, #0f172a 60%, #334155 100%)';
                previewBadge.textContent = 'DOMPET DIGITAL';
                previewBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20';
                previewCategory.textContent = 'E-Wallet / Saldo Non-Tunai';
            } else if (type === 'cash') {
                visualCard.style.background = 'linear-gradient(135deg, #27272a 0%, #18181b 60%, #3f3f46 100%)';
                previewBadge.textContent = 'KAS TUNAI';
                previewBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
                previewCategory.textContent = 'Uang Tunai / Kas Fisik';
            } else if (type === 'investment') {
                visualCard.style.background = 'linear-gradient(135deg, #064e3b 0%, #022c22 60%, #047857 100%)';
                previewBadge.textContent = 'INVESTASI / RDN';
                previewBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
                previewCategory.textContent = 'Rekening RDN / Sekuritas / Portofolio';
            }

            updateAccountPreview();
        }

        function onOpeningBalanceDisplayInput(rawVal) {
            const digits = rawVal.replace(/\D/g, '');
            const intVal = digits ? parseInt(digits, 10) : 0;
            document.getElementById('openingBalanceInput').value = intVal;
            document.getElementById('openingBalanceDisplay').value = intVal > 0 ? Number(intVal).toLocaleString('id-ID') : '';
            updateAccountPreview();
        }

        function setExactBalance(val) {
            document.getElementById('openingBalanceInput').value = val;
            document.getElementById('openingBalanceDisplay').value = val > 0 ? Number(val).toLocaleString('id-ID') : '';
            updateAccountPreview();
        }

        function addBalance(val) {
            const hidden = document.getElementById('openingBalanceInput');
            const current = parseInt(hidden.value, 10) || 0;
            const next = current + val;
            hidden.value = next;
            document.getElementById('openingBalanceDisplay').value = Number(next).toLocaleString('id-ID');
            updateAccountPreview();
        }

        function formatRupiah(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }

        function terbilangRupiah(n) {
            if (n === 0) return 'Nol rupiah';
            const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
            function spell(x) {
                if (x < 12) return satuan[x];
                if (x < 20) return spell(x - 10) + ' belas';
                if (x < 100) return spell(Math.floor(x / 10)) + ' puluh' + (x % 10 > 0 ? ' ' + spell(x % 10) : '');
                if (x < 200) return 'seratus' + (x - 100 > 0 ? ' ' + spell(x - 100) : '');
                if (x < 1000) return spell(Math.floor(x / 100)) + ' ratus' + (x % 100 > 0 ? ' ' + spell(x % 100) : '');
                if (x < 2000) return 'seribu' + (x - 1000 > 0 ? ' ' + spell(x - 1000) : '');
                if (x < 1000000) return spell(Math.floor(x / 1000)) + ' ribu' + (x % 1000 > 0 ? ' ' + spell(x % 1000) : '');
                if (x < 1000000000) return spell(Math.floor(x / 1000000)) + ' juta' + (x % 1000000 > 0 ? ' ' + spell(x % 1000000) : '');
                if (x < 1000000000000) return spell(Math.floor(x / 1000000000)) + ' miliar' + (x % 1000000000 > 0 ? ' ' + spell(x % 1000000000) : '');
                return spell(Math.floor(x / 1000000000000)) + ' triliun';
            }
            const res = spell(n);
            return res.charAt(0).toUpperCase() + res.slice(1) + ' rupiah';
        }

        function updateAccountPreview() {
            const nameVal = document.getElementById('accountNameInput').value.trim();
            const balanceVal = parseInt(document.getElementById('openingBalanceInput').value, 10) || 0;
            const dateVal = document.getElementById('openedOnInput').value;
            const isDefault = document.getElementById('isDefaultCheckbox').checked;

            document.getElementById('previewCardName').textContent = nameVal || '(Belum diberi nama)';
            document.getElementById('previewBalance').textContent = formatRupiah(balanceVal);
            document.getElementById('balanceSpelledPreview').textContent = terbilangRupiah(balanceVal);

            const defBadge = document.getElementById('previewDefaultBadge');
            if (isDefault) {
                defBadge.classList.remove('hidden');
            } else {
                defBadge.classList.add('hidden');
            }

            if (dateVal) {
                const parts = dateVal.split('-');
                if (parts.length === 3) {
                    const yy = parts[0].slice(-2);
                    const mm = parts[1];
                    document.getElementById('previewCardDate').textContent = `${mm}/${yy}`;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const checkedType = document.querySelector('input[name="type"]:checked');
            if (checkedType) {
                onTypeChanged(checkedType.value);
            } else {
                updateAccountPreview();
            }
        });
    </script>
</x-layouts.app>
