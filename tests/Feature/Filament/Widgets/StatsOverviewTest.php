<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\StatsOverview;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class StatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_users_by_access_receipts_to_review_and_this_months_income(): void
    {
        Carbon::setTestNow('2026-10-21 09:00');
        User::factory()->readOnly()->create();
        User::factory()->subscribedUntil(now()->addMonth())->create();
        Payment::factory()->create();
        Payment::factory()->approved()->create(['amount' => '20.00', 'reviewed_at' => '2026-10-05 10:00']);
        Payment::factory()->approved()->create(['amount' => '55.50', 'reviewed_at' => '2026-10-20 10:00']);
        Payment::factory()->approved()->create(['amount' => '99.00', 'reviewed_at' => '2026-09-30 10:00']);
        $this->actingAs(User::factory()->admin()->create());

        // Each payment's factory made its own user on a trial, as did the admin: 2 + 4 + 1 = 7 users, 5 on trial.
        Livewire::test(StatsOverview::class)
            ->assertSeeInOrder(['المستخدمون', '7'])
            ->assertSeeInOrder(['مشتركون الآن', '1'])
            ->assertSeeInOrder(['في فترة التجربة', '5'])
            ->assertSeeInOrder(['إشعارات بانتظار المراجعة', '1'])
            ->assertSeeInOrder(['دخل هذا الشهر', '75.50']);
    }
}
