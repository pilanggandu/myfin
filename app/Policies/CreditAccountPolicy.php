<?php

namespace App\Policies;

use App\Models\CreditAccount;
use App\Models\User;

class CreditAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditAccount $creditAccount): bool
    {
        return $creditAccount->space->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CreditAccount $creditAccount): bool
    {
        return $creditAccount->space->hasMember($user);
    }

    public function delete(User $user, CreditAccount $creditAccount): bool
    {
        return $creditAccount->space->hasMember($user);
    }
}
