<?php

namespace Tests\Feature\Policies;

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
}
