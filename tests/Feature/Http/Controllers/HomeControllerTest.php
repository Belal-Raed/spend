<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Amounts reach the page as JSON numbers, so a whole amount such as 400.00 arrives as 400.
 */
class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function debt(User $user, DebtDirection $direction, string $amount, string $counterparty): Transaction
    {
        return Transaction::factory()->for($user)->debt($direction)->create([
            'amount' => $amount,
            'counterparty' => $counterparty,
        ]);
    }

    public function test_guest_is_sent_to_sign_up(): void
    {
        $this->get(route('home'))->assertRedirect(route('register'));
    }

    public function test_signed_in_user_gets_their_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'أبو محمد']))
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home')
                ->where('auth.user.name', 'أبو محمد'));
    }

    public function test_never_sends_the_page_a_raw_script_tag_from_the_user_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => "<script>alert('xss')</script>"]))
            ->get(route('home'))
            ->assertDontSee('<script>alert', escape: false);
    }

    public function test_new_user_gets_zero_totals_and_no_open_debts(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('balance', ['cash' => 0, 'owed_by_me' => 0, 'owed_to_me' => 0, 'after_debts' => 0])
                ->where('monthExpensesTotal', 0)
                ->has('debtsOwedByMe', 0)
                ->has('debtsOwedToMe', 0));
    }

    public function test_gives_the_balance_before_and_after_open_debts(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->income()->create(['amount' => '3000.00']);
        Transaction::factory()->for($user)->create(['amount' => '1250.50']);
        $this->debt($user, DebtDirection::OwedByMe, '400.00', 'خالد');
        $this->debt($user, DebtDirection::OwedToMe, '150.00', 'سامي');

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('balance', [
                'cash' => 1999.5,
                'owed_by_me' => 400,
                'owed_to_me' => 150,
                'after_debts' => 1749.5,
            ]));
    }

    public function test_month_total_counts_only_this_months_expenses(): void
    {
        $this->travelTo('2026-10-12');
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['amount' => '80.00', 'occurred_on' => '2026-10-01']);
        Transaction::factory()->for($user)->create(['amount' => '20.25', 'occurred_on' => '2026-10-12']);
        Transaction::factory()->for($user)->create(['amount' => '999.00', 'occurred_on' => '2026-09-30']);
        Transaction::factory()->for($user)->income()->create(['amount' => '5000.00', 'occurred_on' => '2026-10-05']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('monthExpensesTotal', 100.25));
    }

    public function test_lists_open_debts_by_direction_with_what_is_left_on_each(): void
    {
        $user = User::factory()->create();
        $partlyPaid = $this->debt($user, DebtDirection::OwedByMe, '400.00', 'خالد');
        $this->debt($user, DebtDirection::OwedToMe, '150.00', 'سامي');
        $this->repayAsOwner($partlyPaid, ['amount' => '100.00']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('debtsOwedByMe', 1)
                ->where('debtsOwedByMe.0.counterparty', 'خالد')
                ->where('debtsOwedByMe.0.remaining', 300)
                ->where('debtsOwedByMe.0.amount', 400)
                ->has('debtsOwedToMe', 1)
                ->where('debtsOwedToMe.0.counterparty', 'سامي')
                ->where('debtsOwedToMe.0.remaining', 150));
    }

    public function test_does_not_list_a_debt_that_is_fully_paid(): void
    {
        $user = User::factory()->create();
        $settled = $this->debt($user, DebtDirection::OwedToMe, '150.00', 'سامي');
        $this->repayAsOwner($settled);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->has('debtsOwedToMe', 0));
    }

    public function test_does_not_show_another_users_transactions(): void
    {
        $otherUser = User::factory()->create();
        Transaction::factory()->for($otherUser)->income()->create(['amount' => '3000.00']);
        Transaction::factory()->for($otherUser)->create(['amount' => '75.00']);
        $this->debt($otherUser, DebtDirection::OwedByMe, '400.00', 'خالد');

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('balance', ['cash' => 0, 'owed_by_me' => 0, 'owed_to_me' => 0, 'after_debts' => 0])
                ->where('monthExpensesTotal', 0)
                ->has('debtsOwedByMe', 0)
                ->has('recentTransactions', 0));
    }

    public function test_never_sends_the_page_a_raw_script_tag_from_a_debt_counterparty(): void
    {
        $user = User::factory()->create();
        $this->debt($user, DebtDirection::OwedToMe, '150.00', "<script>alert('xss')</script>");

        $this->actingAs($user)
            ->get(route('home'))
            ->assertDontSee('<script>alert', escape: false);
    }

    public function test_gives_the_five_latest_transactions_newest_first(): void
    {
        $user = User::factory()->create();
        $transactions = Transaction::factory()->count(6)->for($user)->sequence(
            ['occurred_on' => '2026-10-01'],
            ['occurred_on' => '2026-10-02'],
            ['occurred_on' => '2026-10-03'],
            ['occurred_on' => '2026-10-04'],
            ['occurred_on' => '2026-10-05'],
            ['occurred_on' => '2026-10-06'],
        )->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'recentTransactions',
                fn ($recentTransactions) => collect($recentTransactions)->pluck('id')->all() === $transactions->reverse()->take(5)->pluck('id')->values()->all(),
            ));
    }

    public function test_gives_each_latest_transaction_with_its_category_and_whether_it_adds_to_the_balance(): void
    {
        $user = User::factory()->create();
        $expense = Transaction::factory()->for($user)->create(['amount' => '12.50']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentTransactions.0.id', $expense->id)
                ->where('recentTransactions.0.amount', '12.50')
                ->where('recentTransactions.0.category', $expense->category->name)
                ->where('recentTransactions.0.adds_to_balance', false));
    }
}
