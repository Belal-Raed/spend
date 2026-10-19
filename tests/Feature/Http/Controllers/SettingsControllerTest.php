<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs(User::factory()->create(['name' => 'أبو محمد', 'phone' => '0599123456']))
            ->get(route('settings'))
            ->assertOk()
            ->assertViewIs('settings')
            ->assertSeeTextInOrder(['أبو محمد', '0599123456']);
    }

    public function test_escapes_dangerous_content_in_the_user_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => "<script>alert('xss')</script>"]))
            ->get(route('settings'))
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee("<script>alert('xss')</script>", escape: false);
    }

    public function test_links_to_categories_and_to_signing_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings'))
            ->assertSee(route('categories.index'), escape: false)
            ->assertSee('action="'.route('logout').'"', escape: false);
    }

    public function test_counts_only_the_users_own_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(2)->ownedBy($user)->create();
        Category::factory()->create();

        $response = $this->actingAs($user)->get(route('settings'));

        $this->assertSame(2, $response->viewData('customCategoriesCount'));
    }
}
