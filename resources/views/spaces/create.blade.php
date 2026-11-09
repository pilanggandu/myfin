<x-layouts.app title="Buat Ruang Bersama">
    <div class="mb-6">
        <a href="{{ route('spaces.index') }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1">
            &larr; Kembali ke daftar ruang
        </a>
        <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase mt-3">ARUS / RUANG BERSAMA</div>
        <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
            Buat Ruang Bersama Baru
        </h1>
        <p class="text-xs text-muted">Ruang bersama digunakan untuk mengelola keuangan rumah tangga atau tim bersama anggota lain.</p>
    </div>

    <div class="w-full">
        <x-panel>
            <form method="POST" action="{{ route('spaces.store') }}" novalidate>
                @csrf

                <x-input 
                    name="name" 
                    label="Nama Ruang Bersama" 
                    placeholder="Contoh: Keuangan Rumah Tangga" 
                    required 
                    autofocus 
                    hint="Beri nama yang jelas untuk membedakan ruang ini dari ruang personal Anda." 
                />

                <x-select 
                    name="timezone" 
                    label="Zona Waktu"
                    :options="[
                        'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                        'Asia/Makassar' => 'Asia/Makassar (WITA)',
                        'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                    ]"
                    :selected="config('arus.default_timezone', 'Asia/Jakarta')"
                />

                <div class="flex items-center gap-3 pt-3">
                    <x-button type="submit" variant="primary">
                        Simpan Ruang Baru
                    </x-button>
                    <a href="{{ route('spaces.index') }}" class="btn-arus-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </x-panel>
    </div>
</x-layouts.app>
