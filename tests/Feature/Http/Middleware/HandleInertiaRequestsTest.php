<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_get_no_access_details(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page->where('access', null));
    }

    public function test_trial_far_from_its_end_is_not_ending_soon(): void
    {
        Carbon::setTestNow('2026-10-20 09:00');
        $user = User::factory()->create(['trial_ends_at' => '2026-10-25 09:00']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('access', [
                'status' => 'trial',
                'status_label' => 'فترة تجربة',
                'ends_on' => '2026-10-25',
                'days_left' => 5,
                'is_ending_soon' => false,
            ]));
    }

    public function test_trial_in_its_last_two_days_is_ending_soon(): void
    {
        Carbon::setTestNow('2026-10-20 09:00');
        $user = User::factory()->create(['trial_ends_at' => '2026-10-21 21:00']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('access.days_left', 2)
                ->where('access.is_ending_soon', true));
    }

    public function test_read_only_account_has_no_days_left_and_is_not_ending_soon(): void
    {
        $this->actingAs(User::factory()->readOnly()->create())
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('access.status', 'read_only')
                ->where('access.days_left', 0)
                ->where('access.is_ending_soon', false));
    }
}
