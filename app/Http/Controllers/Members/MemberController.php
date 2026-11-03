<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $space = app('currentSpace');

        $status = $request->query('status', 'active');
        $query = Member::where('space_id', $space->id)
            ->withCount('transactions')
            ->withCount('recurringSchedules');

        if ($status === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }

        $members = $query->orderBy('name')->get();
        $activeCount = Member::where('space_id', $space->id)->active()->count();
        $archivedCount = Member::where('space_id', $space->id)->archived()->count();

        return view('members.index', compact('members', 'status', 'activeCount', 'archivedCount'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $space = app('currentSpace');

        $member = Member::create([
            'space_id' => $space->id,
            'name' => $request->validated('name'),
            'color' => $request->validated('color') ?: '#0f4c3a',
            'notes' => $request->validated('notes'),
        ]);

        return redirect()->route('members.index')
            ->with('status', "Member \"{$member->name}\" berhasil ditambahkan.");
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update([
            'name' => $request->validated('name'),
            'color' => $request->validated('color') ?: $member->color,
            'notes' => $request->validated('notes'),
        ]);

        return redirect()->route('members.index')
            ->with('status', "Data member \"{$member->name}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('delete', $member);

        if ($member->isArchived()) {
            $member->unarchive();
            $message = "Member \"{$member->name}\" berhasil dipulihkan dari arsip.";
        } else {
            $member->archive();
            $message = "Member \"{$member->name}\" berhasil diarsipkan.";
        }

        return redirect()->route('members.index')->with('status', $message);
    }
}
