<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state: a monthly plan with a price.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'شهري',
            'months' => 1,
            'price' => '20.00',
            'is_active' => true,
        ];
    }

    /**
     * A plan the admin has not priced yet.
     */
    public function unpriced(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
        ]);
    }

    /**
     * A plan the admin stopped offering.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
