<?php

namespace Tests\Feature\Models\Concerns;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class BelongsToTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_see_only_the_tenants_transactions(): void
    {
        $strangers = Transaction::factory()->create();
        $user = $this->signIn();
        $own = Transaction::factory()->for($user)->create();

        $this->assertSame([$own->id], Transaction::query()->pluck('id')->all());
        $this->assertNull(Transaction::query()->find($strangers->id));
    }

    public function test_another_users_relation_returns_nothing_to_the_tenant(): void
    {
        $stranger = User::factory()->create();
        Transaction::factory()->for($stranger)->create();
        Category::factory()->ownedBy($stranger)->create();
        $this->signIn();

        $this->assertSame(0, $stranger->transactions()->count());
        $this->assertSame(0, $stranger->categories()->count());
    }

    public function test_queries_see_the_shared_categories_and_the_tenants_own(): void
    {
        $shared = Category::factory()->create();
        Category::factory()->ownedBy(User::factory()->create())->create();
        $user = $this->signIn();
        $own = Category::factory()->ownedBy($user)->create();

        $this->assertEqualsCanonicalizing([$shared->id, $own->id], Category::query()->pluck('id')->all());
    }

    public function test_querying_without_a_tenant_throws_instead_of_returning_every_users_records(): void
    {
        Transaction::factory()->create();

        $this->expectException(LogicException::class);

        Transaction::query()->get();
    }

    public function test_saving_a_record_for_another_user_throws(): void
    {
        $stranger = User::factory()->create();
        $this->signIn();

        try {
            Transaction::factory()->for($stranger)->create();
            $this->fail('Expected saving another user\'s transaction to throw.');
        } catch (LogicException) {
            $this->assertDatabaseCount('transactions', 0);
        }
    }

    public function test_handing_a_record_to_another_user_throws(): void
    {
        $stranger = User::factory()->create();
        $user = $this->signIn();
        $category = Category::factory()->ownedBy($user)->create();

        try {
            $category->update(['user_id' => $stranger->id]);
            $this->fail('Expected handing the category to another user to throw.');
        } catch (LogicException) {
            $this->assertDatabaseHas('categories', ['id' => $category->id, 'user_id' => $user->id]);
        }
    }

    public function test_across_tenants_sees_every_users_records(): void
    {
        Transaction::factory()->count(2)->create();

        $this->assertSame(2, Transaction::query()->acrossTenants()->count());
    }
}
