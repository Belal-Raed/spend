<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    private const WRONG_CREDENTIALS = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';

    public function test_guest_sees_the_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_signed_in_user_is_redirected_away_from_the_login_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_correct_credentials_sign_the_user_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'not-the-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::WRONG_CREDENTIALS]);

        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_same_message_as_a_wrong_password(): void
    {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => self::WRONG_CREDENTIALS]);

        $this->assertGuest();
    }

    public function test_telegram_user_without_a_password_cannot_sign_in(): void
    {
        $user = User::factory()->create(['telegram_id' => '123456789', 'password' => null]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => self::WRONG_CREDENTIALS]);

        $this->assertGuest();
    }

    public function test_empty_form_is_rejected_with_a_message_for_each_field(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors([
                'email' => 'اكتب البريد الإلكتروني.',
                'password' => 'اكتب كلمة المرور.',
            ]);
    }

    public function test_sixth_failed_attempt_is_locked_out_even_with_the_correct_password(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'not-the-password']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'محاولات كثيرة. حاول مرة أخرى بعد 60 ثانية.']);

        $this->assertGuest();
    }

    public function test_lockout_ends_after_a_minute(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'not-the-password']);
        }

        $this->travel(61)->seconds();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_is_sent_back_to_the_page_they_wanted_after_signing_in(): void
    {
        $user = User::factory()->create();

        $this->get(route('home'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
    }

    public function test_logout_signs_the_user_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_log_out(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }
}
