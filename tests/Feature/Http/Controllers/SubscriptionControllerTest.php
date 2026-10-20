<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('subscription'))->assertRedirect(route('login'));
    }

    public function test_read_only_user_can_open_the_page(): void
    {
        $this->actingAs(User::factory()->readOnly()->create())
            ->get(route('subscription'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('subscription'));
    }

    public function test_offers_the_active_plans_shortest_first_and_marks_unpriced_ones(): void
    {
        $quarterly = Plan::factory()->unpriced()->create(['name' => '3 شهور', 'months' => 3]);
        $monthly = Plan::factory()->create(['name' => 'شهري', 'months' => 1, 'price' => '20.00']);
        Plan::factory()->inactive()->create(['months' => 12]);

        $this->actingAs(User::factory()->create())
            ->get(route('subscription'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('plans', 2)
                ->where('plans.0', ['id' => $monthly->id, 'name' => 'شهري', 'months' => 1, 'price' => '20.00', 'is_purchasable' => true])
                ->where('plans.1', ['id' => $quarterly->id, 'name' => '3 شهور', 'months' => 3, 'price' => null, 'is_purchasable' => false]));
    }

    public function test_shows_only_the_bank_details_that_are_set(): void
    {
        config([
            'subscriptions.bank.name' => 'بنك فلسطين',
            'subscriptions.bank.account_name' => null,
            'subscriptions.bank.account_number' => null,
            'subscriptions.bank.iban' => 'PS92PALS000000000400123456702',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('subscription'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('bank', ['name' => 'بنك فلسطين', 'iban' => 'PS92PALS000000000400123456702']));
    }

    public function test_lists_the_users_own_receipts_newest_first(): void
    {
        Payment::factory()->create();
        $user = User::factory()->create();
        $rejected = Payment::factory()->for($user)->rejected('المبلغ ناقص.')->create();
        $pending = Payment::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('subscription'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('hasPendingPayment', true)
                ->has('payments', 2)
                ->where('payments.0.id', $pending->id)
                ->where('payments.0.status_label', 'بانتظار المراجعة')
                ->where('payments.1.id', $rejected->id)
                ->where('payments.1.rejection_reason', 'المبلغ ناقص.'));
    }

    public function test_has_no_pending_receipt_once_the_last_one_was_reviewed(): void
    {
        $user = User::factory()->create();
        Payment::factory()->for($user)->approved()->create();

        $this->actingAs($user)
            ->get(route('subscription'))
            ->assertInertia(fn (Assert $page) => $page->where('hasPendingPayment', false));
    }
}
