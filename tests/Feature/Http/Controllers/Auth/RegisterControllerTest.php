<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'أبو محمد',
            'email' => 'abu.mohammad@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            ...$overrides,
        ];
    }

    public function test_guest_sees_the_registration_form(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertViewIs('auth.register');
    }

    public function test_signed_in_user_is_redirected_away_from_the_registration_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('register'))
            ->assertRedirect(route('home'));
    }

    public function test_valid_form_creates_the_account_and_signs_the_user_in(): void
    {
        $this->post(route('register.store'), $this->validPayload())
            ->assertRedirect(route('home'));

        $user = User::query()->where('email', 'abu.mohammad@example.com')->sole();

        $this->assertSame('أبو محمد', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_empty_form_is_rejected_with_a_message_for_each_field(): void
    {
        $this->post(route('register.store'), [])
            ->assertSessionHasErrors([
                'name' => 'اكتب الاسم.',
                'email' => 'اكتب البريد الإلكتروني.',
                'password' => 'اكتب كلمة المرور.',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_that_already_has_an_account_is_rejected(): void
    {
        User::factory()->create(['email' => 'abu.mohammad@example.com']);

        $this->post(route('register.store'), $this->validPayload())
            ->assertSessionHasErrors(['email' => 'هذا البريد الإلكتروني مسجّل من قبل.']);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_malformed_email_is_rejected(): void
    {
        $this->post(route('register.store'), $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors(['email' => 'البريد الإلكتروني غير صحيح.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_shorter_than_eight_characters_is_rejected(): void
    {
        $this->post(route('register.store'), $this->validPayload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))->assertSessionHasErrors(['password' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_that_does_not_match_its_confirmation_is_rejected(): void
    {
        $this->post(route('register.store'), $this->validPayload(['password_confirmation' => 'something-else']))
            ->assertSessionHasErrors(['password' => 'كلمتا المرور غير متطابقتين.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_extra_fields_cannot_set_other_attributes(): void
    {
        $this->post(route('register.store'), $this->validPayload(['telegram_id' => '123456789']));

        $this->assertNull(User::query()->sole()->telegram_id);
    }
}
