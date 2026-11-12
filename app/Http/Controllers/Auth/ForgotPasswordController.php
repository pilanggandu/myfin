<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetOtpRequest;
use App\Http\Requests\Auth\VerifyPasswordResetOtpRequest;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetOtp(SendPasswordResetOtpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = strtolower(trim($validated['email']));

        $user = User::where('email', $email)->first();
        if (! $user) {
            return back()->withErrors(['email' => 'Alamat email ini tidak terdaftar dalam sistem kami.'])->withInput();
        }

        $otpCode = PasswordResetOtp::generateCode();

        PasswordResetOtp::where('email', $email)->delete();
        PasswordResetOtp::create([
            'email' => $email,
            'otp_code' => $otpCode,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new PasswordResetOtpMail(
            name: $user->name,
            otpCode: $otpCode,
            expiresMinutes: 10
        ));

        session([
            'password_reset_email' => $email,
            'password_reset_otp_sent_at' => now()->timestamp,
        ]);

        return redirect()->route('password.otp.show')
            ->with('success', 'Kode OTP 6 digit telah dikirimkan ke email Anda.');
    }

    public function showVerifyOtp(Request $request): View|RedirectResponse
    {
        $email = session('password_reset_email') ?: $request->query('email');
        if (! $email) {
            return redirect()->route('password.request')
                ->with('warning', 'Silakan masukkan alamat email Anda terlebih dahulu.');
        }

        $pending = PasswordResetOtp::where('email', $email)->first();
        if (! $pending) {
            return redirect()->route('password.request')
                ->with('warning', 'Permintaan reset kata sandi tidak ditemukan atau sudah kadaluarsa. Silakan ajukan kembali.');
        }

        $cooldownSeconds = 0;
        $sentAt = session('password_reset_otp_sent_at') ?: $pending->updated_at->timestamp;
        $elapsed = now()->timestamp - $sentAt;
        if ($elapsed < 60) {
            $cooldownSeconds = 60 - $elapsed;
        }

        return view('auth.verify-reset-otp', [
            'email' => $email,
            'expiresAt' => $pending->expires_at,
            'cooldownSeconds' => $cooldownSeconds,
        ]);
    }

    public function verifyResetOtp(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = strtolower(trim($validated['email']));

        $resetOtp = PasswordResetOtp::where('email', $email)->first();

        if (! $resetOtp || $resetOtp->isExpired()) {
            return back()->withErrors([
                'otp' => 'Kode OTP telah kadaluarsa atau tidak ditemukan. Silakan kirim ulang kode baru.',
            ])->withInput();
        }

        if ($resetOtp->isMaxAttemptsReached()) {
            return back()->withErrors([
                'otp' => 'Batas percobaan salah telah tercapai (5 kali). Silakan minta kirim ulang kode OTP baru.',
            ])->withInput();
        }

        if (! $resetOtp->isValid($validated['otp'])) {
            $resetOtp->increment('attempts');
            $remainingAttempts = max(0, 5 - $resetOtp->attempts);

            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa {$remainingAttempts} kesempatan percobaan.",
            ])->withInput();
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return redirect()->route('password.request')
                ->with('warning', 'Akun pengguna tidak ditemukan.');
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $resetOtp->delete();
        $request->session()->forget(['password_reset_email', 'password_reset_otp_sent_at']);

        $this->activityLog->log(
            module: 'auth',
            action: 'password_reset',
            description: 'Mereset kata sandi akun melalui kode OTP email',
            space: null,
            actor: $user,
            subject: $user,
            subjectLabel: $user->email,
            request: $request,
        );

        return redirect()->route('login')
            ->with('success', 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $email = strtolower(trim($request->input('email', session('password_reset_email', ''))));

        if (! $email) {
            return redirect()->route('password.request')
                ->with('warning', 'Sesi telah berakhir. Silakan masukkan email Anda kembali.');
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return redirect()->route('password.request')
                ->with('warning', 'Akun dengan email ini tidak ditemukan.');
        }

        $pending = PasswordResetOtp::where('email', $email)->first();
        $sentAt = session('password_reset_otp_sent_at') ?: ($pending ? $pending->updated_at->timestamp : 0);
        $elapsed = now()->timestamp - $sentAt;

        if ($elapsed < 60) {
            $remaining = 60 - $elapsed;

            return back()->with('warning', "Harap tunggu {$remaining} detik sebelum meminta kirim ulang kode OTP.");
        }

        $otpCode = PasswordResetOtp::generateCode();

        PasswordResetOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp_code' => $otpCode,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        Mail::to($email)->send(new PasswordResetOtpMail(
            name: $user->name,
            otpCode: $otpCode,
            expiresMinutes: 10
        ));

        session([
            'password_reset_email' => $email,
            'password_reset_otp_sent_at' => now()->timestamp,
        ]);

        return back()->with('success', 'Kode OTP baru telah berhasil dikirimkan ke email Anda.');
    }
}
