<x-layouts.guest title="Lupa Kata Sandi">
    <div class="mb-6">
        <span class="text-[11px] font-bold tracking-[1.5px] text-forest uppercase block mb-1">RESET KATA SANDI</span>
        <h2 class="text-2xl font-serif text-charcoal font-normal">Lupa Kata Sandi?</h2>
        <p class="text-xs text-muted mt-1 leading-relaxed">
            Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan 6 digit kode OTP untuk mengatur ulang kata sandi.
        </p>
    </div>

    @if (session('warning'))
        <div class="mb-4 p-3 bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs rounded-xl flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate class="space-y-4">
        @csrf

        <x-input 
            name="email" 
            label="Alamat Email Akun" 
            type="email" 
            placeholder="nama@email.com" 
            required 
            autofocus 
            value="{{ old('email', session('password_reset_email')) }}"
            autocomplete="email" 
        />

        <div class="pt-1">
            <x-button type="submit" class="w-full">
                Kirim Kode OTP Reset
            </x-button>
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-line text-center">
        <p class="text-xs text-muted">
            Ingat kata sandi akun Anda? 
            <a href="{{ route('login') }}" class="font-bold text-forest hover:underline">
                Kembali ke Halaman Masuk
            </a>
        </p>
    </div>
</x-layouts.guest>
