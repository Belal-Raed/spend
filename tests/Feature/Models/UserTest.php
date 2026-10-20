<?php

namespace Tests\Feature\Models;

use App\Enums\AccessStatus;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_user_needs_no_phone_or_password(): void
    {
        $user = User::create(['telegram_id' => '123456789', 'name' => 'أبو محمد']);

        $this->assertModelExists($user);
        $this->assertNull($user->fresh()->phone);
    }

    public function test_rejects_two_users_with_the_same_telegram_account(): void
    {
        User::create(['telegram_id' => '123456789', 'name' => 'أبو محمد']);

        $this->expectException(UniqueConstraintViolationException::class);

        User::create(['telegram_id' => '123456789', 'name' => 'نسخة']);
    }

    public function test_new_user_starts_a_seven_day_trial_with_full_access(): void
    {
        Carbon::setTestNow('2026-10-20 09:00');

        $user = User::create(['telegram_id' => '123456789', 'name' => 'أبو محمد']);

        $this->assertSame('2026-10-27 09:00:00', $user->trial_ends_at->toDateTimeString());
        $this->assertSame(AccessStatus::Trial, $user->accessStatus());
        $this->assertTrue($user->hasFullAccess());
    }

    public function test_account_turns_read_only_once_the_trial_ends(): void
    {
        Carbon::setTestNow('2026-10-20 09:00');
        $user = User::factory()->create();

        Carbon::setTestNow('2026-10-27 09:00');

        $this->assertSame(AccessStatus::ReadOnly, $user->accessStatus());
        $this->assertFalse($user->hasFullAccess());
    }

    public function test_running_subscription_gives_full_access_after_the_trial(): void
    {
        $user = User::factory()->subscribedUntil(now()->addDay())->create();

        $this->assertSame(AccessStatus::Subscribed, $user->accessStatus());
        $this->assertTrue($user->hasFullAccess());
    }

    public function test_access_ends_with_the_later_of_the_trial_and_the_subscription(): void
    {
        $user = User::factory()->create([
            'trial_ends_at' => '2026-10-27 09:00',
            'subscription_ends_at' => '2026-11-27 09:00',
        ]);

        $this->assertSame('2026-11-27 09:00:00', $user->accessEndsAt()->toDateTimeString());
    }
}
