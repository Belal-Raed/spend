<?php

namespace App\Http\Controllers;

use App\Enums\DebtDirection;
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
            return $this->backToDebts($debt)->withErrors(['amount' => 'هذا الدين انسدّ من قبل.']);
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

        return $this->backToDebts($debt)->with(
            'status',
            $debt->refresh()->isSettled() ? 'انسدّ الدين.' : 'تم تسجيل الدفعة.',
        );
    }

    /**
     * Send the user back to the debts screen, on the tab the debt is listed under.
     */
    private function backToDebts(Transaction $debt): RedirectResponse
    {
        $redirect = redirect()->route('debts.index');

        return $debt->debt_direction === DebtDirection::OwedToMe ? $redirect->withFragment('owed-to-me') : $redirect;
    }
}
