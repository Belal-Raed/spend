<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Show the plans, the bank account to transfer to, the form for the
     * receipt, and the receipts the user sent before.
     *
     * Open to read-only accounts, since this is where they subscribe.
     */
    public function __invoke(Request $request): Response
    {
        $payments = $request->user()->payments()->with('plan:id,name')->latest('id')->get();

        return Inertia::render('subscription', [
            'plans' => PlanResource::collection(Plan::query()->offered()->get()),
            'bank' => array_filter(config('subscriptions.bank')),
            'payments' => PaymentResource::collection($payments),
            'hasPendingPayment' => $payments->contains(fn ($payment) => $payment->isPending()),
        ]);
    }
}
