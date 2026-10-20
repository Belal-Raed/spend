<?php

namespace App\Http\Controllers;

use App\Enums\DebtDirection;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    /**
     * List the user's open debts in each direction, oldest first.
     */
    public function index(Request $request, ReportService $reports): Response
    {
        $user = $request->user();

        return Inertia::render('debts/index', [
            'debtsOwedByMe' => $reports->openDebts($user, DebtDirection::OwedByMe),
            'debtsOwedToMe' => $reports->openDebts($user, DebtDirection::OwedToMe),
        ]);
    }
}
