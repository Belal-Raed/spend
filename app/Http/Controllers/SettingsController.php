<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show the user's account, with links to their categories and to sign out.
     */
    public function __invoke(Request $request): View
    {
        return view('settings', [
            'user' => $request->user(),
            'customCategoriesCount' => $request->user()->categories()->count(),
        ]);
    }
}
