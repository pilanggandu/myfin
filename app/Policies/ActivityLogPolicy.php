<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        if ($activityLog->space_id !== null) {
            return $activityLog->space !== null && $activityLog->space->hasMember($user);
        }

        return (int) $activityLog->actor_id === (int) $user->id;
    }
}
