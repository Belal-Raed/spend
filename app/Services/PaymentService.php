<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Takes bank transfer receipts from users and lets the admin approve or reject them.
 *
 * Approving extends the user's subscription by the plan's length, starting
 * when their current trial or subscription ends, so paying early loses nothing.
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

        return DB::transaction(function () use ($user, $plan, $receipt): Payment {
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
    }

    /**
     * Accept the payment and extend the user's subscription by its plan's length.
     *
     * @throws ValidationException When the payment was already reviewed.
     */
    public function approve(Payment $payment): Payment
    {
        return $this->review($payment, function (Payment $payment, User $user): void {
            $accessEndsAt = $user->accessEndsAt();
            $startsAt = $accessEndsAt?->isFuture() ? $accessEndsAt : now();

            $user->subscription_ends_at = $startsAt->copy()->addMonthsNoOverflow($payment->plan->months);
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
