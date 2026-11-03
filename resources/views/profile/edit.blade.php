<x-layouts.app title="Pengaturan Profil">
    <!-- Header -->
    <div class="mb-6">
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / PROFIL</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
            Pengaturan Profil Akun
        </h1>
        <p class="text-xs text-muted">Perbarui identitas diri, kata sandi keamanan, dan preferensi tema tampilan.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informasi Profil -->
        <x-panel title="Informasi Akun">
            <form method="POST" action="{{ route('profile.update') }}" novalidate>
                @csrf
                @method('PATCH')

                <x-input 
                    name="name" 
                    label="Nama Lengkap" 
                    :value="$user->name" 
                    required 
                />

                <x-input 
                    name="email" 
                    label="Alamat Email" 
                    type="email" 
                    :value="$user->email" 
                    required 
                />

                <div class="pt-3">
                    <x-button type="submit" variant="primary">
                        Perbarui Informasi
                    </x-button>
                </div>
            </form>
        </x-panel>

        <!-- Ubah Kata Sandi -->
        <x-panel title="Ubah Kata Sandi">
            <form method="POST" action="{{ route('profile.password') }}" novalidate>
                @csrf
                @method('PUT')

                <x-input 
                    name="current_password" 
                    label="Kata Sandi Saat Ini" 
                    type="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi saat ini" 
                />

                <x-input 
                    name="password" 
                    label="Kata Sandi Baru" 
                    type="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter" 
                />

                <x-input 
                    name="password_confirmation" 
                    label="Konfirmasi Kata Sandi Baru" 
                    type="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi baru" 
                />

                <div class="pt-3">
                    <x-button type="submit" variant="primary">
                        Ubah Kata Sandi
                    </x-button>
                </div>
            </form>
        </x-panel>

        <!-- Preferensi Tema Tampilan -->
        <div class="lg:col-span-2">
            <x-panel title="Preferensi Tema Tampilan" subtitle="Pilih nuansa tampilan antarmuka yang paling nyaman untuk Anda.">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <button type="button" onclick="setArusTheme('light')" data-theme-value="light" class="p-4 rounded-xl border border-line bg-panel hover:bg-cream transition text-left cursor-pointer group focus-visible:outline-none">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl">☀️</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted bg-cream px-2 py-0.5 rounded">TERANG</span>
                        </div>
                        <div class="font-bold text-sm text-charcoal">Mode Terang</div>
                        <p class="text-xs text-muted mt-1 m-0">Latar hangat ivory dengan panel putih bersih dan teks charcoal.</p>
                    </button>

                    <button type="button" onclick="setArusTheme('dark')" data-theme-value="dark" class="p-4 rounded-xl border border-line bg-panel hover:bg-cream transition text-left cursor-pointer group focus-visible:outline-none">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl">🌙</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted bg-cream px-2 py-0.5 rounded">GELAP</span>
                        </div>
                        <div class="font-bold text-sm text-charcoal">Mode Gelap</div>
                        <p class="text-xs text-muted mt-1 m-0">Latar gelap forest-charcoal tenang dan nyaman untuk mata di malam hari.</p>
                    </button>

                    <button type="button" onclick="setArusTheme('system')" data-theme-value="system" class="p-4 rounded-xl border border-line bg-panel hover:bg-cream transition text-left cursor-pointer group focus-visible:outline-none">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl">💻</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted bg-cream px-2 py-0.5 rounded">OTOMATIS</span>
                        </div>
                        <div class="font-bold text-sm text-charcoal">Ikuti Sistem</div>
                        <p class="text-xs text-muted mt-1 m-0">Menyesuaikan mode secara otomatis dengan preferensi tema perangkat Anda.</p>
                    </button>
                </div>
            </x-panel>
        </div>

        <!-- ZONA BAHAYA: BERSIHKAN / RESET DATA PEMBUKUAN -->
        <div class="lg:col-span-2">
            <div class="panel-card p-6 rounded-2xl border border-rose-500/30 bg-rose-500/5 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-rose-500/20 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black tracking-wider uppercase bg-rose-600 text-white">
                                DANGER ZONE
                            </span>
                            <h3 class="font-serif text-xl text-rose-600 dark:text-rose-400 font-bold">Bersihkan Data Pembukuan</h3>
                        </div>
                        <p class="text-xs text-muted mt-1">
                            Hapus transaksi uji coba, bersihkan data per modul, atau reset penuh seluruh data keuangan ruang kerja Anda.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button 
                            type="button" 
                            onclick="openCleanDataModal('custom')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold border border-rose-500/40 bg-panel text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
                        >
                            Pilih Per Modul...
                        </button>
                        <button 
                            type="button" 
                            onclick="openCleanDataModal('all')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white transition shadow-xs cursor-pointer inline-flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            <span>Hapus Semua Data</span>
                        </button>
                    </div>
                </div>

                <!-- Informasi Ringkasan Modul yang Dapat Dibersihkan -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-panel border border-line">
                        <div class="font-bold text-charcoal flex items-center gap-1.5">
                            <span>💸</span>
                            <span>Transaksi & Jurnal</span>
                        </div>
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">Menghapus mutasi kas masuk, keluar, transfer, dan log audit entri tanpa merusak rekening.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-panel border border-line">
                        <div class="font-bold text-charcoal flex items-center gap-1.5">
                            <span>📊</span>
                            <span>Anggaran & Jadwal</span>
                        </div>
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">Menghapus batas limit anggaran bulanan dan jadwal tagihan berulang.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-panel border border-line">
                        <div class="font-bold text-charcoal flex items-center gap-1.5">
                            <span>💳</span>
                            <span>Rekening & Member</span>
                        </div>
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">Reset saldo awal rekening ke Rp 0 atau hapus akun rekening & master member.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Bersihkan Data -->
    <div id="cleanDataModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        ⚠️
                    </span>
                    <div>
                        <h3 class="font-serif text-lg text-rose-600 dark:text-rose-400 font-bold">
                            Konfirmasi Pembersihan Data
                        </h3>
                        <p class="text-[11px] text-muted">
                            Pilih modul keuangan yang ingin Anda bersihkan secara permanen.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeCleanDataModal()" class="p-1 rounded-lg text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Peringatan Danger -->
            <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-start gap-2">
                <svg class="w-5 h-5 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="leading-relaxed">
                    <strong>PERINGATAN KERAS:</strong> Tindakan ini bersifat permanen dan tidak dapat dibatalkan. Seluruh transaksi dan data yang dipilih akan dihapus tuntas dari sistem.
                </div>
            </div>

            <form method="POST" action="{{ route('profile.clean-data') }}" id="cleanDataForm" class="space-y-4">
                @csrf

                <!-- Pilihan Ruang Keuangan -->
                <div>
                    <label for="cleanSpaceSelect" class="block text-xs font-bold text-charcoal mb-1">
                        Pilih Ruang Keuangan yang Dibersihkan <span class="text-rose-500">*</span>
                    </label>
                    <select name="space_id" id="cleanSpaceSelect" class="field-select w-full text-xs" required>
                        @foreach ($ownedSpaces as $sp)
                            <option value="{{ $sp->id }}" {{ $currentSpace && $currentSpace->id === $sp->id ? 'selected' : '' }}>
                                {{ $sp->name }} ({{ $sp->type === 'personal' ? 'Personal' : 'Bersama' }})
                            </option>
                        @endforeach
                    </select>
                    <div class="text-[11px] text-muted mt-1">
                        Hanya ruang yang Anda miliki (Owner) yang dapat dibersihkan.
                    </div>
                </div>

                <!-- Checkbox Pilihan Modul -->
                <div class="space-y-2 border-t border-line pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-charcoal">Modul yang Ingin Dibersihkan:</span>
                        <button type="button" onclick="toggleSelectAllModules()" id="btnSelectAllModules" class="text-xs text-forest hover:underline font-bold cursor-pointer">
                            Pilih Semua
                        </button>
                    </div>

                    <!-- Modul Transaksi -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="transactions" value="1" id="chk_transactions" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" checked onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Transaksi & Jurnal Pembukuan</div>
                            <div class="text-[11px] text-muted">Menghapus seluruh pemasukan, pengeluaran, transfer, dan log audit mutasi.</div>
                        </div>
                    </label>

                    <!-- Modul Anggaran -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="budgets" value="1" id="chk_budgets" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Batas Anggaran Bulanan (Budgets)</div>
                            <div class="text-[11px] text-muted">Menghapus seluruh limit anggaran pada kategori pengeluaran.</div>
                        </div>
                    </label>

                    <!-- Modul Jadwal Berulang -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="recurring" value="1" id="chk_recurring" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Jadwal Berulang (Recurring)</div>
                            <div class="text-[11px] text-muted">Menghapus seluruh jadwal pembayaran dan transfer berkala beserta riwayat kejadiannya.</div>
                        </div>
                    </label>

                    <!-- Modul Saldo Rekening -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="reset_account_balances" value="1" id="chk_reset_balances" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Reset Saldo Awal Rekening ke Nol</div>
                            <div class="text-[11px] text-muted">Mengubah angka saldo dasar pembukuan seluruh rekening menjadi Rp 0.</div>
                        </div>
                    </label>

                    <!-- Modul Rekening -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="accounts" value="1" id="chk_accounts" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Hapus Seluruh Rekening</div>
                            <div class="text-[11px] text-muted">Menghapus semua akun kas, bank, dan dompet digital (juga mewajibkan hapus transaksi).</div>
                        </div>
                    </label>

                    <!-- Modul Member -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="members" value="1" id="chk_members" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Master Member</div>
                            <div class="text-[11px] text-muted">Menghapus data member pihak ketiga (Me, Parent, Family, dll).</div>
                        </div>
                    </label>

                    <!-- Modul Kategori -->
                    <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-cream transition cursor-pointer">
                        <input type="checkbox" name="reset_categories" value="1" id="chk_categories" class="clean-module-chk rounded border-line text-rose-600 focus:ring-rose-500 mt-0.5" onchange="onModuleCheckboxChange()">
                        <div>
                            <div class="text-xs font-bold text-charcoal">Reset Kategori ke Standar Sistem</div>
                            <div class="text-[11px] text-muted">Menghapus kategori buatan sendiri dan mengembalikan kategori default sistem.</div>
                        </div>
                    </label>
                </div>

                <!-- Konfirmasi Kata Sandi Akun -->
                <div class="border-t border-line pt-3">
                    <label for="cleanPassword" class="block text-xs font-bold text-charcoal mb-1">
                        Konfirmasi Kata Sandi Akun Anda <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="cleanPassword" 
                        placeholder="Ketik kata sandi akun untuk verifikasi keamanan" 
                        class="field-input w-full text-xs" 
                        required 
                        autocomplete="current-password"
                    />
                    <div class="text-[11px] text-muted mt-1">
                        Diperlukan untuk memastikan otorisasi pemilik akun sebelum pembersihan dieksekusi.
                    </div>
                </div>

                <!-- Tombol Aksi Modal -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeCleanDataModal()" class="btn-arus-secondary px-4 py-2 text-xs font-bold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitCleanData" class="btn-arus-primary bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 text-xs font-bold cursor-pointer inline-flex items-center gap-1.5">
                        <svg id="cleanSpinner" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="cleanSubmitLabel">Konfirmasi & Bersihkan Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCleanDataModal(mode) {
            const modal = document.getElementById('cleanDataModal');
            if (!modal) return;
            
            const chks = document.querySelectorAll('.clean-module-chk');
            if (mode === 'all') {
                chks.forEach(chk => chk.checked = true);
            } else {
                chks.forEach(chk => chk.checked = (chk.id === 'chk_transactions'));
            }

            const pwd = document.getElementById('cleanPassword');
            if (pwd) pwd.value = '';

            modal.classList.remove('hidden');
            setTimeout(() => {
                if (pwd) pwd.focus();
            }, 100);
        }

        function closeCleanDataModal() {
            const modal = document.getElementById('cleanDataModal');
            if (modal) modal.classList.add('hidden');
        }

        function toggleSelectAllModules() {
            const chks = Array.from(document.querySelectorAll('.clean-module-chk'));
            const allChecked = chks.every(c => c.checked);
            chks.forEach(c => c.checked = !allChecked);
            const btn = document.getElementById('btnSelectAllModules');
            if (btn) btn.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih Semua';
        }

        function onModuleCheckboxChange() {
            const chkAccounts = document.getElementById('chk_accounts');
            const chkCategories = document.getElementById('chk_categories');
            const chkTx = document.getElementById('chk_transactions');

            if (chkAccounts && chkAccounts.checked && chkTx) {
                chkTx.checked = true;
            }
            if (chkCategories && chkCategories.checked && chkTx) {
                chkTx.checked = true;
            }
        }

        document.getElementById('cleanDataForm')?.addEventListener('submit', function(e) {
            const chks = document.querySelectorAll('.clean-module-chk:checked');
            if (chks.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu modul data yang ingin dibersihkan.');
                return false;
            }

            const btn = document.getElementById('btnSubmitCleanData');
            const spinner = document.getElementById('cleanSpinner');
            const label = document.getElementById('cleanSubmitLabel');
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.textContent = 'Membersihkan Data...';
            if (btn) btn.classList.add('opacity-75', 'pointer-events-none');
        });
    </script>
</x-layouts.app>
