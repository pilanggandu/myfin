<?php

namespace App\Policies;

use App\Models\RecurringSchedule;
use App\Models\User;

class RecurringSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RecurringSchedule $schedule): bool
    {
        return $schedule->space->hasActiveMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, RecurringSchedule $schedule): bool
    {
        return $schedule->space->hasActiveMember($user);
    }

    public function delete(User $user, RecurringSchedule $schedule): bool
    {
        return $schedule->space->hasActiveMember($user);
    }
}
