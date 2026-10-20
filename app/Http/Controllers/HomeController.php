<?php

namespace App\Http\Controllers;

use App\Enums\DebtDirection;
use App\Enums\Period;
use App\Http\Resources\TransactionResource;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    private const RECENT_TRANSACTIONS = 5;

    /**
     * Show the user's balance, this month's spending, their open debts and their latest transactions.
     */
    public function __invoke(Request $request, ReportService $reports): Response
    {
        $user = $request->user();

        return Inertia::render('home', [
            'balance' => $reports->balance($user),
            'monthExpensesTotal' => $reports->summaryForPeriod($user, Period::ThisMonth)['expenses_total'],
            'debtsOwedByMe' => $reports->openDebts($user, DebtDirection::OwedByMe),
            'debtsOwedToMe' => $reports->openDebts($user, DebtDirection::OwedToMe),
            'recentTransactions' => TransactionResource::collection(
                $user->transactions()
                    ->with('category:id,name')
                    ->orderByDesc('occurred_on')
                    ->orderByDesc('id')
                    ->limit(self::RECENT_TRANSACTIONS)
                    ->get(),
            ),
        ]);
    }
}
