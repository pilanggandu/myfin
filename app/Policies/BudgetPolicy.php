<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function view(User $user, Budget $budget): bool
    {
        return $budget->space->hasActiveMember($user);
    }

    public function create(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function update(User $user, Budget $budget): bool
    {
        return $budget->space->hasActiveMember($user);
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $budget->space->hasActiveMember($user);
    }
}
