<?php

namespace Tests\Feature\Seeders;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_monthly_and_three_month_plans_once_without_a_price(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->assertSame(
            [['شهري', 1, null], ['3 شهور', 3, null]],
            Plan::query()->orderBy('months')->get()->map(fn (Plan $plan): array => [$plan->name, $plan->months, $plan->price])->all(),
        );
    }

    public function test_keeps_the_price_the_admin_set(): void
    {
        Plan::factory()->create(['months' => 1, 'name' => 'شهري', 'price' => '25.00']);

        $this->seed(PlanSeeder::class);

        $this->assertSame('25.00', Plan::query()->where('months', 1)->sole()->price);
    }
}
