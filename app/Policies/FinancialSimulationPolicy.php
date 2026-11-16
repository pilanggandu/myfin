<?php

namespace App\Policies;

use App\Models\FinancialSimulation;
use App\Models\User;

class FinancialSimulationPolicy
{
    public function viewAny(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function view(User $user, FinancialSimulation $simulation): bool
    {
        return $simulation->space->hasActiveMember($user);
    }

    public function create(User $user): bool
    {
        $currentSpace = app('currentSpace');
        if (! $currentSpace) {
            return false;
        }

        return $currentSpace->hasActiveMember($user);
    }

    public function update(User $user, FinancialSimulation $simulation): bool
    {
        return $simulation->space->hasActiveMember($user);
    }

    public function delete(User $user, FinancialSimulation $simulation): bool
    {
        return $simulation->space->hasActiveMember($user);
    }
}
