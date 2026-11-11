<x-layouts.guest title="Masuk ke ARUS">
    <div class="mb-6">
        <span class="text-[11px] font-bold tracking-[1.5px] text-forest uppercase block mb-1">AUTENTIKASI</span>
        <h2 class="text-2xl font-serif text-charcoal font-normal">Masuk ke Akun Anda</h2>
        <p class="text-xs text-muted mt-1">Gunakan akun Google atau email dan kata sandi Anda.</p>
    </div>

    @if (session('warning'))
        <div class="mb-4 p-3 bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs rounded-xl flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs rounded-xl flex items-center gap-2">
            <span>✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="mb-5">
        <a 
            href="{{ route('auth.google.redirect') }}" 
            class="w-full min-h-[44px] px-4 py-2.5 rounded-xl border border-line bg-panel hover:bg-cream/70 text-charcoal text-xs font-semibold flex items-center justify-center gap-3 transition shadow-xs cursor-pointer"
        >
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Masuk dengan Google</span>
        </a>
    </div>

    <div class="relative my-5">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-line"></div>
        </div>
        <div class="relative flex justify-center text-[11px] uppercase tracking-wider">
            <span class="bg-card px-3 text-muted">atau dengan email</span>
        </div>
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

        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <x-checkbox 
                name="remember" 
                label="Ingat saya" 
            />
            <a href="{{ route('password.request') }}" class="text-xs text-forest hover:text-forest-hover font-semibold inline-flex items-center min-h-[44px] sm:min-h-0">
                Lupa kata sandi?
            </a>
        </div>

        <x-button type="submit" class="w-full mt-2 min-h-[44px] sm:min-h-[38px]">
            Masuk ke Aplikasi
        </x-button>
    </form>

    @if (config('arus.registration_open', true))
        <div class="mt-6 pt-5 border-t border-line text-center">
            <p class="text-xs text-muted">
                Belum memiliki akun? 
                <a href="{{ route('register') }}" class="font-bold text-forest hover:underline inline-flex items-center min-h-[44px] sm:min-h-0 px-1">
                    Daftar akun baru
                </a>
            </p>
        </div>
    @endif
</x-layouts.guest>
