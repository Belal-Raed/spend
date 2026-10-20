<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Carbon::setTestNow('2026-10-20 09:00');
    }

    private function service(): PaymentService
    {
        return app(PaymentService::class);
    }

    private function receipt(): UploadedFile
    {
        return UploadedFile::fake()->image('receipt.jpg');
    }

    /**
     * A pending payment for a plan of the given length, by a user whose access ends at the given time.
     */
    private function pendingPayment(int $months, ?string $accessEndsAt): Payment
    {
        $user = User::factory()->create(['trial_ends_at' => $accessEndsAt]);

        return Payment::factory()->for($user)->for(Plan::factory()->create(['months' => $months]))->create();
    }

    public function test_submitting_keeps_the_receipt_and_the_plans_current_price(): void
    {
        $user = $this->signIn();
        $plan = Plan::factory()->create(['price' => '45.00']);

        $payment = $this->service()->submit($user, $plan, $this->receipt());

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('45.00', $payment->amount);
        $this->assertStringStartsWith("receipts/{$user->id}/", $payment->receipt_path);
        Storage::disk('local')->assertExists($payment->receipt_path);
    }

    public function test_submitting_for_a_plan_without_a_price_is_refused(): void
    {
        $user = $this->signIn();

        $this->expectException(ValidationException::class);

        $this->service()->submit($user, Plan::factory()->unpriced()->create(), $this->receipt());
    }

    public function test_submitting_while_a_receipt_is_waiting_is_refused(): void
    {
        $user = $this->signIn();
        Payment::factory()->for($user)->create();

        try {
            $this->service()->submit($user, Plan::factory()->create(), $this->receipt());
            $this->fail('Expected a second pending receipt to be refused.');
        } catch (ValidationException) {
            $this->assertSame(1, $user->payments()->count());
        }
    }

    public function test_approving_after_access_ended_subscribes_from_now_for_the_plans_months(): void
    {
        $payment = $this->pendingPayment(months: 3, accessEndsAt: '2026-10-01 09:00');

        $this->service()->approve($payment);

        $this->assertSame(PaymentStatus::Approved, $payment->fresh()->status);
        $this->assertSame('2026-10-20 09:00:00', $payment->fresh()->reviewed_at->toDateTimeString());
        $this->assertSame('2027-01-20 09:00:00', $payment->user->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_approving_during_the_trial_starts_the_subscription_when_the_trial_ends(): void
    {
        $payment = $this->pendingPayment(months: 1, accessEndsAt: '2026-10-25 09:00');

        $this->service()->approve($payment);

        $this->assertSame('2026-11-25 09:00:00', $payment->user->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_approving_a_renewal_adds_to_the_running_subscription(): void
    {
        $user = User::factory()->subscribedUntil(Carbon::parse('2026-11-05 09:00'))->create();
        $payment = Payment::factory()->for($user)->for(Plan::factory()->create(['months' => 1]))->create();

        $this->service()->approve($payment);

        $this->assertSame('2026-12-05 09:00:00', $user->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_admin_signed_in_as_another_user_can_approve(): void
    {
        $payment = $this->pendingPayment(months: 1, accessEndsAt: null);
        $this->signIn();

        $this->service()->approve($payment);

        $this->assertSame(PaymentStatus::Approved, $payment->user->payments()->acrossTenants()->sole()->status);
        $this->assertTrue($payment->user->fresh()->hasFullAccess());
    }

    public function test_rejecting_keeps_the_reason_and_leaves_access_unchanged(): void
    {
        $payment = $this->pendingPayment(months: 1, accessEndsAt: '2026-10-01 09:00');

        $this->service()->reject($payment, 'المبلغ ناقص.');

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame('المبلغ ناقص.', $payment->fresh()->rejection_reason);
        $this->assertNull($payment->user->fresh()->subscription_ends_at);
    }

    public function test_a_payment_is_reviewed_only_once(): void
    {
        $user = User::factory()->subscribedUntil(Carbon::parse('2026-11-20 09:00'))->create();
        $payment = Payment::factory()->for($user)->approved()->create();

        try {
            $this->service()->approve($payment);
            $this->fail('Expected approving a reviewed payment to be refused.');
        } catch (ValidationException) {
            $this->assertSame('2026-11-20 09:00:00', $user->fresh()->subscription_ends_at->toDateTimeString());
        }
    }
}
