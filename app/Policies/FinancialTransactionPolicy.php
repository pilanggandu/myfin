<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function view(User $user, FinancialTransaction $transaction): bool
    {
        return $transaction->space->hasMember($user);
    }

    public function update(User $user, FinancialTransaction $transaction): bool
    {
        return $transaction->space->hasMember($user);
    }

    public function void(User $user, FinancialTransaction $transaction): bool
    {
        return $transaction->space->hasMember($user);
    }
}
