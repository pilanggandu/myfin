<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            $route = Auth::check() ? 'profile.edit' : 'login';

            return redirect()->route($route)->with('warning', 'Integrasi Google SSO belum dikonfigurasi. Harap atur GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET pada file .env.');
        }

        if (Auth::check() || $request->query('action') === 'bind') {
            session(['google_auth_action' => 'bind']);
        } else {
            session()->forget('google_auth_action');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $isBinding = session('google_auth_action') === 'bind' && Auth::check();

        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            $route = $isBinding ? 'profile.edit' : 'login';

            return redirect()->route($route)->with('warning', 'Integrasi Google SSO belum dikonfigurasi di server.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            $route = $isBinding ? 'profile.edit' : 'login';

            return redirect()->route($route)->with('error', 'Autentikasi Google dibatalkan atau terjadi kesalahan: '.$e->getMessage());
        }

        $email = strtolower(trim($googleUser->getEmail()));
        if (empty($email)) {
            $route = $isBinding ? 'profile.edit' : 'login';

            return redirect()->route($route)->with('error', 'Akun Google tidak menyediakan alamat email yang valid.');
        }

        if ($isBinding) {
            session()->forget('google_auth_action');
            $currentUser = Auth::user();

            $conflict = User::where('google_id', $googleUser->getId())
                ->where('id', '!=', $currentUser->id)
                ->first();

            if ($conflict) {
                return redirect()->route('profile.edit')->with('error', 'Akun Google ini sudah terhubung dengan akun pengguna lain.');
            }

            $currentUser->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $currentUser->avatar ?: $googleUser->getAvatar(),
            ]);

            return redirect()->route('profile.edit')->with('success', 'Akun Google berhasil ditautkan ke profil Anda.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $updates = [];
            if (empty($user->google_id)) {
                $updates['google_id'] = $googleUser->getId();
            }
            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $updates['avatar'] = $googleUser->getAvatar();
            }
            if (is_null($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if (! empty($updates)) {
                $user->update($updates);
            }

            $personalSpace = $user->ensurePersonalSpace();
            Auth::login($user);
            $request->session()->regenerate();
            session(['active_space_id' => $personalSpace->id]);

            return redirect()->intended(route('dashboard'))->with('success', 'Berhasil masuk dengan akun Google.');
        }

        if (! config('arus.registration_open', true)) {
            return redirect()->route('login')->with('warning', 'Pendaftaran publik saat ini sedang ditutup.');
        }

        $newUser = User::create([
            'name' => $googleUser->getName() ?: 'Pengguna Google',
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(Str::random(32)),
            'has_password' => false,
            'email_verified_at' => now(),
        ]);

        $personalSpace = $newUser->ensurePersonalSpace();
        Auth::login($newUser);
        $request->session()->regenerate();
        session(['active_space_id' => $personalSpace->id]);

        return redirect()->route('dashboard')->with('success', 'Selamat datang di ARUS! Akun Anda berhasil didaftarkan melalui Google.');
    }

    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->has_password) {
            return redirect()->route('profile.edit')->with('error', 'Buat kata sandi akun terlebih dahulu sebelum memutuskan tautan Google agar Anda tidak kehilangan akses masuk.');
        }

        $user->update(['google_id' => null]);

        return redirect()->route('profile.edit')->with('success', 'Tautan akun Google berhasil diputuskan.');
    }
}
