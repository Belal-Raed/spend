<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('settings'))->assertRedirect(route('login'));
    }

    public function test_shows_the_users_name_and_phone(): void
    {
        $user = User::factory()->create(['name' => 'أبو محمد', 'phone' => '0599123456']);

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings')
                ->where('auth.user.name', 'أبو محمد')
                ->where('auth.user.phone', '0599123456'));
    }

    public function test_never_sends_the_page_a_raw_script_tag_from_the_user_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => "<script>alert('xss')</script>"]))
            ->get(route('settings'))
            ->assertDontSee('<script>alert', escape: false);
    }

    public function test_counts_only_the_users_own_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(2)->ownedBy($user)->create();
        Category::factory()->create();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertInertia(fn (Assert $page) => $page->where('customCategoriesCount', 2));
    }
}
