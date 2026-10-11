<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_user_needs_no_email_or_password(): void
    {
        $user = User::create(['telegram_id' => '123456789', 'name' => 'أبو محمد']);

        $this->assertModelExists($user);
        $this->assertNull($user->fresh()->email);
    }

    public function test_rejects_two_users_with_the_same_telegram_account(): void
    {
        User::create(['telegram_id' => '123456789', 'name' => 'أبو محمد']);

        $this->expectException(UniqueConstraintViolationException::class);

        User::create(['telegram_id' => '123456789', 'name' => 'نسخة']);
    }
}
