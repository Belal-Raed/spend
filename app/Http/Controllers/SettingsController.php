<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show the user's account, with links to their categories and to sign out.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('settings', [
            'customCategoriesCount' => $request->user()->categories()->count(),
        ]);
    }
}
