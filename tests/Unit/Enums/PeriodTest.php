<?php

namespace Tests\Unit\Enums;

use App\Enums\Period;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase
{
    /**
     * Ranges relative to Sunday 2026-10-11.
     *
     * @return array<string, array{0: Period, 1: string, 2: string}>
     */
    public static function ranges(): array
    {
        return [
            'today' => [Period::Today, '2026-10-11', '2026-10-11'],
            'yesterday' => [Period::Yesterday, '2026-10-10', '2026-10-10'],
            'this week starts on saturday' => [Period::ThisWeek, '2026-10-10', '2026-10-16'],
            'last week' => [Period::LastWeek, '2026-10-03', '2026-10-09'],
            'this month' => [Period::ThisMonth, '2026-10-01', '2026-10-31'],
            'last month' => [Period::LastMonth, '2026-09-01', '2026-09-30'],
            'this year' => [Period::ThisYear, '2026-01-01', '2026-12-31'],
        ];
    }

    #[DataProvider('ranges')]
    public function test_returns_inclusive_day_range(Period $period, string $from, string $to): void
    {
        [$start, $end] = $period->range(CarbonImmutable::parse('2026-10-11 18:30'));

        $this->assertSame([$from, $to], [$start->toDateString(), $end->toDateString()]);
    }

    public function test_week_includes_saturday_as_its_first_day(): void
    {
        [$start] = Period::ThisWeek->range(CarbonImmutable::parse('2026-10-10'));

        $this->assertSame('2026-10-10', $start->toDateString());
    }

    public function test_last_month_does_not_overflow_from_the_31st(): void
    {
        [$start, $end] = Period::LastMonth->range(CarbonImmutable::parse('2026-03-31'));

        $this->assertSame(['2026-02-01', '2026-02-28'], [$start->toDateString(), $end->toDateString()]);
    }
}
