<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_makes_the_user_with_that_phone_an_admin(): void
    {
        $user = User::factory()->create(['phone' => '0599123456']);

        $this->artisan('app:make-admin', ['phone' => '0599123456'])->assertSuccessful();

        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_revokes_the_admin_panel(): void
    {
        $user = User::factory()->admin()->create(['phone' => '0599123456']);

        $this->artisan('app:make-admin', ['phone' => '0599123456', '--revoke' => true])->assertSuccessful();

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_fails_when_no_user_has_that_phone(): void
    {
        $this->artisan('app:make-admin', ['phone' => '0599123456'])
            ->expectsOutput('No user signs in with that phone number.')
            ->assertFailed();
    }
}
