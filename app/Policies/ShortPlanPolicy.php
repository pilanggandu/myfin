<?php

namespace App\Policies;

use App\Models\ShortPlan;
use App\Models\User;

class ShortPlanPolicy
{
    public function viewAny(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function view(User $user, ShortPlan $shortPlan): bool
    {
        return $shortPlan->space->hasActiveMember($user);
    }

    public function create(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function update(User $user, ShortPlan $shortPlan): bool
    {
        return $shortPlan->space->hasActiveMember($user);
    }

    public function delete(User $user, ShortPlan $shortPlan): bool
    {
        return $shortPlan->space->hasActiveMember($user);
    }
}
