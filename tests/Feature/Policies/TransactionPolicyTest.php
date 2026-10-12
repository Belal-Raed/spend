<?php

namespace Tests\Feature\Policies;

use App\Enums\DebtDirection;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TransactionPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_owner_is_allowed(string $ability): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertTrue($transaction->user->can($ability, $transaction));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_another_user_is_refused(string $ability): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertFalse(User::factory()->create()->can($ability, $transaction));
    }

    public function test_owner_may_pay_their_own_debt(): void
    {
        $debt = Transaction::factory()->debt(DebtDirection::OwedByMe)->create();

        $this->assertTrue($debt->user->can('repay', $debt));
    }

    public function test_another_user_may_not_pay_the_debt(): void
    {
        $debt = Transaction::factory()->debt(DebtDirection::OwedByMe)->create();

        $this->assertFalse(User::factory()->create()->can('repay', $debt));
    }

    public function test_owner_may_not_pay_a_transaction_that_is_not_a_debt(): void
    {
        $expense = Transaction::factory()->create();

        $this->assertFalse($expense->user->can('repay', $expense));
    }
}
