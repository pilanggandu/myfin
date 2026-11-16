<x-layouts.app title="Impor Transaksi">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / TRANSAKSI / IMPOR</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Impor Transaksi &amp; Aturan Otomatis
            </h1>
            <p class="text-xs text-muted">
                Unggah mutasi rekening atau CSV pembukuan. Aturan otomatis akan langsung mengenali kategori, member, dan catatan untuk setiap baris.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a 
                href="{{ route('transactions.import.sample') }}" 
                class="btn-arus-secondary inline-flex items-center gap-1.5 min-h-[40px] px-3 text-xs font-semibold"
                title="Unduh berkas contoh CSV dengan format yang didukung"
            >
                <svg class="w-4 h-4 text-forest dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Unduh Contoh CSV</span>
            </a>
            <a 
                href="{{ route('transaction-rules.index') }}" 
                class="btn-arus-secondary inline-flex items-center gap-1.5 min-h-[40px] px-3 text-xs font-semibold"
            >
                <svg class="w-4 h-4 text-forest dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                </svg>
                <span>Kelola Aturan ({{ $activeRulesCount }} Aktif)</span>
            </a>
        </div>
    </div>

    @if (! isset($previewData))
        <!-- Form Unggah & Input CSV -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="panel-card p-5 sm:p-6">
                    <form method="POST" action="{{ route('transactions.import.parse') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <!-- Pilihan Rekening Tujuan -->
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1.5">
                                Rekening Tujuan Utama <span class="text-rose-500">*</span>
                            </label>
                            <select name="account_id" class="field-select w-full text-xs" required>
                                <option value="">-- Pilih Rekening Penyimpan Mutasi --</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (old('account_id') == $acc->id || $acc->is_default) ? 'selected' : '' }}>
                                        {{ $acc->name }} ({{ ucfirst($acc->type) }}) - Saldo: Rp {{ number_format((float) $acc->current_balance, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-muted mt-1">Transaksí yang diimpor akan dibukukan ke rekening ini secara otomatis.</p>
                        </div>

                        <!-- Opsi Input: Berkas atau Teks -->
                        <div x-data="{ tab: 'file' }">
                            <div class="flex items-center gap-2 border-b border-line mb-4">
                                <button 
                                    type="button" 
                                    @click="tab = 'file'" 
                                    :class="tab === 'file' ? 'border-forest text-forest dark:border-emerald-400 dark:text-emerald-400 font-bold' : 'border-transparent text-muted hover:text-charcoal font-semibold'"
                                    class="pb-2 text-xs border-b-2 transition"
                                >
                                    Unggah Berkas CSV
                                </button>
                                <button 
                                    type="button" 
                                    @click="tab = 'text'" 
                                    :class="tab === 'text' ? 'border-forest text-forest dark:border-emerald-400 dark:text-emerald-400 font-bold' : 'border-transparent text-muted hover:text-charcoal font-semibold'"
                                    class="pb-2 text-xs border-b-2 transition"
                                >
                                    Tempel Teks / Tabel CSV
                                </button>
                            </div>

                            <div x-show="tab === 'file'" class="space-y-3">
                                <label class="block text-xs font-bold text-charcoal mb-1">Pilih Berkas CSV / TXT</label>
                                <div class="border-2 border-dashed border-line rounded-2xl p-6 text-center hover:border-forest/50 transition bg-sidebar/30">
                                    <svg class="w-10 h-10 mx-auto text-muted mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <input type="file" name="csv_file" accept=".csv,.txt" class="text-xs text-muted file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-forest file:text-white hover:file:bg-forest/90 cursor-pointer">
                                    <p class="text-[11px] text-muted mt-2">Mendukung format pemisah koma (,), titik koma (;), atau tabulator (\t).</p>
                                </div>
                            </div>

                            <div x-show="tab === 'text'" style="display: none;" class="space-y-2">
                                <label class="block text-xs font-bold text-charcoal mb-1">Tempelkan Data Baris CSV</label>
                                <textarea 
                                    name="pasted_csv" 
                                    rows="8" 
                                    placeholder="Tanggal,Keterangan,Nominal,Jenis&#10;16/11/2026,Kopi Kenangan,24000,Pengeluaran&#10;15/11/2026,Gaji Bulanan,8500000,Pemasukan" 
                                    class="field-input font-mono text-xs w-full"
                                ></textarea>
                                <p class="text-[11px] text-muted">Salin langsung dari spreadsheet (Excel/Google Sheets) atau teks mutasi bank.</p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn-arus-primary w-full py-3 text-xs font-bold flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <span>Pratinjau &amp; Jalankan Aturan Otomatis</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Panduan & Format Kolom -->
            <div class="space-y-4">
                <div class="panel-card p-5">
                    <h3 class="font-serif font-bold text-sm text-charcoal mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-forest dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span>Aturan Otomatis Transaksi</span>
                    </h3>
                    <p class="text-xs text-muted leading-relaxed mb-3">
                        Saat mengimpor, sistem akan mengecek nama keterangan dan nominal pada setiap baris. Jika cocok dengan aturan Anda:
                    </p>
                    <ul class="text-xs text-muted space-y-2 list-disc list-inside">
                        <li>Kategori pos belanja/pemasukan langsung terisi.</li>
                        <li>Member penanggung jawab langsung terpasang.</li>
                        <li>Catatan audit otomatis disematkan.</li>
                    </ul>
                    <div class="mt-4 pt-3 border-t border-line flex items-center justify-between text-xs">
                        <span class="text-muted">Aturan aktif di ruang ini:</span>
                        <strong class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $activeRulesCount }} Aturan</strong>
                    </div>
                </div>

                <div class="panel-card p-5">
                    <h3 class="font-serif font-bold text-sm text-charcoal mb-2">Kolom yang Didukung</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-start justify-between py-1 border-b border-line">
                            <span class="font-mono text-charcoal font-semibold">Tanggal</span>
                            <span class="text-muted text-[11px]">DD/MM/YYYY atau YYYY-MM-DD</span>
                        </div>
                        <div class="flex items-start justify-between py-1 border-b border-line">
                            <span class="font-mono text-charcoal font-semibold">Keterangan</span>
                            <span class="text-muted text-[11px]">Deskripsi / Mutasi bank</span>
                        </div>
                        <div class="flex items-start justify-between py-1 border-b border-line">
                            <span class="font-mono text-charcoal font-semibold">Nominal</span>
                            <span class="text-muted text-[11px]">150.000 atau 150,000.00</span>
                        </div>
                        <div class="flex items-start justify-between py-1 border-b border-line">
                            <span class="font-mono text-charcoal font-semibold">Jenis (Opsional)</span>
                            <span class="text-muted text-[11px]">Pengeluaran / Pemasukan</span>
                        </div>
                        <div class="flex items-start justify-between py-1 border-b border-line">
                            <span class="font-mono text-charcoal font-semibold">Kategori</span>
                            <span class="text-muted text-[11px]">Dapat terisi oleh aturan</span>
                        </div>
                        <div class="flex items-start justify-between py-1">
                            <span class="font-mono text-charcoal font-semibold">Catatan</span>
                            <span class="text-muted text-[11px]">Memo / Catatan tambahan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Tahap Pratinjau & Konfirmasi Eksekusi -->
        <div class="space-y-6">
            <!-- Kartu Ringkasan Pratinjau -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="panel-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Total Baris</div>
                    <div class="text-2xl font-serif text-charcoal font-bold mt-1">{{ $previewData['summary']['total_rows'] }}</div>
                </div>
                <div class="panel-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Dikenali Aturan</div>
                    <div class="text-2xl font-serif text-emerald-600 dark:text-emerald-400 font-bold mt-1 flex items-center gap-1.5">
                        <span>{{ $previewData['summary']['matched_rules_count'] }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 font-sans font-bold">Otomatis</span>
                    </div>
                </div>
                <div class="panel-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Total Pengeluaran</div>
                    <div class="text-lg sm:text-xl font-serif text-rose-600 dark:text-rose-400 font-bold mt-1">
                        Rp {{ number_format((float) $previewData['summary']['total_expense'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="panel-card p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Total Pemasukan</div>
                    <div class="text-lg sm:text-xl font-serif text-emerald-600 dark:text-emerald-400 font-bold mt-1">
                        Rp {{ number_format((float) $previewData['summary']['total_income'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('transactions.import.execute') }}">
                @csrf
                <input type="hidden" name="account_id" value="{{ $accountId }}">

                <div class="panel-card overflow-hidden">
                    <div class="p-4 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-sidebar/30">
                        <div class="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                id="selectAll" 
                                checked 
                                onclick="toggleAllRows(this)" 
                                class="rounded border-line text-forest focus:ring-forest cursor-pointer"
                            >
                            <label for="selectAll" class="text-xs font-bold text-charcoal cursor-pointer">
                                Pilih Semua Baris untuk Diimpor
                            </label>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('transactions.import') }}" class="btn-arus-secondary px-3 py-1.5 text-xs font-semibold">
                                Batalkan &amp; Unggah Ulang
                            </a>
                            <button type="submit" class="btn-arus-primary px-5 py-2 text-xs font-bold flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>Simpan &amp; Impor Sekarang</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs divide-y divide-line">
                            <thead class="bg-sidebar/50 text-[11px] font-bold text-muted uppercase tracking-wider">
                                <tr>
                                    <th class="p-3 w-10 text-center">Pilih</th>
                                    <th class="p-3 w-28">Tanggal</th>
                                    <th class="p-3 min-w-[200px]">Keterangan</th>
                                    <th class="p-3 w-32">Nominal</th>
                                    <th class="p-3 w-48">Kategori Pos</th>
                                    <th class="p-3 w-36">Member</th>
                                    <th class="p-3 min-w-[150px]">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($previewData['rows'] as $idx => $row)
                                    <tr class="hover:bg-cream/40 dark:hover:bg-line/20 {{ ! empty($row['primary_matched_rule']) ? 'bg-emerald-50/20 dark:bg-emerald-950/10' : '' }}">
                                        <td class="p-3 text-center">
                                            <input 
                                                type="checkbox" 
                                                name="rows[{{ $idx }}][selected]" 
                                                value="1" 
                                                checked 
                                                class="row-checkbox rounded border-line text-forest focus:ring-forest cursor-pointer"
                                            >
                                            <input type="hidden" name="rows[{{ $idx }}][account_id]" value="{{ $row['account_id'] }}">
                                            <input type="hidden" name="rows[{{ $idx }}][type]" value="{{ $row['type'] }}">
                                            <input type="hidden" name="rows[{{ $idx }}][primary_matched_rule]" value="{{ $row['primary_matched_rule'] ?? '' }}">
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <input 
                                                type="date" 
                                                name="rows[{{ $idx }}][date]" 
                                                value="{{ $row['date'] }}" 
                                                class="field-input h-8 text-[11px] py-1 px-2 w-full"
                                                required
                                            >
                                        </td>
                                        <td class="p-3">
                                            <input 
                                                type="text" 
                                                name="rows[{{ $idx }}][description]" 
                                                value="{{ $row['description'] }}" 
                                                class="field-input h-8 text-xs py-1 px-2 w-full font-medium"
                                                required
                                            >
                                            @if (! empty($row['primary_matched_rule']))
                                                <div class="mt-1 flex items-center gap-1 text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                                    </svg>
                                                    <span>Aturan: {{ $row['primary_matched_rule'] }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <div class="font-bold text-xs {{ $row['type'] === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                {{ $row['type'] === 'income' ? '+ ' : '- ' }} Rp {{ number_format((float) $row['amount'], 0, ',', '.') }}
                                            </div>
                                            <input type="hidden" name="rows[{{ $idx }}][amount]" value="{{ $row['amount'] }}">
                                            <span class="text-[10px] uppercase font-bold text-muted">{{ $row['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</span>
                                        </td>
                                        <td class="p-3">
                                            <select name="rows[{{ $idx }}][category_id]" class="field-select h-8 text-xs py-0.5 px-2 w-full">
                                                <option value="">-- Tanpa Kategori (Default) --</option>
                                                <optgroup label="Pengeluaran">
                                                    @foreach ($categories->where('type', 'expense') as $cat)
                                                        <option value="{{ $cat->id }}" {{ (isset($row['category_id']) && $row['category_id'] == $cat->id) ? 'selected' : '' }}>
                                                            {{ $cat->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                                <optgroup label="Pemasukan">
                                                    @foreach ($categories->where('type', 'income') as $cat)
                                                        <option value="{{ $cat->id }}" {{ (isset($row['category_id']) && $row['category_id'] == $cat->id) ? 'selected' : '' }}>
                                                            {{ $cat->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            </select>
                                        </td>
                                        <td class="p-3">
                                            <select name="rows[{{ $idx }}][member_id]" class="field-select h-8 text-xs py-0.5 px-2 w-full">
                                                <option value="">-- Tanpa Member --</option>
                                                @foreach ($members as $m)
                                                    <option value="{{ $m->id }}" {{ (isset($row['member_id']) && $row['member_id'] == $m->id) ? 'selected' : '' }}>
                                                        {{ $m->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="p-3">
                                            <input 
                                                type="text" 
                                                name="rows[{{ $idx }}][notes]" 
                                                value="{{ $row['notes'] ?? '' }}" 
                                                placeholder="Catatan..." 
                                                class="field-input h-8 text-[11px] py-1 px-2 w-full"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-line flex items-center justify-between bg-sidebar/30">
                        <div class="text-xs text-muted">
                            Pastikan data di atas sudah sesuai sebelum menekan tombol simpan.
                        </div>
                        <button type="submit" class="btn-arus-primary px-6 py-2.5 text-xs font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>Impor {{ count($previewData['rows']) }} Transaksi Sekarang</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <script>
            function toggleAllRows(masterCheckbox) {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = masterCheckbox.checked;
                });
            }
        </script>
    @endif
</x-layouts.app>
