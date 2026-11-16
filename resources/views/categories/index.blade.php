<x-layouts.app title="Kategori Keuangan">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / KATEGORI</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Kategori Keuangan
            </h1>
            <p class="text-xs text-muted">Klasifikasikan sumber pemasukan dan pos pengeluaran dalam ruang aktif ini.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-panel title="Kategori Pemasukan">
            <x-slot:header>
                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900">INCOME</span>
            </x-slot:header>

            <form method="POST" action="{{ route('categories.store') }}" class="mb-6 p-4 rounded-xl bg-sidebar/50 border border-line" novalidate>
                @csrf
                <input type="hidden" name="type" value="income">
                <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-2">+ Tambah Kategori Pemasukan</div>
                <div class="flex flex-col min-[360px]:flex-row gap-2">
                    <div class="flex-1 min-w-0">
                        <input 
                            type="text" 
                            name="name" 
                            placeholder="Contoh: Gaji Pokok, Freelance" 
                            class="field-input h-11 sm:h-10 py-1.5 text-xs" 
                            required 
                        />
                    </div>
                    <button type="submit" class="btn-arus-primary h-11 sm:h-10 min-h-[44px] sm:min-h-[40px] px-4 text-xs font-bold whitespace-nowrap">
                        Simpan
                    </button>
                </div>
            </form>

            <div class="divide-y divide-line">
                @foreach ($incomeCategories as $category)
                    <div class="py-3 flex items-center justify-between gap-2 {{ $category->isArchived() ? 'opacity-50' : '' }}">
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-sm text-charcoal break-words">{{ $category->name }}</span>
                            @if ($category->isArchived())
                                <span class="text-[10px] text-muted bg-cream px-1.5 py-0.5 rounded ml-2">Arsip</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if ($category->isArchived())
                                <form method="POST" action="{{ route('categories.unarchive', $category) }}" class="inline">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                        title="Aktifkan Kembali Kategori"
                                        aria-label="Aktifkan Kembali Kategori"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('categories.archive', $category) }}" class="inline" onsubmit="return confirm('Arsipkan kategori ini? Kategori arsip tidak dapat digunakan untuk transaksi baru.')">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                        title="Arsipkan Kategori"
                                        aria-label="Arsipkan Kategori"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-panel>

        <x-panel title="Kategori Pengeluaran">
            <x-slot:header>
                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900">EXPENSE</span>
            </x-slot:header>

            <form method="POST" action="{{ route('categories.store') }}" class="mb-6 p-4 rounded-xl bg-sidebar/50 border border-line" novalidate>
                @csrf
                <input type="hidden" name="type" value="expense">
                <div class="text-xs font-bold text-rose-600 dark:text-rose-400 mb-2">+ Tambah Kategori Pengeluaran</div>
                <div class="flex flex-col min-[360px]:flex-row gap-2">
                    <div class="flex-1 min-w-0">
                        <input 
                            type="text" 
                            name="name" 
                            placeholder="Contoh: Makanan, Transportasi" 
                            class="field-input h-11 sm:h-10 py-1.5 text-xs" 
                            required 
                        />
                    </div>
                    <button type="submit" class="btn-arus-primary h-11 sm:h-10 min-h-[44px] sm:min-h-[40px] px-4 text-xs font-bold whitespace-nowrap">
                        Simpan
                    </button>
                </div>
            </form>

            <div class="divide-y divide-line">
                @foreach ($expenseCategories as $category)
                    <div class="py-3 flex items-center justify-between gap-2 {{ $category->isArchived() ? 'opacity-50' : '' }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold text-sm text-charcoal break-words">{{ $category->name }}</span>
                                @if ($category->isRolloverEnabled())
                                    <span class="text-[10px] text-forest bg-forest/10 border border-forest/20 px-1.5 py-0.5 rounded font-medium" title="Mode: {{ $category->rolloverModeLabel() }}{{ $category->rollover_cap ? ', Maks Cap: Rp ' . number_format($category->rollover_cap, 0, ',', '.') : '' }}">
                                        🔄 Bergulir ({{ $category->rolloverModeLabel() }})
                                    </span>
                                @endif
                                @if ($category->isArchived())
                                    <span class="text-[10px] text-muted bg-cream px-1.5 py-0.5 rounded">Arsip</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if (! $category->isArchived())
                                <button 
                                    type="button" 
                                    onclick="openRolloverModal({{ $category->id }}, '{{ addslashes($category->name) }}', {{ $category->rollover_enabled ? 'true' : 'false' }}, '{{ $category->rollover_mode }}', {{ $category->rollover_cap ?? 'null' }})"
                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                    title="Atur Anggaran Bergulir"
                                    aria-label="Atur Anggaran Bergulir"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </button>
                            @endif

                            @if ($category->isArchived())
                                <form method="POST" action="{{ route('categories.unarchive', $category) }}" class="inline">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                        title="Aktifkan Kembali Kategori"
                                        aria-label="Aktifkan Kembali Kategori"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('categories.archive', $category) }}" class="inline" onsubmit="return confirm('Arsipkan kategori ini? Kategori arsip tidak dapat digunakan untuk transaksi baru.')">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                        title="Arsipkan Kategori"
                                        aria-label="Arsipkan Kategori"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-panel>
    </div>

    <!-- Modal Pengaturan Anggaran Bergulir (Rollover) -->
    <div id="rollover-settings-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-serif text-xl text-forest font-normal mb-1">Pengaturan Anggaran Bergulir</h3>
                    <p class="text-xs text-muted" id="rollover-modal-category-label">
                        Kategori: <strong class="text-charcoal" id="rollover-modal-category-name"></strong>
                    </p>
                </div>
                <button type="button" onclick="document.getElementById('rollover-settings-modal').classList.add('hidden')" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer inline-flex items-center justify-center shrink-0" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="rollover-settings-form" method="POST" action="" class="space-y-4">
                @csrf

                <!-- Toggle Aktif -->
                <div class="p-3.5 rounded-xl bg-sidebar/50 border border-line">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="rollover_enabled" value="0">
                        <input 
                            type="checkbox" 
                            name="rollover_enabled" 
                            id="modal_rollover_enabled" 
                            value="1" 
                            class="rounded border-line text-forest focus:ring-forest/20 w-4 h-4 mt-0.5 cursor-pointer"
                        />
                        <div class="text-xs">
                            <span class="font-bold text-charcoal block">Aktifkan Anggaran Bergulir (Rollover)</span>
                            <span class="text-muted block mt-0.5 leading-relaxed">
                                Sisa anggaran (surplus) atau kelebihan pengeluaran (defisit) bulan lalu akan otomatis dialihkan ke plafon anggaran bulan berikutnya.
                            </span>
                        </div>
                    </label>
                </div>

                <!-- Mode Rollover -->
                <div>
                    <label for="modal_rollover_mode" class="block text-xs font-bold text-charcoal mb-1.5 uppercase tracking-wider">
                        Aturan Pemindahan Saldo (Mode)
                    </label>
                    <select name="rollover_mode" id="modal_rollover_mode" class="field-select text-xs w-full">
                        <option value="all">Semua (Bawa Surplus Sisa & Kurangi Defisit)</option>
                        <option value="surplus_only">Surplus Saja (Hanya bawa sisa yang belum terpakai)</option>
                        <option value="deficit_only">Defisit Saja (Hanya kurangi plafon jika overbudget)</option>
                    </select>
                    <p class="text-[11px] text-muted mt-1 leading-relaxed">
                        Mode <strong>Semua</strong> sangat dianjurkan untuk menjaga kedisiplinan dan akurasi arus kas.
                    </p>
                </div>

                <!-- Batas Cap Surplus -->
                <div>
                    <label for="modal_rollover_cap" class="block text-xs font-bold text-charcoal mb-1.5 uppercase tracking-wider">
                        Batas Maksimal Surplus Bergulir (Cap)
                    </label>
                    <input 
                        type="number" 
                        name="rollover_cap" 
                        id="modal_rollover_cap" 
                        placeholder="Contoh: 500000 (Kosongkan jika tanpa batas)" 
                        class="field-input text-xs w-full font-mono font-bold" 
                        min="0"
                        step="1000"
                        onfocus="this.select()"
                        onclick="this.select()"
                    />
                    <p class="text-[11px] text-muted mt-1 leading-relaxed">
                        Membatasi nominal surplus yang dapat bergulir agar tidak terakumulasi melebihi batas. Kosongkan jika tanpa batas.
                    </p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="document.getElementById('rollover-settings-modal').classList.add('hidden')" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] cursor-pointer">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRolloverModal(categoryId, categoryName, isEnabled, mode, cap) {
            var form = document.getElementById('rollover-settings-form');
            form.action = '/categories/' + categoryId + '/rollover';
            document.getElementById('rollover-modal-category-name').innerText = categoryName;
            document.getElementById('modal_rollover_enabled').checked = Boolean(isEnabled);
            document.getElementById('modal_rollover_mode').value = mode || 'all';
            document.getElementById('modal_rollover_cap').value = (cap !== null && cap !== undefined && cap !== '') ? cap : '';
            document.getElementById('rollover-settings-modal').classList.remove('hidden');
        }
    </script>
</x-layouts.app>
