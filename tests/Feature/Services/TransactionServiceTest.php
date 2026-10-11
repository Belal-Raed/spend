<?php

namespace Tests\Feature\Services;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TransactionService
    {
        return app(TransactionService::class);
    }

    /**
     * Run the callback and return the validation errors it raised.
     *
     * @return array<string, list<string>>
     */
    private function validationErrors(callable $callback): array
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        $this->fail('Expected a validation error.');
    }

    private function debt(User $user, DebtDirection $direction, string $amount): Transaction
    {
        return Transaction::factory()->for($user)->debt($direction)->create(['amount' => $amount]);
    }

    public function test_records_expense_in_default_category_dated_today(): void
    {
        Carbon::setTestNow('2026-10-11 09:00');
        $user = User::factory()->create();
        $food = Category::factory()->create(['name' => 'أكل']);

        $expense = $this->service()->record($user, [
            'type' => TransactionType::Expense,
            'amount' => '5',
            'category_id' => $food->id,
            'note' => 'خبز',
            'raw_text' => 'اشتريت خبز بـ 5',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'category_id' => $food->id,
            'note' => 'خبز',
            'occurred_on' => '2026-10-11',
            'raw_text' => 'اشتريت خبز بـ 5',
        ]);
        $this->assertSame('5.00', $expense->fresh()->amount);
    }

    public function test_records_expense_on_given_date(): void
    {
        $expense = $this->service()->record(User::factory()->create(), [
            'type' => 'expense',
            'amount' => 10,
            'category_id' => Category::factory()->create()->id,
            'occurred_on' => '2026-10-10',
        ]);

        $this->assertSame('2026-10-10', $expense->occurred_on->toDateString());
    }

    public function test_records_income_with_its_source(): void
    {
        $income = $this->service()->record(User::factory()->create(), [
            'type' => 'income',
            'amount' => 1500,
            'category_id' => Category::factory()->income()->create()->id,
            'counterparty' => 'الشركة',
        ]);

        $this->assertSame(TransactionType::Income, $income->type);
        $this->assertSame('الشركة', $income->counterparty);
    }

    public function test_records_debt_without_category(): void
    {
        $debt = $this->service()->record(User::factory()->create(), [
            'type' => 'debt',
            'amount' => 100,
            'counterparty' => 'أحمد',
            'debt_direction' => DebtDirection::OwedToMe,
        ]);

        $this->assertSame(DebtDirection::OwedToMe, $debt->debt_direction);
        $this->assertNull($debt->category_id);
        $this->assertFalse($debt->isSettled());
    }

    public function test_accepts_the_user_own_category(): void
    {
        $user = User::factory()->create();

        $expense = $this->service()->record($user, [
            'type' => 'expense',
            'amount' => 3,
            'category_id' => Category::factory()->ownedBy($user)->create()->id,
        ]);

        $this->assertNotNull($expense->id);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidRecords(): array
    {
        return [
            'zero amount' => [['type' => 'expense', 'amount' => 0], 'amount'],
            'negative amount' => [['type' => 'expense', 'amount' => -5], 'amount'],
            'unknown type' => [['type' => 'gift', 'amount' => 5], 'type'],
            'expense without category' => [['type' => 'expense', 'amount' => 5], 'category_id'],
            'debt without counterparty' => [['type' => 'debt', 'amount' => 5, 'debt_direction' => 'owed_to_me'], 'counterparty'],
            'debt without direction' => [['type' => 'debt', 'amount' => 5, 'counterparty' => 'أحمد'], 'debt_direction'],
            'unknown direction' => [['type' => 'debt', 'amount' => 5, 'counterparty' => 'أحمد', 'debt_direction' => 'sideways'], 'debt_direction'],
            'invalid date' => [['type' => 'debt', 'amount' => 5, 'counterparty' => 'أحمد', 'debt_direction' => 'owed_to_me', 'occurred_on' => 'yesterday-ish'], 'occurred_on'],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    #[DataProvider('invalidRecords')]
    public function test_rejects_invalid_record(array $attributes, string $field): void
    {
        $errors = $this->validationErrors(fn () => $this->service()->record(User::factory()->create(), $attributes));

        $this->assertArrayHasKey($field, $errors);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_category_of_another_user(): void
    {
        $strangersCategory = Category::factory()->ownedBy(User::factory()->create())->create();

        $errors = $this->validationErrors(fn () => $this->service()->record(User::factory()->create(), [
            'type' => 'expense',
            'amount' => 5,
            'category_id' => $strangersCategory->id,
        ]));

        $this->assertArrayHasKey('category_id', $errors);
    }

    public function test_rejects_income_category_on_expense(): void
    {
        $errors = $this->validationErrors(fn () => $this->service()->record(User::factory()->create(), [
            'type' => 'expense',
            'amount' => 5,
            'category_id' => Category::factory()->income()->create()->id,
        ]));

        $this->assertArrayHasKey('category_id', $errors);
    }

    public function test_rejects_category_on_debt(): void
    {
        $errors = $this->validationErrors(fn () => $this->service()->record(User::factory()->create(), [
            'type' => 'debt',
            'amount' => 5,
            'counterparty' => 'أحمد',
            'debt_direction' => 'owed_by_me',
            'category_id' => Category::factory()->create()->id,
        ]));

        $this->assertArrayHasKey('category_id', $errors);
    }

    public function test_rejects_direction_on_expense(): void
    {
        $errors = $this->validationErrors(fn () => $this->service()->record(User::factory()->create(), [
            'type' => 'expense',
            'amount' => 5,
            'category_id' => Category::factory()->create()->id,
            'debt_direction' => 'owed_by_me',
        ]));

        $this->assertArrayHasKey('debt_direction', $errors);
    }

    public function test_updates_only_given_attributes(): void
    {
        $expense = Transaction::factory()->create(['amount' => '10.00', 'note' => 'غاز']);

        $this->service()->update($expense, ['amount' => 15]);

        $this->assertSame('15.00', $expense->fresh()->amount);
        $this->assertSame('غاز', $expense->fresh()->note);
    }

    public function test_rejects_changing_type(): void
    {
        $expense = Transaction::factory()->create();

        $errors = $this->validationErrors(fn () => $this->service()->update($expense, ['type' => 'income']));

        $this->assertArrayHasKey('type', $errors);
        $this->assertSame(TransactionType::Expense, $expense->fresh()->type);
    }

    public function test_rejects_moving_expense_to_income_category(): void
    {
        $expense = Transaction::factory()->create();

        $errors = $this->validationErrors(fn () => $this->service()->update($expense, [
            'category_id' => Category::factory()->income()->create()->id,
        ]));

        $this->assertArrayHasKey('category_id', $errors);
    }

    public function test_deletes_transaction(): void
    {
        $expense = Transaction::factory()->create();

        $this->service()->delete($expense);

        $this->assertModelMissing($expense);
    }

    public function test_latest_returns_most_recently_recorded_transaction_of_the_user(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['occurred_on' => '2026-10-11']);
        $latest = Transaction::factory()->for($user)->create(['occurred_on' => '2026-10-01']);
        Transaction::factory()->create();

        $this->assertTrue($latest->is($this->service()->latest($user)));
    }

    public function test_latest_filters_by_type(): void
    {
        $user = User::factory()->create();
        $expense = Transaction::factory()->for($user)->create();
        Transaction::factory()->for($user)->income()->create();

        $this->assertTrue($expense->is($this->service()->latest($user, TransactionType::Expense)));
    }

    public function test_partial_payment_of_debt_owed_to_me_is_income_and_keeps_debt_open(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user, DebtDirection::OwedToMe, '100.00');

        $payment = $this->service()->repayDebt($debt, ['amount' => 40, 'occurred_on' => '2026-10-05']);

        $this->assertSame(TransactionType::Income, $payment->type);
        $this->assertSame('40.00', $payment->amount);
        $this->assertSame($debt->counterparty, $payment->counterparty);
        $this->assertTrue($debt->is($payment->debt));
        $this->assertSame(6000, $debt->fresh()->remainingInCents());
        $this->assertFalse($debt->fresh()->isSettled());
    }

    public function test_payment_of_debt_owed_by_me_is_expense(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedByMe, '100.00');

        $payment = $this->service()->repayDebt($debt, ['amount' => 30]);

        $this->assertSame(TransactionType::Expense, $payment->type);
    }

    public function test_payments_that_reach_the_full_amount_settle_debt_on_last_payment_date(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');

        $this->service()->repayDebt($debt, ['amount' => 40, 'occurred_on' => '2026-10-05']);
        $this->service()->repayDebt($debt, ['amount' => 60, 'occurred_on' => '2026-10-09']);

        $this->assertSame('2026-10-09', $debt->fresh()->settled_on->toDateString());
    }

    public function test_payment_without_amount_pays_off_what_is_left(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedByMe, '100.00');
        $this->service()->repayDebt($debt, ['amount' => 25.5]);

        $payment = $this->service()->repayDebt($debt);

        $this->assertSame('74.50', $payment->amount);
        $this->assertTrue($debt->fresh()->isSettled());
    }

    public function test_rejects_payment_above_what_is_left(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $this->service()->repayDebt($debt, ['amount' => 70]);

        $errors = $this->validationErrors(fn () => $this->service()->repayDebt($debt, ['amount' => 30.01]));

        $this->assertArrayHasKey('amount', $errors);
        $this->assertSame(1, $debt->repayments()->count());
    }

    public function test_rejects_payment_on_settled_debt(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $this->service()->repayDebt($debt);

        $errors = $this->validationErrors(fn () => $this->service()->repayDebt($debt, ['amount' => 1]));

        $this->assertArrayHasKey('debt', $errors);
    }

    public function test_rejects_paying_off_something_that_is_not_a_debt(): void
    {
        $errors = $this->validationErrors(fn () => $this->service()->repayDebt(Transaction::factory()->create()));

        $this->assertArrayHasKey('debt', $errors);
    }

    public function test_deleting_payment_reopens_settled_debt(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $this->service()->repayDebt($debt, ['amount' => 40]);
        $lastPayment = $this->service()->repayDebt($debt);

        $this->service()->delete($lastPayment);

        $this->assertFalse($debt->fresh()->isSettled());
        $this->assertSame(6000, $debt->fresh()->remainingInCents());
    }

    public function test_deleting_debt_deletes_its_payments(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $payment = $this->service()->repayDebt($debt, ['amount' => 40]);

        $this->service()->delete($debt);

        $this->assertModelMissing($debt);
        $this->assertModelMissing($payment);
    }

    public function test_raising_debt_amount_reopens_settled_debt(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $this->service()->repayDebt($debt);

        $this->service()->update($debt, ['amount' => 150]);

        $this->assertFalse($debt->fresh()->isSettled());
        $this->assertSame(5000, $debt->fresh()->remainingInCents());
    }

    public function test_lowering_debt_to_what_was_paid_settles_it(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $this->service()->repayDebt($debt, ['amount' => 40, 'occurred_on' => '2026-10-05']);

        $this->service()->update($debt, ['amount' => 40]);

        $this->assertSame('2026-10-05', $debt->fresh()->settled_on->toDateString());
    }

    public function test_rejects_lowering_debt_below_what_was_paid(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $this->service()->repayDebt($debt, ['amount' => 40]);

        $errors = $this->validationErrors(fn () => $this->service()->update($debt, ['amount' => 39.99]));

        $this->assertArrayHasKey('amount', $errors);
        $this->assertSame('100.00', $debt->fresh()->amount);
    }

    public function test_raising_payment_to_what_is_left_settles_debt(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $payment = $this->service()->repayDebt($debt, ['amount' => 40]);

        $this->service()->update($payment, ['amount' => 100]);

        $this->assertTrue($debt->fresh()->isSettled());
    }

    public function test_rejects_raising_payment_above_what_is_left(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedToMe, '100.00');
        $payment = $this->service()->repayDebt($debt, ['amount' => 40]);
        $this->service()->repayDebt($debt, ['amount' => 50]);

        $errors = $this->validationErrors(fn () => $this->service()->update($payment, ['amount' => 50.01]));

        $this->assertArrayHasKey('amount', $errors);
    }

    public function test_rejects_category_on_payment(): void
    {
        $debt = $this->debt(User::factory()->create(), DebtDirection::OwedByMe, '100.00');
        $payment = $this->service()->repayDebt($debt, ['amount' => 40]);

        $errors = $this->validationErrors(fn () => $this->service()->update($payment, [
            'category_id' => Category::factory()->create()->id,
        ]));

        $this->assertArrayHasKey('category_id', $errors);
    }
}
