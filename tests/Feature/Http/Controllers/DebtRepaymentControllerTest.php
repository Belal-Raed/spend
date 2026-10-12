<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtRepaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function debt(User $user, DebtDirection $direction = DebtDirection::OwedByMe, string $amount = '500.00'): Transaction
    {
        return Transaction::factory()->for($user)->debt($direction)->create([
            'amount' => $amount,
            'counterparty' => 'خالد',
        ]);
    }

    // Create

    public function test_guest_is_redirected_to_login_from_the_payment_form(): void
    {
        $debt = $this->debt(User::factory()->create());

        $this->get(route('debts.repayments.create', $debt))->assertRedirect(route('login'));
    }

    public function test_payment_form_is_filled_in_with_what_is_left_on_the_debt(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);
        app(TransactionService::class)->repayDebt($debt, ['amount' => '120.50']);

        $this->actingAs($user)
            ->get(route('debts.repayments.create', $debt))
            ->assertOk()
            ->assertViewHas('remaining', '379.50')
            ->assertSee('value="379.50"', escape: false)
            ->assertSeeText('دين عليّ · خالد');
    }

    public function test_another_user_is_forbidden_from_the_payment_form(): void
    {
        $debt = $this->debt(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('debts.repayments.create', $debt))
            ->assertForbidden();
    }

    public function test_payment_form_is_forbidden_for_a_transaction_that_is_not_a_debt(): void
    {
        $user = User::factory()->create();
        $expense = Transaction::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('debts.repayments.create', $expense))
            ->assertForbidden();
    }

    public function test_payment_form_for_a_settled_debt_sends_the_user_back_to_the_list(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);
        app(TransactionService::class)->repayDebt($debt);

        $this->actingAs($user)
            ->get(route('debts.repayments.create', $debt))
            ->assertRedirect(route('debts.index'))
            ->assertSessionHasErrors(['amount' => 'هذا الدين انسدّ من قبل.']);
    }

    // Store

    public function test_guest_cannot_record_a_payment(): void
    {
        $debt = $this->debt(User::factory()->create());

        $this->post(route('debts.repayments.store', $debt))->assertRedirect(route('login'));

        $this->assertFalse($debt->repayments()->exists());
    }

    public function test_payment_without_an_amount_pays_everything_left_and_settles_the_debt(): void
    {
        $this->travelTo('2026-10-12');
        $user = User::factory()->create();
        $debt = $this->debt($user);
        app(TransactionService::class)->repayDebt($debt, ['amount' => '100', 'occurred_on' => '2026-10-01']);

        $this->actingAs($user)
            ->post(route('debts.repayments.store', $debt))
            ->assertRedirect(route('debts.index'))
            ->assertSessionHas('status', 'انسدّ الدين.');

        $this->assertSame(['100.00', '400.00'], $debt->repayments()->orderBy('id')->pluck('amount')->all());
        $this->assertSame('2026-10-12', $debt->refresh()->settled_on->toDateString());
    }

    public function test_partial_payment_leaves_the_debt_open(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);

        $this->actingAs($user)
            ->post(route('debts.repayments.store', $debt), [
                'amount' => '150.25',
                'occurred_on' => '2026-10-05',
                'note' => 'دفعة أولى',
            ])
            ->assertRedirect(route('debts.index'))
            ->assertSessionHas('status', 'تم تسجيل الدفعة.');

        $repayment = $debt->repayments()->sole();
        $this->assertSame('150.25', $repayment->amount);
        $this->assertSame('2026-10-05', $repayment->occurred_on->toDateString());
        $this->assertSame('دفعة أولى', $repayment->note);
        $this->assertSame($user->id, $repayment->user_id);
        $this->assertFalse($debt->refresh()->isSettled());
    }

    public function test_paying_a_debt_i_owe_is_recorded_as_an_expense(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user, DebtDirection::OwedByMe);

        $this->actingAs($user)->post(route('debts.repayments.store', $debt), ['amount' => '100']);

        $this->assertSame(TransactionType::Expense, $debt->repayments()->sole()->type);
    }

    public function test_being_paid_a_debt_owed_to_me_is_recorded_as_income(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user, DebtDirection::OwedToMe);

        $this->actingAs($user)->post(route('debts.repayments.store', $debt), ['amount' => '100']);

        $this->assertSame(TransactionType::Income, $debt->repayments()->sole()->type);
    }

    public function test_payment_cannot_be_more_than_what_is_left(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);

        $this->actingAs($user)
            ->post(route('debts.repayments.store', $debt), ['amount' => '500.01'])
            ->assertSessionHasErrors(['amount' => 'الدفعة لا يمكن أن تزيد عن المتبقي من الدين (500.00).']);

        $this->assertFalse($debt->repayments()->exists());
    }

    public function test_settled_debt_takes_no_more_payments(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);
        app(TransactionService::class)->repayDebt($debt);

        $this->actingAs($user)
            ->post(route('debts.repayments.store', $debt))
            ->assertSessionHasErrors(['amount' => 'هذا الدين انسدّ من قبل.']);

        $this->assertSame(1, $debt->repayments()->count());
    }

    public function test_invalid_values_are_rejected_with_a_message_for_each_field(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);

        $this->actingAs($user)
            ->post(route('debts.repayments.store', $debt), ['amount' => '-5', 'occurred_on' => 'not-a-date'])
            ->assertSessionHasErrors([
                'amount' => 'المبلغ يجب أن يكون أكبر من صفر.',
                'occurred_on' => 'اختر تاريخًا صحيحًا.',
            ]);

        $this->assertFalse($debt->repayments()->exists());
    }

    public function test_another_user_is_forbidden_from_paying_a_debt(): void
    {
        $debt = $this->debt(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post(route('debts.repayments.store', $debt))
            ->assertForbidden();

        $this->assertFalse($debt->repayments()->exists());
    }

    public function test_payment_is_forbidden_against_a_transaction_that_is_not_a_debt(): void
    {
        $user = User::factory()->create();
        $expense = Transaction::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('debts.repayments.store', $expense), ['amount' => '1'])
            ->assertForbidden();

        $this->assertSame(1, $user->transactions()->count());
    }

    public function test_debt_list_shows_why_a_payment_was_refused(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);
        app(TransactionService::class)->repayDebt($debt);

        $this->actingAs($user)
            ->from(route('debts.index'))
            ->followingRedirects()
            ->post(route('debts.repayments.store', $debt))
            ->assertOk()
            ->assertSeeText('هذا الدين انسدّ من قبل.');
    }
}
