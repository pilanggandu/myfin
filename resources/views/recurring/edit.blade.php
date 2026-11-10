<x-layouts.app title="Ubah Jadwal Berulang">
    <div class="mb-6">
        <a href="{{ route('recurring.show', $recurring) }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali ke detail jadwal
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / RECURRING / EDIT</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-1">
            Ubah Jadwal: {{ $recurring->name }}
        </h1>
        <p class="text-xs text-muted max-w-2xl">
            Perbarui parameter jadwal berulang. Perubahan tidak akan mempengaruhi transaksi historis yang telah berhasil dicatat sebelumnya.
        </p>
    </div>

    <form method="POST" action="{{ route('recurring.update', $recurring) }}" id="recurringEditForm" novalidate>
        @csrf
        @method('PUT')

        <div class="w-full space-y-6">
            <!-- Informasi Utama -->
            <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                        1. INFORMASI JADWAL ({{ $recurring->isExpense() ? 'TAGIHAN' : 'TRANSFER' }})
                    </div>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-sidebar border border-line text-charcoal">
                        {{ strtoupper($recurring->type) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="editName" class="block text-xs font-bold text-charcoal mb-1">
                            Nama Jadwal <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="editName" 
                            value="{{ old('name', $recurring->name) }}"
                            class="field-input w-full text-xs" 
                            required 
                        />
                        @error('name')
                            <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="editMember" class="block text-xs font-bold text-charcoal mb-1">
                            Pihak / Member
                        </label>
                        <select name="member_id" id="editMember" class="field-select w-full text-xs">
                            <option value="">Tanpa Member (Umum)</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}" {{ (string)old('member_id', $recurring->member_id) === (string)$m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="editFrequency" class="block text-xs font-bold text-charcoal mb-1">
                            Frekuensi Perulangan <span class="text-rose-500">*</span>
                        </label>
                        <select name="frequency" id="editFrequency" class="field-select w-full text-xs" required>
                            <option value="monthly" {{ old('frequency', $recurring->frequency) === 'monthly' ? 'selected' : '' }}>Bulanan (Setiap Bulan)</option>
                            <option value="weekly" {{ old('frequency', $recurring->frequency) === 'weekly' ? 'selected' : '' }}>Mingguan (Setiap Minggu)</option>
                            <option value="yearly" {{ old('frequency', $recurring->frequency) === 'yearly' ? 'selected' : '' }}>Tahunan (Setiap Tahun)</option>
                        </select>
                    </div>

                    <div>
                        <label for="editStatus" class="block text-xs font-bold text-charcoal mb-1">
                            Status Jadwal
                        </label>
                        <select name="status" id="editStatus" class="field-select w-full text-xs">
                            <option value="active" {{ old('status', $recurring->status) === 'active' ? 'selected' : '' }}>Aktif (Berjalan Normal)</option>
                            <option value="paused" {{ old('status', $recurring->status) === 'paused' ? 'selected' : '' }}>Dijeda (Sementara)</option>
                            <option value="cancelled" {{ old('status', $recurring->status) === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            <option value="archived" {{ old('status', $recurring->status) === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Rekening & Pos Keuangan -->
            <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                    2. REKENING & POS KEUANGAN
                </div>

                @if ($recurring->isExpense())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="editCategory" class="block text-xs font-bold text-charcoal mb-1">
                                Pos / Kategori Pengeluaran <span class="text-rose-500">*</span>
                            </label>
                            <select name="category_id" id="editCategory" class="field-select w-full text-xs" required>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)old('category_id', $recurring->category_id) === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="editSourceExpense" class="block text-xs font-bold text-charcoal mb-1">
                                Rekening Sumber Pembayaran <span class="text-[10px] text-muted font-normal">(Opsional / Fleksibel)</span>
                            </label>
                            <select name="source_account_id" id="editSourceExpense" class="field-select w-full text-xs">
                                <option value="">Pilih saat bayar (Rekening Fleksibel)</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (string)old('source_account_id', $recurring->source_account_id) === (string)$acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="text-[11px] text-muted mt-1">Kosongkan jika ingin memilih rekening saat konfirmasi tagihan tiba.</div>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="editSourceTransfer" class="block text-xs font-bold text-charcoal mb-1">
                                    Dari Rekening (Asal) <span class="text-rose-500">*</span>
                                </label>
                                <select name="source_account_id" id="editSourceTransfer" class="field-select w-full text-xs" onchange="syncEditTransferAccounts()" required>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ (string)old('source_account_id', $recurring->source_account_id) === (string)$acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="editDestTransfer" class="block text-xs font-bold text-charcoal mb-1">
                                    Ke Rekening (Tujuan) <span class="text-rose-500">*</span>
                                </label>
                                <select name="destination_account_id" id="editDestTransfer" class="field-select w-full text-xs" onchange="syncEditTransferAccounts()" required>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ (string)old('destination_account_id', $recurring->destination_account_id) === (string)$acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="editTransferSameAccountAlert" class="hidden p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span>Rekening tujuan transfer tidak boleh sama dengan rekening asal.</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Nominal & Tanggal -->
            <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4 shadow-xs">
                <div class="text-[11px] font-bold tracking-wider text-muted uppercase">
                    3. NOMINAL & JATUH TEMPO
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="editAmount" class="block text-xs font-bold text-charcoal">
                                Nominal Transaksi (Rp)
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="is_variable_amount" 
                                    id="editToggleVariable" 
                                    value="1" 
                                    class="rounded border-line text-forest focus:ring-forest"
                                    {{ old('is_variable_amount', $recurring->is_variable_amount) ? 'checked' : '' }}
                                    onchange="onToggleEditVariable(this.checked)"
                                />
                                <span class="text-xs text-muted font-normal">Nominal Fleksibel</span>
                            </label>
                        </div>

                        <input 
                            type="number" 
                            name="amount" 
                            id="editAmount" 
                            min="1" 
                            value="{{ old('amount', $recurring->amount) }}"
                            class="field-input w-full text-xs font-mono font-bold"
                            onfocus="this.select()"
                            onclick="this.select()"
                            {{ $recurring->is_variable_amount ? 'disabled' : '' }}
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="editStartDate" class="block text-xs font-bold text-charcoal mb-1">
                                Tanggal Mulai <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="start_date" 
                                id="editStartDate" 
                                value="{{ old('start_date', $recurring->start_date->toDateString()) }}" 
                                class="field-input w-full text-xs" 
                                required 
                            />
                        </div>

                        <div>
                            <label for="editEndDate" class="block text-xs font-bold text-charcoal mb-1">
                                Tanggal Berakhir (Opsional)
                            </label>
                            <input 
                                type="date" 
                                name="end_date" 
                                id="editEndDate" 
                                value="{{ old('end_date', $recurring->end_date?->toDateString()) }}" 
                                class="field-input w-full text-xs" 
                            />
                        </div>
                    </div>

                    <div>
                        <label for="editDescription" class="block text-xs font-bold text-charcoal mb-1">
                            Catatan Tambahan
                        </label>
                        <textarea 
                            name="description" 
                            id="editDescription" 
                            rows="2" 
                            class="field-input w-full text-xs"
                        >{{ old('description', $recurring->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-arus-primary min-h-[44px] px-6 text-xs font-bold cursor-pointer">
                    Simpan Perubahan
                </button>
                <a href="{{ route('recurring.show', $recurring) }}" class="btn-arus-secondary min-h-[44px] px-5 text-xs font-bold">
                    Batal
                </a>
            </div>
        </div>
    </form>

    <script>
        function onToggleEditVariable(isVariable) {
            const amountInput = document.getElementById('editAmount');
            if (isVariable) {
                amountInput.value = '';
                amountInput.disabled = true;
                amountInput.classList.add('opacity-50');
            } else {
                amountInput.disabled = false;
                amountInput.classList.remove('opacity-50');
            }
        }

        function syncEditTransferAccounts() {
            const src = document.getElementById('editSourceTransfer');
            const dst = document.getElementById('editDestTransfer');
            const alertEl = document.getElementById('editTransferSameAccountAlert');
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
            syncEditTransferAccounts();
        });
    </script>
</x-layouts.app>
