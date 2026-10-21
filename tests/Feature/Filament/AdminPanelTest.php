<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_admin_sign_in(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_user_who_is_not_an_admin_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_sees_the_dashboard_in_arabic(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'أبو محمد']))
            ->get('/admin')
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee('مصاريفي · الإدارة');
    }

    public function test_avatars_are_drawn_in_the_app_without_sending_names_elsewhere(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'أبو محمد']))
            ->get('/admin')
            ->assertSee('data:image/svg+xml;base64,', escape: false)
            ->assertDontSee('ui-avatars.com', escape: false);
    }

    public function test_admin_signs_in_with_their_phone_number(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0599123456']);

        Livewire::test(Login::class)
            ->fillForm(['phone' => '0599123456', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_user_who_is_not_an_admin_cannot_sign_in_to_the_panel(): void
    {
        User::factory()->create(['phone' => '0599123456']);

        Livewire::test(Login::class)
            ->fillForm(['phone' => '0599123456', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['phone']);

        $this->assertGuest();
    }
}
