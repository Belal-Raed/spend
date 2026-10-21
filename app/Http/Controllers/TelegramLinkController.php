<?php

namespace App\Http\Controllers;

use App\Services\TelegramBot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class TelegramLinkController extends Controller
{
    /**
     * Open the bot in Telegram with a one-time code that links the chat to this user.
     */
    public function store(Request $request, TelegramBot $bot): Response
    {
        abort_if(blank(config('services.telegram.bot_username')), 404);

        return Inertia::location($bot->linkUrl($request->user()));
    }

    /**
     * Unlink the user's Telegram account, so the bot no longer answers it.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['telegram_id' => null])->save();

        Inertia::flash('status', 'تم فك ربط تيليجرام.');

        return redirect()->route('settings');
    }
}
