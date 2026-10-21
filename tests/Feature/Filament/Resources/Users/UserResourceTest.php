<?php

namespace Tests\Feature\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_users_by_access(): void
    {
        $readOnly = User::factory()->readOnly()->create();
        $subscribed = User::factory()->subscribedUntil(now()->addMonth())->create();
        $onTrial = User::factory()->create();
        $this->actingAs($admin = User::factory()->admin()->create());

        Livewire::test(ManageUsers::class)
            ->filterTable('access', 'read_only')
            ->assertCanSeeTableRecords([$readOnly])
            ->assertCanNotSeeTableRecords([$subscribed, $onTrial, $admin]);
    }

    public function test_admin_gives_a_read_only_user_days_from_today(): void
    {
        Carbon::setTestNow('2026-10-21 09:00');
        $user = User::factory()->readOnly()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('grantDays')->table($user), data: ['days' => 7])
            ->assertHasNoFormErrors();

        $this->assertSame('2026-10-28 09:00:00', $user->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertTrue($user->fresh()->hasFullAccess());
    }

    public function test_days_must_be_between_one_and_a_year(): void
    {
        $user = User::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('grantDays')->table($user), data: ['days' => 0])
            ->assertHasFormErrors(['days' => 'min']);

        $this->assertNull($user->fresh()->subscription_ends_at);
    }
}
