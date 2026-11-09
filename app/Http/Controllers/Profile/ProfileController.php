<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CleanDataRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Space;
use App\Services\DataCleanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $ownedSpaces = $user->ownedSpaces()->orderBy('name')->get();
        $currentSpace = app('currentSpace');

        return view('profile.edit', compact('user', 'ownedSpaces', 'currentSpace'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profil Anda berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $isFirstTime = ! $user->has_password;

        $user->update([
            'password' => Hash::make($request->validated('password')),
            'has_password' => true,
        ]);

        $message = $isFirstTime
            ? 'Kata sandi akun berhasil dibuat. Sekarang Anda dapat masuk menggunakan email dan kata sandi.'
            : 'Kata sandi Anda berhasil diubah.';

        return redirect()->route('profile.edit')->with('success', $message);
    }

    public function cleanData(CleanDataRequest $request, DataCleanService $cleanService): RedirectResponse
    {
        $space = Space::findOrFail($request->validated('space_id'));
        $summary = $cleanService->cleanSpace($space, $request->validated());

        $details = [];
        if (! empty($summary['transactions'])) {
            $details[] = "{$summary['transactions']} transaksi";
        }
        if (! empty($summary['recurring'])) {
            $details[] = "{$summary['recurring']} jadwal berulang";
        }
        if (! empty($summary['budgets'])) {
            $details[] = "{$summary['budgets']} anggaran";
        }
        if (! empty($summary['members'])) {
            $details[] = "{$summary['members']} member";
        }
        if (! empty($summary['accounts'])) {
            $details[] = "{$summary['accounts']} rekening";
        }
        if (! empty($summary['reset_balances'])) {
            $details[] = 'saldo rekening direset ke Rp 0';
        }
        if (! empty($summary['categories_reset'])) {
            $details[] = 'kategori direset ke default';
        }

        $detailText = ! empty($details) ? ' ('.implode(', ', $details).')' : '';

        return redirect()->route('profile.edit')->with('success', "Data ruang \"{$space->name}\" berhasil dibersihkan{$detailText}.");
    }
}
