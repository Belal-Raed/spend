<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum Period: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case ThisWeek = 'this_week';
    case LastWeek = 'last_week';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisYear = 'this_year';

    /**
     * Weeks start on Saturday, as is common in the Arab world.
     */
    public const WEEK_STARTS_ON = CarbonInterface::SATURDAY;

    /**
     * The first and last day (inclusive) of the period relative to the given day.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonInterface $today): array
    {
        $today = CarbonImmutable::instance($today)->startOfDay();

        return match ($this) {
            self::Today => [$today, $today],
            self::Yesterday => [$today->subDay(), $today->subDay()],
            self::ThisWeek => [$today->startOfWeek(self::WEEK_STARTS_ON), $today->endOfWeek($this->weekEndsOn())->startOfDay()],
            self::LastWeek => [
                $today->subWeek()->startOfWeek(self::WEEK_STARTS_ON),
                $today->subWeek()->endOfWeek($this->weekEndsOn())->startOfDay(),
            ],
            self::ThisMonth => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
            self::LastMonth => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            self::ThisYear => [$today->startOfYear(), $today->endOfYear()->startOfDay()],
        };
    }

    private function weekEndsOn(): int
    {
        return (self::WEEK_STARTS_ON + 6) % 7;
    }
}
