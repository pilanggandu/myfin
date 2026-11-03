<x-layouts.app title="Ubah Pengaturan Ruang">
    <div class="mb-6">
        <a href="{{ route('spaces.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1">
            &larr; Kembali ke daftar ruang
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / PENGATURAN RUANG</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
            Ubah Pengaturan Ruang
        </h1>
        <p class="text-xs text-muted">Ubah nama atau zona waktu ruang <strong class="text-charcoal">{{ $space->name }}</strong>.</p>
    </div>

    <div class="max-w-xl">
        <x-panel>
            <form method="POST" action="{{ route('spaces.update', $space) }}" novalidate>
                @csrf
                @method('PUT')

                <x-input 
                    name="name" 
                    label="Nama Ruang" 
                    :value="$space->name" 
                    required 
                    autofocus 
                />

                <x-select 
                    name="timezone" 
                    label="Zona Waktu"
                    :options="[
                        'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                        'Asia/Makassar' => 'Asia/Makassar (WITA)',
                        'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                    ]"
                    :selected="$space->timezone"
                />

                <div class="flex items-center gap-3 pt-3">
                    <x-button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-button>
                    <a href="{{ route('spaces.index') }}" class="btn-arus-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </x-panel>
    </div>
</x-layouts.app>
