<?php

namespace App\Http\Controllers;

use App\Services\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Take an update from Telegram and handle it after answering.
     *
     * Telegram signs every update with the secret set by `php artisan
     * telegram:set-webhook`; anything else is refused. The answer goes back at
     * once, since Telegram resends updates that wait too long.
     */
    public function __invoke(Request $request, TelegramBot $bot): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');

        abort_if($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        $update = $request->all();

        defer(fn () => $bot->handle($update));

        return response()->json(['ok' => true]);
    }
}
