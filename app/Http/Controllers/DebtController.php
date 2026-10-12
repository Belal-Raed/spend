<?php

namespace App\Http\Controllers;

use App\Enums\DebtDirection;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DebtController extends Controller
{
    /**
     * List the user's open debts in each direction, oldest first.
     */
    public function index(Request $request, ReportService $reports): View
    {
        $user = $request->user();

        return view('debts.index', [
            'debtsOwedByMe' => $reports->openDebts($user, DebtDirection::OwedByMe),
            'debtsOwedToMe' => $reports->openDebts($user, DebtDirection::OwedToMe),
        ]);
    }
}
