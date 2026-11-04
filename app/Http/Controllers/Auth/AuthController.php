<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Mail\RegistrationOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();
        $personalSpace = $user->personalSpace() ?: $user->activeSpaces()->first();

        if ($personalSpace) {
            session(['active_space_id' => $personalSpace->id]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View|RedirectResponse
    {
        if (! config('arus.registration_open', true)) {
            return redirect()->route('login')->with('warning', 'Pendaftaran publik saat ini sedang ditutup.');
        }

        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = strtolower(trim($validated['email']));
        $otpCode = EmailOtp::generateCode();

        EmailOtp::updateOrCreate(
            ['email' => $email],
            [
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'otp_code' => $otpCode,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        try {
            Mail::to($email)->send(new RegistrationOtpMail($validated['name'], $otpCode, 10));
        } catch (\Throwable $e) {
            report($e);
        }

        session([
            'registration_email' => $email,
            'otp_sent_at' => now()->timestamp,
        ]);

        return redirect()->route('register.otp.show')
            ->with('success', 'Kode OTP telah dikirimkan ke email Anda. Silakan masukkan 6 digit kode untuk verifikasi.');
    }

    public function showVerifyOtp(Request $request): View|RedirectResponse
    {
        $email = session('registration_email') ?: $request->query('email');

        if (! $email) {
            return redirect()->route('register')->with('warning', 'Silakan lengkapi formulir pendaftaran terlebih dahulu.');
        }

        $pending = EmailOtp::where('email', $email)->first();
        if (! $pending) {
            return redirect()->route('register')->with('warning', 'Sesi pendaftaran tidak ditemukan atau sudah kadaluarsa. Silakan daftar kembali.');
        }

        $cooldownSeconds = 0;
        $sentAt = session('otp_sent_at') ?: $pending->updated_at->timestamp;
        $elapsed = now()->timestamp - $sentAt;
        if ($elapsed < 60) {
            $cooldownSeconds = 60 - $elapsed;
        }

        return view('auth.verify-otp', [
            'email' => $email,
            'expiresAt' => $pending->expires_at,
            'cooldownSeconds' => $cooldownSeconds,
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = strtolower(trim($validated['email']));

        $emailOtp = EmailOtp::where('email', $email)->first();

        if (! $emailOtp || $emailOtp->isExpired()) {
            return back()->withErrors([
                'otp' => 'Kode OTP telah kadaluarsa atau tidak ditemukan. Silakan kirim ulang kode baru.',
            ])->withInput();
        }

        if ($emailOtp->isMaxAttemptsReached()) {
            return back()->withErrors([
                'otp' => 'Batas percobaan salah telah tercapai (5 kali). Silakan minta kirim ulang kode OTP baru.',
            ])->withInput();
        }

        if (! $emailOtp->isValid($validated['otp'])) {
            $emailOtp->increment('attempts');
            $remainingAttempts = max(0, 5 - $emailOtp->attempts);

            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa {$remainingAttempts} kesempatan percobaan.",
            ])->withInput();
        }

        if (User::where('email', $email)->exists()) {
            $emailOtp->delete();

            return redirect()->route('login')->with('warning', 'Email ini sudah terdaftar. Silakan masuk dengan kata sandi Anda.');
        }

        $user = DB::transaction(function () use ($emailOtp) {
            $user = User::create([
                'name' => $emailOtp->name,
                'email' => $emailOtp->email,
                'password' => $emailOtp->password,
                'email_verified_at' => now(),
            ]);

            $user->ensurePersonalSpace();
            $emailOtp->delete();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['registration_email', 'otp_sent_at']);

        $personalSpace = $user->personalSpace();
        if ($personalSpace) {
            session(['active_space_id' => $personalSpace->id]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Selamat datang di ARUS! Email berhasil diverifikasi dan ruang keuangan personal Anda telah disiapkan.');
    }

    public function resendOtp(ResendOtpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = strtolower(trim($validated['email']));

        $emailOtp = EmailOtp::where('email', $email)->first();

        if (! $emailOtp) {
            return redirect()->route('register')->with('warning', 'Data pendaftaran tidak ditemukan. Silakan isi form pendaftaran kembali.');
        }

        $sentAt = session('otp_sent_at') ?: $emailOtp->updated_at->timestamp;
        $elapsed = now()->timestamp - $sentAt;
        if ($elapsed < 60) {
            $secondsLeft = 60 - $elapsed;

            return back()->with('warning', "Harap tunggu {$secondsLeft} detik lagi sebelum meminta kode OTP baru.");
        }

        $newOtpCode = EmailOtp::generateCode();
        $emailOtp->update([
            'otp_code' => $newOtpCode,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($email)->send(new RegistrationOtpMail($emailOtp->name, $newOtpCode, 10));
        } catch (\Throwable $e) {
            report($e);
        }

        session([
            'registration_email' => $email,
            'otp_sent_at' => now()->timestamp,
        ]);

        return back()->with('success', 'Kode OTP baru telah berhasil dikirimkan ke email Anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
