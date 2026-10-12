<?php

namespace Tests\Feature\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_user_is_allowed_on_a_category_they_created(string $ability): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create();

        $this->assertTrue($user->can($ability, $category));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_user_is_refused_on_another_users_category(string $ability): void
    {
        $category = Category::factory()->ownedBy(User::factory()->create())->create();

        $this->assertFalse(User::factory()->create()->can($ability, $category));
    }

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_user_is_refused_on_a_shared_default_category(string $ability): void
    {
        $category = Category::factory()->create();

        $this->assertFalse(User::factory()->create()->can($ability, $category));
    }
}
