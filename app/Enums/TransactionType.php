<?php

namespace App\Enums;

enum TransactionType: string
{
    case Expense = 'expense';
    case Income = 'income';
    case Debt = 'debt';

    /**
     * The name shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::Expense => 'مصروف',
            self::Income => 'قبض',
            self::Debt => 'دين',
        };
    }

    /**
     * Whether transactions of this type are grouped by category.
     */
    public function hasCategory(): bool
    {
        return $this !== self::Debt;
    }
}
