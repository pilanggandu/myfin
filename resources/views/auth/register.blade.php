<x-layouts.guest title="Daftar Akun Baru">
    <div class="mb-6">
        <span class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase block mb-1">REGISTRASI</span>
        <h2 class="text-2xl font-serif text-forest font-normal">Pendaftaran Akun Baru</h2>
        <p class="text-xs text-muted mt-1">Ruang personal Anda akan otomatis dibuat begitu pendaftaran selesai.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <x-input 
            name="name" 
            label="Nama Lengkap" 
            placeholder="Contoh: Budi Pratama" 
            required 
            autofocus 
            autocomplete="name" 
        />

        <x-input 
            name="email" 
            label="Alamat Email" 
            type="email" 
            placeholder="nama@email.com" 
            required 
            autocomplete="username" 
        />

        <x-input 
            name="password" 
            label="Kata Sandi" 
            type="password" 
            placeholder="Minimal 8 karakter" 
            required 
            autocomplete="new-password" 
        />

        <x-input 
            name="password_confirmation" 
            label="Konfirmasi Kata Sandi" 
            type="password" 
            placeholder="Ulangi kata sandi di atas" 
            required 
            autocomplete="new-password" 
        />

        <div class="pt-2">
            <x-button type="submit" class="w-full">
                Daftar dan Buat Ruang
            </x-button>
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-line text-center">
        <p class="text-xs text-muted">
            Sudah memiliki akun? 
            <a href="{{ route('login') }}" class="font-bold text-forest hover:underline">
                Masuk ke akun
            </a>
        </p>
    </div>
</x-layouts.guest>
