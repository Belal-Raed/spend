<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('payments.store'))->assertRedirect(route('login'));
    }

    public function test_read_only_user_can_send_a_receipt_for_review(): void
    {
        $user = User::factory()->readOnly()->create();
        $plan = Plan::factory()->create(['price' => '20.00']);

        $this->actingAs($user)
            ->post(route('payments.store'), ['plan_id' => $plan->id, 'receipt' => UploadedFile::fake()->image('receipt.png')])
            ->assertRedirect(route('subscription'))
            ->assertInertiaFlash('status', 'وصلنا الإشعار. سنراجعه ونفعّل اشتراكك قريبًا.');

        $payment = $user->payments()->sole();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertTrue($plan->is($payment->plan));
        Storage::disk('local')->assertExists($payment->receipt_path);
    }

    public function test_accepts_a_pdf_receipt(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('payments.store'), [
                'plan_id' => Plan::factory()->create()->id,
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unavailablePlans(): array
    {
        return [
            'without a price' => ['unpriced'],
            'no longer offered' => ['inactive'],
        ];
    }

    #[DataProvider('unavailablePlans')]
    public function test_rejects_a_plan_that_cannot_be_bought(string $state): void
    {
        $plan = Plan::factory()->{$state}()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('payments.store'), ['plan_id' => $plan->id, 'receipt' => UploadedFile::fake()->image('receipt.jpg')])
            ->assertSessionHasErrors(['plan_id' => 'اختر باقة من القائمة.']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_requires_a_receipt(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('payments.store'), ['plan_id' => Plan::factory()->create()->id])
            ->assertSessionHasErrors(['receipt' => 'أرفق صورة إشعار التحويل.']);
    }

    public function test_rejects_a_receipt_that_is_not_an_image_or_a_pdf(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('payments.store'), [
                'plan_id' => Plan::factory()->create()->id,
                'receipt' => UploadedFile::fake()->create('receipt.html', 10, 'text/html'),
            ])
            ->assertSessionHasErrors(['receipt' => 'الإشعار يجب أن يكون صورة أو ملف PDF.']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_rejects_a_receipt_over_five_megabytes(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('payments.store'), [
                'plan_id' => Plan::factory()->create()->id,
                'receipt' => UploadedFile::fake()->image('receipt.jpg')->size(5121),
            ])
            ->assertSessionHasErrors(['receipt' => 'حجم الإشعار يجب ألا يزيد عن 5 ميجابايت.']);
    }

    public function test_rejects_a_second_receipt_while_one_is_waiting(): void
    {
        $user = User::factory()->create();
        Payment::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('payments.store'), ['plan_id' => Plan::factory()->create()->id, 'receipt' => UploadedFile::fake()->image('receipt.jpg')])
            ->assertSessionHasErrors(['receipt' => 'عندك إشعار بانتظار المراجعة. انتظر الرد قبل إرسال إشعار جديد.']);

        $this->assertSame(1, $user->payments()->count());
    }

    public function test_another_users_waiting_receipt_does_not_block_the_user(): void
    {
        Payment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('payments.store'), ['plan_id' => Plan::factory()->create()->id, 'receipt' => UploadedFile::fake()->image('receipt.jpg')])
            ->assertSessionHasNoErrors();
    }
}
