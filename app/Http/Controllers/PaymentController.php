<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Keep the receipt of the user's bank transfer for the admin to review.
     *
     * Open to read-only accounts, since this is how they subscribe.
     */
    public function store(StorePaymentRequest $request, PaymentService $payments): RedirectResponse
    {
        $payments->submit($request->user(), $request->plan(), $request->file('receipt'));

        Inertia::flash('status', 'وصلنا الإشعار. سنراجعه ونفعّل اشتراكك قريبًا.');

        return redirect()->route('subscription');
    }
}
