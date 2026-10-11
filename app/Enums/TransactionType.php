<?php

namespace App\Enums;

enum TransactionType: string
{
    case Expense = 'expense';
    case Income = 'income';
    case Debt = 'debt';

    /**
     * Whether transactions of this type are grouped by category.
     */
    public function hasCategory(): bool
    {
        return $this !== self::Debt;
    }
}
