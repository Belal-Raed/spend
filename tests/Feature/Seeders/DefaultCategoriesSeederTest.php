<?php

namespace Tests\Feature\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultCategoriesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_shared_expense_and_income_categories_once(): void
    {
        $this->seed(DefaultCategoriesSeeder::class);
        $this->seed(DefaultCategoriesSeeder::class);

        $this->assertSame(10, Category::query()->whereNull('user_id')->count());
        $this->assertTrue(Category::query()->ofType(TransactionType::Expense)->where('name', 'أكل')->exists());
        $this->assertTrue(Category::query()->ofType(TransactionType::Income)->where('name', 'راتب')->exists());
    }
}
