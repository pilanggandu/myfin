<?php

namespace App\Http\Controllers\Spaces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Space\StoreSpaceRequest;
use App\Http\Requests\Space\UpdateSpaceRequest;
use App\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SpaceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $spaces = $user->activeSpaces()
            ->with(['owner'])
            ->orderBy('type', 'desc')
            ->orderBy('name')
            ->get();

        return view('spaces.index', compact('spaces'));
    }

    public function create(): View
    {
        return view('spaces.create');
    }

    public function store(StoreSpaceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $space = DB::transaction(function () use ($user, $validated) {
            $space = Space::create([
                'name' => $validated['name'],
                'type' => 'shared',
                'owner_id' => $user->id,
                'timezone' => $validated['timezone'] ?? config('arus.default_timezone', 'Asia/Jakarta'),
            ]);

            $space->members()->attach($user->id, [
                'role' => 'owner',
                'status' => 'active',
            ]);

            $space->createDefaultCategories();

            return $space;
        });

        session(['active_space_id' => $space->id]);

        return redirect()->route('spaces.index')->with('success', "Ruang bersama '{$space->name}' berhasil dibuat dan aktif.");
    }

    public function switchSpace(Space $space): RedirectResponse
    {
        Gate::authorize('view', $space);

        session(['active_space_id' => $space->id]);

        return redirect()->back()->with('success', "Beralih ke ruang '{$space->name}'.");
    }

    public function edit(Space $space): View
    {
        Gate::authorize('update', $space);

        return view('spaces.edit', compact('space'));
    }

    public function update(UpdateSpaceRequest $request, Space $space): RedirectResponse
    {
        Gate::authorize('update', $space);

        $space->update($request->validated());

        return redirect()->route('spaces.index')->with('success', 'Pengaturan ruang berhasil diperbarui.');
    }

    public function destroy(Space $space): RedirectResponse
    {
        Gate::authorize('delete', $space);

        $spaceName = $space->name;

        DB::transaction(function () use ($space) {
            $space->delete();
        });

        $user = auth()->user();
        $activeSpaceId = session('active_space_id');

        if ((int) $activeSpaceId === (int) $space->id) {
            $fallbackSpace = $user->personalSpace() ?: $user->activeSpaces()->first();
            if ($fallbackSpace) {
                session(['active_space_id' => $fallbackSpace->id]);
            }
        }

        return redirect()->route('spaces.index')->with('success', "Ruang '{$spaceName}' berhasil dihapus.");
    }
}
