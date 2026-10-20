<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * The plans offered, as months => name.
     *
     * @var array<int, string>
     */
    public const PLANS = [
        1 => 'شهري',
        3 => '3 شهور',
    ];

    /**
     * Create the plans without a price; the admin sets it from the admin panel.
     *
     * Safe to run more than once, and never touches a plan that already exists.
     */
    public function run(): void
    {
        foreach (self::PLANS as $months => $name) {
            Plan::query()->firstOrCreate(['months' => $months], ['name' => $name]);
        }
    }
}
