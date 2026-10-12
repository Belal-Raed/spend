<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_signed_in_user_sees_their_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'أبو محمد']))
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('أبو محمد');
    }

    public function test_escapes_dangerous_content_in_the_user_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => "<script>alert('xss')</script>"]))
            ->get(route('home'))
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee("<script>alert('xss')</script>", escape: false);
    }

    public function test_new_user_sees_zero_totals_and_no_open_debts(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertViewHas('balance', ['cash' => 0.0, 'owed_by_me' => 0.0, 'owed_to_me' => 0.0, 'after_debts' => 0.0])
            ->assertViewHas('monthExpensesTotal', 0.0)
            ->assertSeeText('ما عليك ديون مفتوحة.')
            ->assertSeeText('ما إلك ديون مفتوحة.')
            ->assertDontSeeText('-0.00');
    }

    public function test_shows_the_balance_before_and_after_open_debts(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->income()->create(['amount' => '3000.00']);
        Transaction::factory()->for($user)->create(['amount' => '1250.50']);
        $this->debt($user, DebtDirection::OwedByMe, '400.00', 'خالد');
        $this->debt($user, DebtDirection::OwedToMe, '150.00', 'سامي');

        $this->actingAs($user)
            ->get(route('home'))
            ->assertViewHas('balance', [
                'cash' => 1999.5,
                'owed_by_me' => 400.0,
                'owed_to_me' => 150.0,
                'after_debts' => 1749.5,
            ])
            ->assertSeeTextInOrder(['1,999.50', '-400.00', '150.00', '1,749.50']);
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
            ->assertViewHas('monthExpensesTotal', 100.25)
            ->assertSeeText('100.25');
    }

    public function test_lists_open_debts_by_direction_with_what_is_left_on_each(): void
    {
        $user = User::factory()->create();
        $partlyPaid = $this->debt($user, DebtDirection::OwedByMe, '400.00', 'خالد');
        $this->debt($user, DebtDirection::OwedToMe, '150.00', 'سامي');
        app(TransactionService::class)->repayDebt($partlyPaid, ['amount' => '100.00']);

        $response = $this->actingAs($user)->get(route('home'));

        $this->assertSame(['خالد'], array_column($response->viewData('debtsOwedByMe'), 'counterparty'));
        $this->assertSame(['سامي'], array_column($response->viewData('debtsOwedToMe'), 'counterparty'));
        $response->assertSeeTextInOrder(['ديون عليّ', 'خالد', '300.00', 'من أصل', '400.00', 'ديون إلي', 'سامي', '150.00']);
    }

    public function test_does_not_list_a_debt_that_is_fully_paid(): void
    {
        $user = User::factory()->create();
        $settled = $this->debt($user, DebtDirection::OwedToMe, '150.00', 'سامي');
        app(TransactionService::class)->repayDebt($settled);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertViewHas('debtsOwedToMe', [])
            ->assertDontSeeText('سامي');
    }

    public function test_does_not_show_another_users_transactions(): void
    {
        $otherUser = User::factory()->create();
        Transaction::factory()->for($otherUser)->income()->create(['amount' => '3000.00']);
        Transaction::factory()->for($otherUser)->create(['amount' => '75.00']);
        $this->debt($otherUser, DebtDirection::OwedByMe, '400.00', 'خالد');

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertViewHas('balance', ['cash' => 0.0, 'owed_by_me' => 0.0, 'owed_to_me' => 0.0, 'after_debts' => 0.0])
            ->assertViewHas('monthExpensesTotal', 0.0)
            ->assertDontSeeText('خالد');
    }

    public function test_escapes_dangerous_content_in_a_debt_counterparty(): void
    {
        $user = User::factory()->create();
        $this->debt($user, DebtDirection::OwedToMe, '150.00', "<script>alert('xss')</script>");

        $this->actingAs($user)
            ->get(route('home'))
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee("<script>alert('xss')</script>", escape: false);
    }
}
