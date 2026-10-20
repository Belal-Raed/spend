<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DebtControllerTest extends TestCase
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
        $this->get(route('debts.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_debts_gets_both_lists_empty(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('debts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('debts/index')
                ->has('debtsOwedByMe', 0)
                ->has('debtsOwedToMe', 0));
    }

    public function test_lists_open_debts_by_direction_with_what_is_left(): void
    {
        $user = User::factory()->create();
        $partlyPaid = $this->debt($user, DebtDirection::OwedByMe, '500.00', 'خالد');
        $untouched = $this->debt($user, DebtDirection::OwedToMe, '200.00', 'سامي');
        app(TransactionService::class)->repayDebt($partlyPaid, ['amount' => '100.00']);

        $this->actingAs($user)
            ->get(route('debts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('debtsOwedByMe', 1)
                ->where('debtsOwedByMe.0.id', $partlyPaid->id)
                ->where('debtsOwedByMe.0.counterparty', 'خالد')
                ->where('debtsOwedByMe.0.remaining', 400)
                ->where('debtsOwedByMe.0.amount', 500)
                ->has('debtsOwedToMe', 1)
                ->where('debtsOwedToMe.0.id', $untouched->id)
                ->where('debtsOwedToMe.0.remaining', 200));
    }

    public function test_does_not_list_a_debt_that_is_fully_paid(): void
    {
        $user = User::factory()->create();
        $settled = $this->debt($user, DebtDirection::OwedToMe, '200.00', 'سامي');
        app(TransactionService::class)->repayDebt($settled);

        $this->actingAs($user)
            ->get(route('debts.index'))
            ->assertInertia(fn (Assert $page) => $page->has('debtsOwedToMe', 0));
    }

    public function test_does_not_list_another_users_debts(): void
    {
        $this->debt(User::factory()->create(), DebtDirection::OwedByMe, '500.00', 'خالد');

        $this->actingAs(User::factory()->create())
            ->get(route('debts.index'))
            ->assertInertia(fn (Assert $page) => $page->has('debtsOwedByMe', 0));
    }

    public function test_never_sends_the_page_a_raw_script_tag_from_a_counterparty_or_a_note(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->debt()->create([
            'counterparty' => "<script>alert('name')</script>",
            'note' => "<script>alert('note')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('debts.index'))
            ->assertDontSee('<script>alert', escape: false);
    }
}
