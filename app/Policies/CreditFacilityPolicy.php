<?php

namespace App\Policies;

use App\Models\CreditFacility;
use App\Models\User;

class CreditFacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditFacility $creditFacility): bool
    {
        return $creditFacility->space->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CreditFacility $creditFacility): bool
    {
        return $creditFacility->space->hasMember($user);
    }

    public function delete(User $user, CreditFacility $creditFacility): bool
    {
        return $creditFacility->space->hasMember($user);
    }
}
