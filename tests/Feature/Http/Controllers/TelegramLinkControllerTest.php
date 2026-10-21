<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TelegramLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_username' => 'masarifi_bot']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('telegram.link'))->assertRedirect(route('login'));
    }

    public function test_opens_the_bot_with_a_code_for_this_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('telegram.link'));

        $url = $response->headers->get('Location');
        $this->assertStringStartsWith('https://t.me/masarifi_bot?start=', $url);
        $this->assertSame($user->id, Cache::get('telegram:link:'.str($url)->afterLast('=')));
    }

    public function test_is_not_found_until_the_bot_is_set_up(): void
    {
        config(['services.telegram.bot_username' => null]);

        $this->actingAs(User::factory()->create())
            ->post(route('telegram.link'))
            ->assertNotFound();
    }

    public function test_unlinks_the_telegram_account(): void
    {
        $user = User::factory()->create(['telegram_id' => '777000']);

        $this->actingAs($user)
            ->delete(route('telegram.unlink'))
            ->assertRedirect(route('settings'))
            ->assertInertiaFlash('status', 'تم فك ربط تيليجرام.');

        $this->assertNull($user->fresh()->telegram_id);
    }

    public function test_settings_offer_to_link_once_the_bot_is_set_up(): void
    {
        $this->actingAs(User::factory()->create(['telegram_id' => '777000']))
            ->get(route('settings'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('telegram', ['isAvailable' => true, 'isLinked' => true]));
    }

    public function test_settings_hide_the_bot_until_it_is_set_up(): void
    {
        config(['services.telegram.bot_username' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('settings'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('telegram', ['isAvailable' => false, 'isLinked' => false]));
    }
}
