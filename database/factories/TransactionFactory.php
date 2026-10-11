<?php

namespace Database\Factories;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state: an expense.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => TransactionType::Expense,
            'amount' => fake()->randomFloat(2, 1, 200),
            'category_id' => Category::factory(),
            'note' => fake()->words(2, true),
            'occurred_on' => today(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
            'category_id' => Category::factory()->income(),
        ]);
    }

    public function debt(DebtDirection $direction = DebtDirection::OwedToMe): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Debt,
            'category_id' => null,
            'counterparty' => fake()->firstName(),
            'debt_direction' => $direction,
        ]);
    }
}
