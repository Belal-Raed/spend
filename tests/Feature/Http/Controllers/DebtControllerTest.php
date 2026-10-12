<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_user_without_debts_sees_both_lists_empty(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('debts.index'))
            ->assertOk()
            ->assertSeeText('ما عليك ديون مفتوحة.')
            ->assertSeeText('ما إلك ديون مفتوحة.');
    }

    public function test_lists_open_debts_by_direction_with_what_is_left_and_a_way_to_pay(): void
    {
        $user = User::factory()->create();
        $partlyPaid = $this->debt($user, DebtDirection::OwedByMe, '500.00', 'خالد');
        $untouched = $this->debt($user, DebtDirection::OwedToMe, '200.00', 'سامي');
        app(TransactionService::class)->repayDebt($partlyPaid, ['amount' => '100.00']);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertOk()
            ->assertSeeTextInOrder(['ديون عليّ', 'خالد', '400.00', 'من أصل', '500.00', 'ديون إلي', 'سامي', '200.00'])
            ->assertSee(route('debts.repayments.store', $partlyPaid), escape: false)
            ->assertSee(route('debts.repayments.create', $untouched), escape: false);
    }

    public function test_does_not_list_a_debt_that_is_fully_paid(): void
    {
        $user = User::factory()->create();
        $settled = $this->debt($user, DebtDirection::OwedToMe, '200.00', 'سامي');
        app(TransactionService::class)->repayDebt($settled);

        $this->actingAs($user)
            ->get(route('debts.index'))
            ->assertViewHas('debtsOwedToMe', [])
            ->assertDontSeeText('سامي');
    }

    public function test_does_not_list_another_users_debts(): void
    {
        $this->debt(User::factory()->create(), DebtDirection::OwedByMe, '500.00', 'خالد');

        $this->actingAs(User::factory()->create())
            ->get(route('debts.index'))
            ->assertViewHas('debtsOwedByMe', [])
            ->assertDontSeeText('خالد');
    }

    public function test_escapes_dangerous_content_in_a_counterparty_and_a_note(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->debt()->create([
            'counterparty' => "<script>alert('name')</script>",
            'note' => "<script>alert('note')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('debts.index'))
            ->assertSee('&lt;script&gt;alert(&#039;note&#039;)', escape: false)
            ->assertDontSee("<script>alert('name')</script>", escape: false)
            ->assertDontSee("<script>alert('note')</script>", escape: false);
    }
}
