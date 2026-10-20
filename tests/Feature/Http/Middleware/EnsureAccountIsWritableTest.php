<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnsureAccountIsWritableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every route that adds, changes or deletes records, as [method, route name, needs].
     *
     * `needs` names the record the route takes: a transaction, a debt or a category.
     *
     * @return array<string, array{0: string, 1: string, 2: ?string}>
     */
    public static function writeRoutes(): array
    {
        return [
            'new transaction form' => ['get', 'transactions.create', null],
            'record a transaction' => ['post', 'transactions.store', null],
            'edit transaction form' => ['get', 'transactions.edit', 'transaction'],
            'change a transaction' => ['put', 'transactions.update', 'transaction'],
            'delete a transaction' => ['delete', 'transactions.destroy', 'transaction'],
            'repayment form' => ['get', 'debts.repayments.create', 'debt'],
            'record a repayment' => ['post', 'debts.repayments.store', 'debt'],
            'add a category' => ['post', 'categories.store', null],
            'edit category form' => ['get', 'categories.edit', 'category'],
            'rename a category' => ['put', 'categories.update', 'category'],
            'delete a category' => ['delete', 'categories.destroy', 'category'],
        ];
    }

    #[DataProvider('writeRoutes')]
    public function test_read_only_user_is_sent_to_the_subscription_page(string $method, string $routeName, ?string $needs): void
    {
        $user = User::factory()->readOnly()->create();
        $record = match ($needs) {
            'transaction' => Transaction::factory()->for($user)->create(),
            'debt' => Transaction::factory()->for($user)->debt()->create(),
            'category' => Category::factory()->ownedBy($user)->create(),
            null => null,
        };

        $this->actingAs($user)
            ->{$method}(route($routeName, $record))
            ->assertRedirect(route('subscription'))
            ->assertInertiaFlash('status', 'حسابك للقراءة فقط. اشترك لتضيف وتعدّل.');
    }

    public function test_read_only_user_cannot_record_a_transaction(): void
    {
        $user = User::factory()->readOnly()->create();

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'debt',
            'amount' => '50',
            'occurred_on' => '2026-10-20',
            'counterparty' => 'سامي',
            'debt_direction' => 'owed_to_me',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function readRoutes(): array
    {
        return [
            'home' => ['home'],
            'transactions' => ['transactions.index'],
            'debts' => ['debts.index'],
            'categories' => ['categories.index'],
            'settings' => ['settings'],
            'export' => ['export'],
            'subscription' => ['subscription'],
        ];
    }

    #[DataProvider('readRoutes')]
    public function test_read_only_user_can_still_view_their_records(string $routeName): void
    {
        $this->actingAs(User::factory()->readOnly()->create())
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_subscribed_user_whose_trial_ended_can_still_write(): void
    {
        $this->actingAs(User::factory()->subscribedUntil(now()->addMonth())->create())
            ->get(route('transactions.create'))
            ->assertOk();
    }
}
