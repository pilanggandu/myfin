<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->ensurePersonalSpace();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $personalSpace = $user->personalSpace();
        if ($personalSpace) {
            session(['active_space_id' => $personalSpace->id]);
        }

        return redirect()->route('dashboard')->with('success', 'Selamat datang di ARUS. Ruang personal Anda telah disiapkan.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
