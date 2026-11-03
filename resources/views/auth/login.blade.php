<x-layouts.guest title="Masuk ke ARUS">
    <div class="mb-6">
        <span class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase block mb-1">AUTENTIKASI</span>
        <h2 class="text-2xl font-serif text-forest font-normal">Masuk ke Akun Anda</h2>
        <p class="text-xs text-muted mt-1">Gunakan alamat email dan kata sandi untuk mengelola ruang keuangan Anda.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <x-input 
            name="email" 
            label="Alamat Email" 
            type="email" 
            placeholder="nama@email.com" 
            required 
            autofocus 
            autocomplete="username" 
        />

        <x-input 
            name="password" 
            label="Kata Sandi" 
            type="password" 
            placeholder="Masukkan kata sandi akun" 
            required 
            autocomplete="current-password" 
        />

        <x-checkbox 
            name="remember" 
            label="Ingat saya di perangkat ini" 
        />

        <x-button type="submit" class="w-full mt-2">
            Masuk ke Aplikasi
        </x-button>
    </form>

    @if (config('arus.registration_open', true))
        <div class="mt-6 pt-5 border-t border-line text-center">
            <p class="text-xs text-muted">
                Belum memiliki akun? 
                <a href="{{ route('register') }}" class="font-bold text-forest hover:underline">
                    Daftar akun baru
                </a>
            </p>
        </div>
    @endif
</x-layouts.guest>
