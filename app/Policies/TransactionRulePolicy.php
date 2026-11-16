<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\TransactionRule;
use App\Models\User;

class TransactionRulePolicy
{
    public function viewAny(User $user, Space $space): bool
    {
        return $space->hasMember($user);
    }

    public function view(User $user, TransactionRule $rule): bool
    {
        return $rule->space->hasMember($user);
    }

    public function create(User $user, Space $space): bool
    {
        return $space->hasMember($user);
    }

    public function update(User $user, TransactionRule $rule): bool
    {
        return $rule->space->hasMember($user);
    }

    public function delete(User $user, TransactionRule $rule): bool
    {
        return $rule->space->hasMember($user);
    }

    public function toggleActive(User $user, TransactionRule $rule): bool
    {
        return $rule->space->hasMember($user);
    }

    public function applyExisting(User $user, TransactionRule $rule): bool
    {
        return $rule->space->hasMember($user);
    }
}
