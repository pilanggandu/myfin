<?php

namespace App\Policies;

use App\Models\InstallmentPlan;
use App\Models\User;

class InstallmentPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InstallmentPlan $plan): bool
    {
        return $plan->space->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, InstallmentPlan $plan): bool
    {
        return $plan->space->hasMember($user);
    }

    public function delete(User $user, InstallmentPlan $plan): bool
    {
        return $plan->space->hasMember($user);
    }
}
