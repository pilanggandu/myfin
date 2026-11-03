<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $account->space->hasMember($user);
    }

    public function create(User $user, Account $account): bool
    {
        return $account->space->hasMember($user);
    }

    public function update(User $user, Account $account): bool
    {
        return $account->space->hasMember($user);
    }

    public function delete(User $user, Account $account): bool
    {
        return $account->space->hasMember($user);
    }
}
