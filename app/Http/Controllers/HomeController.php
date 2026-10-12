<?php

namespace App\Http\Controllers;

use App\Enums\DebtDirection;
use App\Enums\Period;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the user's balance, this month's spending and their open debts.
     */
    public function __invoke(Request $request, ReportService $reports): View
    {
        $user = $request->user();

        return view('home', [
            'balance' => $reports->balance($user),
            'monthExpensesTotal' => $reports->summaryForPeriod($user, Period::ThisMonth)['expenses_total'],
            'debtsOwedByMe' => $reports->openDebts($user, DebtDirection::OwedByMe),
            'debtsOwedToMe' => $reports->openDebts($user, DebtDirection::OwedToMe),
        ]);
    }
}
