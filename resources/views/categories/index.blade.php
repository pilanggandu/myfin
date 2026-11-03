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
                <x-badge variant="default">INCOME</x-badge>
            </x-slot:header>

            <form method="POST" action="{{ route('categories.store') }}" class="mb-6 p-4 rounded-xl bg-sidebar/50 border border-line" novalidate>
                @csrf
                <input type="hidden" name="type" value="income">
                <div class="text-xs font-bold text-forest mb-2">+ Tambah Kategori Pemasukan</div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="name" 
                            placeholder="Contoh: Gaji Pokok, Freelance" 
                            class="field-input h-10 py-1.5 text-xs" 
                            required 
                        />
                    </div>
                    <button type="submit" class="btn-arus-primary h-10 min-h-[40px] px-4 text-xs font-bold whitespace-nowrap">
                        Simpan
                    </button>
                </div>
            </form>

            <div class="divide-y divide-line">
                @foreach ($incomeCategories as $category)
                    <div class="py-3 flex items-center justify-between gap-2 {{ $category->isArchived() ? 'opacity-50' : '' }}">
                        <div>
                            <span class="font-bold text-sm text-charcoal">{{ $category->name }}</span>
                            @if ($category->isArchived())
                                <span class="text-[10px] text-muted bg-cream px-1.5 py-0.5 rounded ml-2">Arsip</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if ($category->isArchived())
                                <form method="POST" action="{{ route('categories.unarchive', $category) }}" class="inline">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
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
                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
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
                <x-badge variant="default">EXPENSE</x-badge>
            </x-slot:header>

            <form method="POST" action="{{ route('categories.store') }}" class="mb-6 p-4 rounded-xl bg-sidebar/50 border border-line" novalidate>
                @csrf
                <input type="hidden" name="type" value="expense">
                <div class="text-xs font-bold text-forest mb-2">+ Tambah Kategori Pengeluaran</div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="name" 
                            placeholder="Contoh: Makanan, Transportasi" 
                            class="field-input h-10 py-1.5 text-xs" 
                            required 
                        />
                    </div>
                    <button type="submit" class="btn-arus-primary h-10 min-h-[40px] px-4 text-xs font-bold whitespace-nowrap">
                        Simpan
                    </button>
                </div>
            </form>

            <div class="divide-y divide-line">
                @foreach ($expenseCategories as $category)
                    <div class="py-3 flex items-center justify-between gap-2 {{ $category->isArchived() ? 'opacity-50' : '' }}">
                        <div>
                            <span class="font-bold text-sm text-charcoal">{{ $category->name }}</span>
                            @if ($category->isArchived())
                                <span class="text-[10px] text-muted bg-cream px-1.5 py-0.5 rounded ml-2">Arsip</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if ($category->isArchived())
                                <form method="POST" action="{{ route('categories.unarchive', $category) }}" class="inline">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
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
                                        class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
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
</x-layouts.app>
