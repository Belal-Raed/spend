<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * A user may only change their own transactions.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        return $transaction->user_id === $user->id;
    }

    /**
     * A user may only delete their own transactions.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return $transaction->user_id === $user->id;
    }
}
