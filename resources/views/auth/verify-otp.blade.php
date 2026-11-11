<x-layouts.guest title="Verifikasi Kode OTP">
    <div class="mb-6">
        <span class="text-[11px] font-bold tracking-[1.5px] text-forest uppercase block mb-1">VERIFIKASI EMAIL</span>
        <h2 class="text-2xl font-serif text-charcoal font-normal">Masukkan Kode OTP</h2>
        <p class="text-xs text-muted mt-1 leading-relaxed">
            Kode verifikasi 6 digit telah dikirimkan ke alamat email:
            <strong class="text-charcoal block mt-0.5 font-mono">{{ $email }}</strong>
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs rounded-xl flex items-center gap-2">
            <span>✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 p-3 bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs rounded-xl flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('register.otp.verify') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label for="otp" class="block text-xs font-semibold text-charcoal mb-1.5 text-center">
                6 Digit Kode OTP
            </label>
            <div class="relative max-w-xs mx-auto">
                <input 
                    type="text" 
                    name="otp" 
                    id="otp" 
                    maxlength="6" 
                    inputmode="numeric" 
                    pattern="[0-9]*" 
                    required 
                    autofocus 
                    autocomplete="one-time-code"
                    value="{{ old('otp') }}" 
                    placeholder="------" 
                    class="w-full text-center py-3.5 px-3 sm:px-4 text-2xl sm:text-3xl font-mono font-extrabold tracking-[6px] sm:tracking-[14px] bg-panel text-charcoal border @error('otp') border-red @else border-line @enderror rounded-xl shadow-xs focus:ring-2 focus:ring-forest focus:border-forest uppercase"
                >
            </div>
            @error('otp')
                <div class="mt-2 text-center text-xs text-red font-medium">
                    <span>{{ $message }}</span>
                </div>
            @enderror
            <span class="block text-center text-[11px] text-muted mt-2">
                Kode berlaku selama 10 menit sejak dikirimkan.
            </span>
        </div>

        <div class="pt-2">
            <x-button type="submit" class="w-full">
                Verifikasi & Aktifkan Akun
            </x-button>
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-line space-y-3 text-center">
        <form method="POST" action="{{ route('register.otp.resend') }}" id="resendForm">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <p class="text-xs text-muted mb-2">
                Tidak menerima email? Periksa folder spam atau kirim ulang kode.
            </p>
            <button 
                type="submit" 
                id="btnResend" 
                @if($cooldownSeconds > 0) disabled @endif
                class="min-h-[44px] sm:min-h-0 px-3 py-2 sm:py-0 inline-flex items-center justify-center text-xs font-bold text-forest hover:text-forest-hover disabled:text-muted disabled:cursor-not-allowed transition cursor-pointer"
            >
                <span id="resendText">
                    @if($cooldownSeconds > 0)
                        Kirim ulang kode dalam <span id="countdown">{{ $cooldownSeconds }}</span> detik
                    @else
                        Kirim Ulang Kode OTP
                    @endif
                </span>
            </button>
        </form>

        <div>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center min-h-[44px] sm:min-h-0 px-2 py-1 sm:py-0 text-[11px] text-muted hover:text-charcoal underline">
                Ganti alamat email / Daftar ulang
            </a>
        </div>
    </div>

    @if($cooldownSeconds > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let seconds = {{ $cooldownSeconds }};
                const btn = document.getElementById('btnResend');
                const countdown = document.getElementById('countdown');
                const resendText = document.getElementById('resendText');

                const timer = setInterval(() => {
                    seconds--;
                    if (countdown) {
                        countdown.textContent = seconds;
                    }
                    if (seconds <= 0) {
                        clearInterval(timer);
                        btn.disabled = false;
                        resendText.textContent = 'Kirim Ulang Kode OTP';
                    }
                }, 1000);
            });
        </script>
    @endif
</x-layouts.guest>
