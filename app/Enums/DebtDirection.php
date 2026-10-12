<?php

namespace App\Enums;

enum DebtDirection: string
{
    /**
     * The user borrowed money and owes it to the counterparty (علي).
     */
    case OwedByMe = 'owed_by_me';

    /**
     * The user lent money and the counterparty owes it back (إلي).
     */
    case OwedToMe = 'owed_to_me';

    /**
     * The name shown to the user.
     */
    public function label(): string
    {
        return match ($this) {
            self::OwedByMe => 'عليّ',
            self::OwedToMe => 'إلي',
        };
    }

    /**
     * The transaction type recorded when a payment is made against a debt in this direction.
     */
    public function repaymentType(): TransactionType
    {
        return match ($this) {
            self::OwedByMe => TransactionType::Expense,
            self::OwedToMe => TransactionType::Income,
        };
    }
}
