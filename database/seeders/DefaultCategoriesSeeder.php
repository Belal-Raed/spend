<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    /**
     * The categories every user starts with, by transaction type.
     *
     * @var array<string, list<string>>
     */
    public const CATEGORIES = [
        'expense' => ['أكل', 'مواصلات', 'بيت', 'فواتير', 'صحة', 'اتصالات', 'أخرى'],
        'income' => ['راتب', 'شغل حر', 'أخرى'],
    ];

    /**
     * Create the shared default categories. Safe to run more than once.
     *
     * The defaults belong to no user, so they are looked up across tenants.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $type => $names) {
            foreach ($names as $name) {
                Category::query()->acrossTenants()->firstOrCreate([
                    'user_id' => null,
                    'type' => TransactionType::from($type),
                    'name' => $name,
                ]);
            }
        }
    }
}
