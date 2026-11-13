<?php

namespace App\Policies;

use App\Models\PlannedIncome;
use App\Models\User;

class PlannedIncomePolicy
{
    public function viewAny(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function view(User $user, PlannedIncome $plannedIncome): bool
    {
        return $plannedIncome->space->hasActiveMember($user);
    }

    public function create(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function update(User $user, PlannedIncome $plannedIncome): bool
    {
        return $plannedIncome->space->hasActiveMember($user);
    }

    public function delete(User $user, PlannedIncome $plannedIncome): bool
    {
        return $plannedIncome->space->hasActiveMember($user);
    }

    public function realize(User $user, PlannedIncome $plannedIncome): bool
    {
        return $plannedIncome->space->hasActiveMember($user);
    }
}
