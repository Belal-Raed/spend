<?php

namespace Tests\Feature\Services;

use App\Enums\DebtDirection;
use App\Enums\Period;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function reports(): ReportService
    {
        return app(ReportService::class);
    }

    private function transactions(): TransactionService
    {
        return app(TransactionService::class);
    }

    private function debt(User $user, DebtDirection $direction, string $amount): Transaction
    {
        return Transaction::factory()->for($user)->debt($direction)->create(['amount' => $amount]);
    }

    public function test_balance_is_income_minus_expenses(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->income()->create(['amount' => '1000.00']);
        Transaction::factory()->for($user)->create(['amount' => '250.25']);
        Transaction::factory()->for($user)->create(['amount' => '49.75']);

        $this->assertSame(
            ['cash' => 700.0, 'owed_by_me' => 0.0, 'owed_to_me' => 0.0, 'after_debts' => 700.0],
            $this->reports()->balance($user),
        );
    }

    public function test_lending_lowers_cash_but_not_balance_after_debts(): void
    {
        $user = User::factory()->create();
        $this->debt($user, DebtDirection::OwedToMe, '100.00');

        $this->assertSame(
            ['cash' => -100.0, 'owed_by_me' => 0.0, 'owed_to_me' => 100.0, 'after_debts' => 0.0],
            $this->reports()->balance($user),
        );
    }

    public function test_borrowing_raises_cash_but_not_balance_after_debts(): void
    {
        $user = User::factory()->create();
        $this->debt($user, DebtDirection::OwedByMe, '100.00');

        $this->assertSame(
            ['cash' => 100.0, 'owed_by_me' => 100.0, 'owed_to_me' => 0.0, 'after_debts' => 0.0],
            $this->reports()->balance($user),
        );
    }

    public function test_partial_payment_counts_only_what_is_left_of_the_debt(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user, DebtDirection::OwedToMe, '100.00');
        $this->transactions()->repayDebt($debt, ['amount' => 40]);

        $this->assertSame(
            ['cash' => -60.0, 'owed_by_me' => 0.0, 'owed_to_me' => 60.0, 'after_debts' => 0.0],
            $this->reports()->balance($user),
        );
    }

    public function test_settled_debts_no_longer_count_against_balance_after_debts(): void
    {
        $user = User::factory()->create();
        $this->transactions()->repayDebt($this->debt($user, DebtDirection::OwedByMe, '100.00'));

        $this->assertSame(
            ['cash' => 0.0, 'owed_by_me' => 0.0, 'owed_to_me' => 0.0, 'after_debts' => 0.0],
            $this->reports()->balance($user),
        );
    }

    public function test_balance_ignores_other_users(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->income()->create(['amount' => '500.00']);
        $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');

        $this->assertSame(0.0, $this->reports()->balance($user)['after_debts']);
        $this->assertSame(0.0, $this->reports()->balance($user)['owed_to_me']);
    }

    public function test_summary_breaks_expenses_down_by_category_largest_first(): void
    {
        $user = User::factory()->create();
        $food = Category::factory()->create(['name' => 'أكل']);
        $home = Category::factory()->create(['name' => 'بيت']);
        Transaction::factory()->for($user)->for($food)->create(['amount' => '5.00', 'occurred_on' => '2026-10-01']);
        Transaction::factory()->for($user)->for($food)->create(['amount' => '15.00', 'occurred_on' => '2026-10-31']);
        Transaction::factory()->for($user)->for($home)->create(['amount' => '60.00', 'occurred_on' => '2026-10-15']);
        Transaction::factory()->for($user)->income()->create(['amount' => '1000.00', 'occurred_on' => '2026-10-15']);

        $summary = $this->reports()->summary($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame([
            'from' => '2026-10-01',
            'to' => '2026-10-31',
            'expenses_total' => 80.0,
            'income_total' => 1000.0,
            'expenses_by_category' => [
                ['category_id' => $home->id, 'category' => 'بيت', 'total' => 60.0, 'percentage' => 75.0],
                ['category_id' => $food->id, 'category' => 'أكل', 'total' => 20.0, 'percentage' => 25.0],
            ],
        ], $summary);
    }

    public function test_summary_excludes_days_outside_the_range(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['amount' => '7.00', 'occurred_on' => '2026-09-30']);
        Transaction::factory()->for($user)->create(['amount' => '9.00', 'occurred_on' => '2026-11-01']);

        $summary = $this->reports()->summary($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame(0.0, $summary['expenses_total']);
        $this->assertSame([], $summary['expenses_by_category']);
    }

    public function test_summary_includes_debt_payments_made_as_uncategorized_expenses(): void
    {
        $user = User::factory()->create();
        $this->transactions()->repayDebt(
            $this->debt($user, DebtDirection::OwedByMe, '30.00'),
            ['occurred_on' => '2026-10-05'],
        );

        $summary = $this->reports()->summary($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame(30.0, $summary['expenses_total']);
        $this->assertSame(
            [['category_id' => null, 'category' => null, 'total' => 30.0, 'percentage' => 100.0]],
            $summary['expenses_by_category'],
        );
    }

    public function test_summary_can_be_limited_to_one_category(): void
    {
        $user = User::factory()->create();
        $food = Category::factory()->create();
        Transaction::factory()->for($user)->for($food)->create(['amount' => '5.00', 'occurred_on' => '2026-10-02']);
        Transaction::factory()->for($user)->create(['amount' => '60.00', 'occurred_on' => '2026-10-02']);

        $summary = $this->reports()->summary($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), $food);

        $this->assertSame(5.0, $summary['expenses_total']);
        $this->assertCount(1, $summary['expenses_by_category']);
    }

    public function test_summary_ignores_other_users(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->create(['amount' => '60.00', 'occurred_on' => '2026-10-02']);

        $summary = $this->reports()->summary($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame(0.0, $summary['expenses_total']);
    }

    public function test_summary_for_period_uses_the_period_range(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['amount' => '10.00', 'occurred_on' => '2026-10-10']);
        Transaction::factory()->for($user)->create(['amount' => '4.00', 'occurred_on' => '2026-10-11']);

        $summary = $this->reports()->summaryForPeriod($user, Period::Yesterday, today: Carbon::parse('2026-10-11'));

        $this->assertSame('2026-10-10', $summary['from']);
        $this->assertSame(10.0, $summary['expenses_total']);
    }

    public function test_open_debts_lists_what_is_left_oldest_first(): void
    {
        $user = User::factory()->create();
        $newer = Transaction::factory()->for($user)->debt(DebtDirection::OwedByMe)
            ->create(['amount' => '50.00', 'counterparty' => 'سامي', 'occurred_on' => '2026-10-08']);
        $older = Transaction::factory()->for($user)->debt(DebtDirection::OwedToMe)
            ->create(['amount' => '100.00', 'counterparty' => 'أحمد', 'occurred_on' => '2026-10-01', 'note' => 'سلفة']);
        $this->transactions()->repayDebt($older, ['amount' => 40]);
        $this->transactions()->repayDebt($this->debt($user, DebtDirection::OwedToMe, '20.00'));

        $this->assertSame([
            [
                'id' => $older->id,
                'counterparty' => 'أحمد',
                'direction' => 'owed_to_me',
                'amount' => 100.0,
                'repaid' => 40.0,
                'remaining' => 60.0,
                'occurred_on' => '2026-10-01',
                'note' => 'سلفة',
            ],
            [
                'id' => $newer->id,
                'counterparty' => 'سامي',
                'direction' => 'owed_by_me',
                'amount' => 50.0,
                'repaid' => 0.0,
                'remaining' => 50.0,
                'occurred_on' => '2026-10-08',
                'note' => $newer->note,
            ],
        ], $this->reports()->openDebts($user));
    }

    public function test_open_debts_can_be_limited_to_one_direction(): void
    {
        $user = User::factory()->create();
        $this->debt($user, DebtDirection::OwedByMe, '50.00');
        $owedToMe = $this->debt($user, DebtDirection::OwedToMe, '20.00');

        $debts = $this->reports()->openDebts($user, DebtDirection::OwedToMe);

        $this->assertSame([$owedToMe->id], array_column($debts, 'id'));
    }
}
