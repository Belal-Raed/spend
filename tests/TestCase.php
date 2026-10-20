<?php

namespace Tests;

use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrentTenant;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pages render without the built frontend assets, so tests do not depend on `npm run build`.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Sign a new user in, which makes them the tenant every model query is limited to.
     *
     * Create other users' records before calling this: while a tenant is set,
     * saving a record that belongs to someone else throws.
     */
    protected function signIn(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    /**
     * Record a payment against a debt as its owner, without signing anyone in.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function repayAsOwner(Transaction $debt, array $attributes = []): Transaction
    {
        return app(CurrentTenant::class)->runAs(
            $debt->user,
            fn (): Transaction => app(TransactionService::class)->repayDebt($debt, $attributes),
        );
    }
}
