<?php

namespace Tests\Feature\Seeders;

use App\Enums\DebtDirection;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    private function demoUser(): User
    {
        return User::query()->where('email', DemoDataSeeder::EMAIL)->sole();
    }

    public function test_creates_a_demo_user_who_can_sign_in_with_the_default_password(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertTrue(Hash::check('password', $this->demoUser()->password));
    }

    public function test_gives_the_demo_user_income_expenses_and_debts(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(
            ['cash' => 1200.0, 'owed_by_me' => 400.0, 'owed_to_me' => 200.0, 'after_debts' => 1000.0],
            app(ReportService::class)->balance($this->demoUser()),
        );
    }

    public function test_leaves_one_debt_partly_paid_one_untouched_and_one_settled(): void
    {
        $this->seed(DemoDataSeeder::class);

        $reports = app(ReportService::class);
        $owedByMe = $reports->openDebts($this->demoUser(), DebtDirection::OwedByMe);
        $owedToMe = $reports->openDebts($this->demoUser(), DebtDirection::OwedToMe);

        $this->assertSame([['خالد', 100.0, 400.0]], array_map(
            fn (array $debt): array => [$debt['counterparty'], $debt['repaid'], $debt['remaining']],
            $owedByMe,
        ));
        $this->assertSame([['سامي', 0.0, 200.0]], array_map(
            fn (array $debt): array => [$debt['counterparty'], $debt['repaid'], $debt['remaining']],
            $owedToMe,
        ));
        $this->assertTrue(
            Transaction::query()->where('counterparty', 'محمود')->whereNull('settles_id')->sole()->isSettled(),
        );
    }

    public function test_does_not_add_transactions_twice(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(11, $this->demoUser()->transactions()->count());
    }

    public function test_uses_an_existing_demo_user_instead_of_creating_another(): void
    {
        $existing = User::factory()->create(['email' => DemoDataSeeder::EMAIL]);

        $this->seed(DemoDataSeeder::class);

        $this->assertTrue($this->demoUser()->is($existing));
        $this->assertSame(11, $existing->transactions()->count());
    }

    public function test_database_seeder_can_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(11, $this->demoUser()->transactions()->count());
    }
}
