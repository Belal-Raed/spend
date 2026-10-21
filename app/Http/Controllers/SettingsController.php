<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show the user's account, with links to their categories, the Telegram bot and to sign out.
     *
     * The bot row shows only once the bot is set up on the server.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('settings', [
            'customCategoriesCount' => $request->user()->categories()->count(),
            'telegram' => [
                'isAvailable' => filled(config('services.telegram.bot_username')),
                'isLinked' => $request->user()->telegram_id !== null,
            ],
        ]);
    }
}
