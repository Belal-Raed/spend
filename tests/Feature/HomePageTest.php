<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_signed_in_user_sees_their_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'أبو محمد']))
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('أهلًا أبو محمد');
    }

    public function test_escapes_dangerous_content_in_the_user_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => "<script>alert('xss')</script>"]))
            ->get(route('home'))
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee("<script>alert('xss')</script>", escape: false);
    }
}
