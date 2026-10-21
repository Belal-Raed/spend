<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\PaymentSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Takes bank transfer receipts from users and lets the admin approve or reject them.
 *
 * A new receipt notifies every admin. Approving extends the user's
 * subscription by the plan's length, starting when their current trial or
 * subscription ends, so paying early loses nothing.
 */
class PaymentService
{
    public function __construct(private CurrentTenant $tenant) {}

    /**
     * Keep the receipt of a transfer the user made for a plan, waiting for the admin's review.
     *
     * @throws ValidationException When the plan cannot be bought or a receipt is already waiting.
     */
    public function submit(User $user, Plan $plan, UploadedFile $receipt): Payment
    {
        if (! $plan->isPurchasable()) {
            throw ValidationException::withMessages(['plan_id' => 'This plan cannot be bought now.']);
        }

        $payment = DB::transaction(function () use ($user, $plan, $receipt): Payment {
            User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->payments()->pending()->exists()) {
                throw ValidationException::withMessages(['receipt' => 'A receipt is already waiting for review.']);
            }

            return $user->payments()->create([
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'receipt_path' => $receipt->store("receipts/{$user->id}", 'local'),
                'status' => PaymentStatus::Pending,
            ]);
        });

        Notification::send(User::query()->admins()->get(), new PaymentSubmitted($payment));

        return $payment;
    }

    /**
     * Give the user extra days of full access, such as a gift or a make-good,
     * starting when their current trial or subscription ends.
     */
    public function grantDays(User $user, int $days): void
    {
        DB::transaction(function () use ($user, $days): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            $user->subscription_ends_at = $user->nextSubscriptionStart()->addDays($days);
            $user->save();
        });
    }

    /**
     * Accept the payment and extend the user's subscription by its plan's length.
     *
     * @throws ValidationException When the payment was already reviewed.
     */
    public function approve(Payment $payment): Payment
    {
        return $this->review($payment, function (Payment $payment, User $user): void {
            $user->subscription_ends_at = $user->nextSubscriptionStart()->addMonthsNoOverflow($payment->plan->months);
            $user->save();

            $payment->update(['status' => PaymentStatus::Approved, 'reviewed_at' => now()]);
        });
    }

    /**
     * Turn the payment down, telling the user why. Their access does not change.
     *
     * @throws ValidationException When the payment was already reviewed.
     */
    public function reject(Payment $payment, string $reason): Payment
    {
        return $this->review($payment, function (Payment $payment) use ($reason): void {
            $payment->update([
                'status' => PaymentStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_at' => now(),
            ]);
        });
    }

    /**
     * Run a review on the locked payment and user, as the payment's owner, so
     * the admin reviewing it can save the owner's records.
     *
     * @param  callable(Payment, User): void  $decide
     */
    private function review(Payment $payment, callable $decide): Payment
    {
        return $this->tenant->runAs($payment->user, fn (): Payment => DB::transaction(function () use ($payment, $decide): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $user = User::query()->lockForUpdate()->findOrFail($payment->user_id);

            if (! $payment->isPending()) {
                throw ValidationException::withMessages(['payment' => 'This payment was already reviewed.']);
            }

            $decide($payment, $user);

            return $payment;
        }));
    }
}
