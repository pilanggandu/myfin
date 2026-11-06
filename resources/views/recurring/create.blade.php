<x-layouts.app title="Buat Jadwal Berulang">
    <div class="mb-6">
        <a href="{{ route('recurring.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar jadwal berulang
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / RECURRING / TAMBAH</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-1">
            Buat Jadwal Berulang Baru
        </h1>
        <p class="text-xs text-muted max-w-2xl">
            Atur tagihan rutin atau transfer terjadwal. Saldo tidak akan berubah otomatis sebelum Anda memberikan konfirmasi pencatatan.
        </p>
    </div>

    @if ($accounts->isEmpty())
        <div class="panel-card p-8 text-center space-y-4 max-w-xl mx-auto border border-line rounded-2xl bg-panel">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto text-xl">
                ⚠️
            </div>
            <div>
                <h3 class="font-bold text-base text-charcoal">Belum Ada Rekening Aktif</h3>
                <p class="text-xs text-muted mt-1 max-w-md mx-auto">
                    Anda perlu memiliki setidaknya satu rekening aktif sebelum dapat membuat jadwal pengeluaran atau transfer berulang.
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('accounts.create') }}" class="btn-arus-primary">
                    + Tambah Rekening Sekarang
                </a>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('recurring.store') }}" id="recurringForm" novalidate>
            @csrf

            <div class="max-w-3xl space-y-6">
                <!-- 1. Tipe Jadwal -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        1. JENIS JADWAL BERULANG
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input 
                                type="radio" 
                                name="type" 
                                value="expense" 
                                class="sr-only peer" 
                                {{ old('type', $defaultType) === 'expense' ? 'checked' : '' }}
                                onchange="onRecurringTypeChange('expense')"
                            />
                            <div class="p-4 rounded-xl border border-line bg-sidebar peer-checked:bg-panel peer-checked:border-forest peer-checked:ring-2 peer-checked:ring-forest/20 transition flex items-center gap-3">
                                <span class="text-2xl">🏷️</span>
                                <div>
                                    <div class="font-bold text-xs text-charcoal">Tagihan Pengeluaran</div>
                                    <div class="text-[11px] text-muted">Langganan, sewa, listrik, internet, asuransi</div>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input 
                                type="radio" 
                                name="type" 
                                value="transfer" 
                                class="sr-only peer" 
                                {{ old('type', $defaultType) === 'transfer' ? 'checked' : '' }}
                                onchange="onRecurringTypeChange('transfer')"
                            />
                            <div class="p-4 rounded-xl border border-line bg-sidebar peer-checked:bg-panel peer-checked:border-forest peer-checked:ring-2 peer-checked:ring-forest/20 transition flex items-center gap-3">
                                <span class="text-2xl">⇄</span>
                                <div>
                                    <div class="font-bold text-xs text-charcoal">Transfer Berulang</div>
                                    <div class="text-[11px] text-muted">Rotasi kas rutin antar-rekening sendiri</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('type')
                        <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 2. Informasi Utama Jadwal -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        2. INFORMASI JADWAL
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="recurringName" class="block text-xs font-bold text-charcoal mb-1">
                                Nama Jadwal <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                id="recurringName" 
                                value="{{ old('name') }}"
                                placeholder="Contoh: Langganan Netflix, Tagihan Internet IndiHome, Uang Bulanan" 
                                class="field-input w-full text-xs" 
                                required 
                            />
                            @error('name')
                                <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="recurringMember" class="block text-xs font-bold text-charcoal mb-1">
                                Pihak / Member (Opsional)
                            </label>
                            <select name="member_id" id="recurringMember" class="field-select w-full text-xs">
                                <option value="">Tanpa Member (Umum)</option>
                                @foreach ($members as $m)
                                    <option value="{{ $m->id }}" {{ (string)old('member_id') === (string)$m->id ? 'selected' : '' }}>
                                        {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('member_id')
                                <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="recurringFrequency" class="block text-xs font-bold text-charcoal mb-1">
                                Frekuensi Perulangan <span class="text-rose-500">*</span>
                            </label>
                            <select name="frequency" id="recurringFrequency" class="field-select w-full text-xs" required>
                                <option value="monthly" {{ old('frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Bulanan (Setiap Bulan)</option>
                                <option value="weekly" {{ old('frequency') === 'weekly' ? 'selected' : '' }}>Mingguan (Setiap Minggu)</option>
                                <option value="yearly" {{ old('frequency') === 'yearly' ? 'selected' : '' }}>Tahunan (Setiap Tahun)</option>
                            </select>
                            @error('frequency')
                                <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 3. Alur Rekening & Kategori -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        3. REKENING & POS KEUANGAN
                    </div>

                    <!-- Blok Expense -->
                    <div id="expenseFields" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="expenseCategory" class="block text-xs font-bold text-charcoal mb-1">
                                Pos / Kategori Pengeluaran <span class="text-rose-500">*</span>
                            </label>
                            <select name="category_id" id="expenseCategory" class="field-select w-full text-xs">
                                <option value="">Pilih Kategori...</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="sourceAccountExpense" class="block text-xs font-bold text-charcoal mb-1">
                                Rekening Sumber Pembayaran <span class="text-[10px] text-muted font-normal">(Opsional / Fleksibel)</span>
                            </label>
                            <select name="source_account_id" id="sourceAccountExpense" class="field-select w-full text-xs">
                                <option value="">Pilih saat bayar (Rekening Fleksibel)</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (string)old('source_account_id') === (string)$acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="text-[11px] text-muted mt-1">Kosongkan jika ingin memilih rekening saat konfirmasi tagihan tiba.</div>
                            @error('source_account_id')
                                <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Blok Transfer -->
                    <div id="transferFields" class="hidden space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="sourceAccountTransfer" class="block text-xs font-bold text-charcoal mb-1">
                                    Dari Rekening (Asal) <span class="text-rose-500">*</span>
                                </label>
                                <select name="source_account_id" id="sourceAccountTransfer" class="field-select w-full text-xs" onchange="syncTransferAccounts()" disabled>
                                    <option value="">Pilih Rekening Asal...</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ (string)old('source_account_id') === (string)$acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }}{{ $acc->is_default ? ' (Default)' : '' }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="destinationAccountTransfer" class="block text-xs font-bold text-charcoal mb-1">
                                    Ke Rekening (Tujuan) <span class="text-rose-500">*</span>
                                </label>
                                <select name="destination_account_id" id="destinationAccountTransfer" class="field-select w-full text-xs" onchange="syncTransferAccounts()" disabled>
                                    <option value="">Pilih Rekening Tujuan...</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ (string)old('destination_account_id') === (string)$acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('destination_account_id')
                                    <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="transferSameAccountAlert" class="hidden p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span>Rekening tujuan transfer tidak boleh sama dengan rekening asal.</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Nominal & Waktu Jatuh Tempo -->
                <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        4. NOMINAL & PERIODE WAKTU
                    </div>

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="recurringAmount" class="block text-xs font-bold text-charcoal">
                                    Nominal Transaksi (Rp)
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="is_variable_amount" 
                                        id="toggleVariableAmount" 
                                        value="1" 
                                        class="rounded border-line text-forest focus:ring-forest"
                                        {{ old('is_variable_amount') ? 'checked' : '' }}
                                        onchange="onToggleVariable(this.checked)"
                                    />
                                    <span class="text-xs text-muted font-normal">Nominal Fleksibel (diisi saat konfirmasi)</span>
                                </label>
                            </div>

                            <input 
                                type="number" 
                                name="amount" 
                                id="recurringAmount" 
                                min="1" 
                                value="{{ old('amount') }}"
                                placeholder="Contoh: 150000" 
                                class="field-input w-full text-xs font-mono font-bold"
                                onfocus="this.select()"
                                onclick="this.select()"
                            />
                            @error('amount')
                                <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                            @enderror
                            <div class="text-[11px] text-muted mt-1" id="amountHelper">
                                Masukkan nominal rupiah tetap. Untuk tagihan seperti listrik yang nilainya berubah, aktifkan opsi nominal fleksibel.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="recurringStartDate" class="block text-xs font-bold text-charcoal mb-1">
                                    Tanggal Mulai / Jatuh Tempo Pertama <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    name="start_date" 
                                    id="recurringStartDate" 
                                    value="{{ old('start_date', (app('currentSpace') ? app('currentSpace')->now() : now())->format('Y-m-d')) }}" 
                                    class="field-input w-full text-xs" 
                                    required 
                                />
                                <div class="text-[11px] text-muted mt-1">
                                    Tanggal ini menetapkan hari patokan perulangan (misal tgl 31 akan otomatis menyesuaikan pada bulan pendek).
                                </div>
                                @error('start_date')
                                    <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="recurringEndDate" class="block text-xs font-bold text-charcoal mb-1">
                                    Tanggal Berakhir (Opsional)
                                </label>
                                <input 
                                    type="date" 
                                    name="end_date" 
                                    id="recurringEndDate" 
                                    value="{{ old('end_date') }}" 
                                    class="field-input w-full text-xs" 
                                />
                                <div class="text-[11px] text-muted mt-1">
                                    Kosongkan jika jadwal berlangsung seterusnya tanpa batas akhir.
                                </div>
                                @error('end_date')
                                    <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="recurringDescription" class="block text-xs font-bold text-charcoal mb-1">
                                Catatan Tambahan (Opsional)
                            </label>
                            <textarea 
                                name="description" 
                                id="recurringDescription" 
                                rows="2" 
                                placeholder="Nomor pelanggan, tautan portal pembayaran, atau rincian lainnya..." 
                                class="field-input w-full text-xs"
                            >{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-arus-primary min-h-[44px] px-6 text-xs font-bold cursor-pointer">
                        + Simpan Jadwal Berulang
                    </button>
                    <a href="{{ route('recurring.index') }}" class="btn-arus-secondary min-h-[44px] px-5 text-xs font-bold">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    @endif

    <script>
        let currentType = '{{ old('type', $defaultType) }}';

        function onRecurringTypeChange(type) {
            currentType = type;
            const expFields = document.getElementById('expenseFields');
            const trfFields = document.getElementById('transferFields');
            const expSource = document.getElementById('sourceAccountExpense');
            const expCat = document.getElementById('expenseCategory');
            const trfSource = document.getElementById('sourceAccountTransfer');
            const trfDest = document.getElementById('destinationAccountTransfer');

            if (type === 'transfer') {
                expFields.classList.add('hidden');
                trfFields.classList.remove('hidden');

                expSource.disabled = true;
                expCat.disabled = true;
                trfSource.disabled = false;
                trfDest.disabled = false;
                syncTransferAccounts();
            } else {
                expFields.classList.remove('hidden');
                trfFields.classList.add('hidden');

                expSource.disabled = false;
                expCat.disabled = false;
                trfSource.disabled = true;
                trfDest.disabled = true;
            }
        }

        function onToggleVariable(isVariable) {
            const amountInput = document.getElementById('recurringAmount');
            const helper = document.getElementById('amountHelper');
            if (isVariable) {
                amountInput.value = '';
                amountInput.disabled = true;
                amountInput.classList.add('opacity-50');
                helper.textContent = 'Nominal fleksibel aktif: Anda akan memasukkan nominal riil saat mengonfirmasi tagihan.';
            } else {
                amountInput.disabled = false;
                amountInput.classList.remove('opacity-50');
                helper.textContent = 'Masukkan nominal rupiah tetap untuk setiap periode.';
            }
        }

        function syncTransferAccounts() {
            const src = document.getElementById('sourceAccountTransfer');
            const dst = document.getElementById('destinationAccountTransfer');
            const alertEl = document.getElementById('transferSameAccountAlert');
            if (!src || !dst) return;

            const srcVal = src.value;
            const dstVal = dst.value;

            for (let i = 0; i < dst.options.length; i++) {
                const opt = dst.options[i];
                if (!opt.value) continue;
                if (srcVal && opt.value === srcVal) {
                    opt.disabled = true;
                    if (opt.selected) dst.value = '';
                } else {
                    opt.disabled = false;
                }
            }

            for (let i = 0; i < src.options.length; i++) {
                const opt = src.options[i];
                if (!opt.value) continue;
                if (dstVal && opt.value === dstVal) {
                    opt.disabled = true;
                    if (opt.selected) src.value = '';
                } else {
                    opt.disabled = false;
                }
            }

            if (alertEl) {
                if (src.value && dst.value && src.value === dst.value) {
                    alertEl.classList.remove('hidden');
                } else {
                    alertEl.classList.add('hidden');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            onRecurringTypeChange(currentType);
            const toggle = document.getElementById('toggleVariableAmount');
            if (toggle) onToggleVariable(toggle.checked);

            const form = document.getElementById('recurringForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (currentType === 'transfer') {
                        const src = document.getElementById('sourceAccountTransfer');
                        const dst = document.getElementById('destinationAccountTransfer');
                        if (src && dst && (!src.value || !dst.value || src.value === dst.value)) {
                            e.preventDefault();
                            const alertEl = document.getElementById('transferSameAccountAlert');
                            if (alertEl) alertEl.classList.remove('hidden');
                            if (src.value === dst.value || !dst.value) {
                                dst.focus();
                            } else {
                                src.focus();
                            }
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.app>
