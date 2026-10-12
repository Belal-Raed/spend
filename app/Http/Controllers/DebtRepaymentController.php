<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDebtRepaymentRequest;
use App\Models\Transaction;
use App\Services\Money;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DebtRepaymentController extends Controller
{
    /**
     * Show the form for a payment against a debt, filled in with what is left on it.
     */
    public function create(Transaction $debt): View|RedirectResponse
    {
        Gate::authorize('repay', $debt);

        if ($debt->isSettled()) {
            return redirect()->route('debts.index')->withErrors(['amount' => 'هذا الدين انسدّ من قبل.']);
        }

        return view('debts.repayments.create', [
            'debt' => $debt,
            'remaining' => Money::toDecimal($debt->remainingInCents()),
        ]);
    }

    /**
     * Record a payment against a debt. Without an amount, everything left is paid.
     */
    public function store(StoreDebtRepaymentRequest $request, Transaction $debt, TransactionService $transactions): RedirectResponse
    {
        $transactions->repayDebt($debt, $request->validated());

        return redirect()->route('debts.index')->with(
            'status',
            $debt->refresh()->isSettled() ? 'انسدّ الدين.' : 'تم تسجيل الدفعة.',
        );
    }
}
