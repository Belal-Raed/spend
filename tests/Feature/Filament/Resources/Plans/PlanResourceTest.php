<?php

namespace Tests\Feature\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sets_a_plans_price(): void
    {
        $plan = Plan::factory()->unpriced()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManagePlans::class)
            ->callAction(TestAction::make('edit')->table($plan), data: ['price' => '25'])
            ->assertHasNoFormErrors();

        $this->assertSame('25.00', $plan->fresh()->price);
        $this->assertTrue($plan->fresh()->isPurchasable());
    }

    public function test_admin_stops_offering_a_plan(): void
    {
        $plan = Plan::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManagePlans::class)
            ->callAction(TestAction::make('edit')->table($plan), data: ['is_active' => false]);

        $this->assertFalse($plan->fresh()->is_active);
    }

    public function test_admin_adds_a_plan(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManagePlans::class)
            ->callAction('create', data: ['name' => 'سنوي', 'months' => 12, 'price' => '200', 'is_active' => true])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('plans', ['name' => 'سنوي', 'months' => 12, 'price' => '200.00']);
    }

    public function test_plans_cannot_be_deleted(): void
    {
        $plan = Plan::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManagePlans::class)
            ->assertActionDoesNotExist(TestAction::make('delete')->table($plan));
    }
}
