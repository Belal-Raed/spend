<?php

namespace App\Services;

/**
 * Converts between stored decimal amounts and integer cents.
 *
 * Arithmetic on amounts is done in cents so sums and comparisons are exact.
 */
final class Money
{
    public static function toCents(string|int|float|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * The decimal string stored in an amount column.
     */
    public static function toDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
