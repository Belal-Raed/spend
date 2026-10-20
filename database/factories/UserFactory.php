<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('059#######'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A user whose trial ended without a subscription, so their account is read-only.
     */
    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * A user with a subscription running until the given time.
     */
    public function subscribedUntil(\DateTimeInterface $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subMonth(),
            'subscription_ends_at' => $endsAt,
        ]);
    }
}
