<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Talks to the Telegram Bot API for the app's bot.
 *
 * @see https://core.telegram.org/bots/api
 */
class TelegramClient
{
    /**
     * Send a text message, with buttons under it when given.
     *
     * @param  list<list<array{text: string, callback_data?: string, url?: string}>>|null  $buttons  Rows of inline buttons.
     */
    public function sendMessage(int|string $chatId, string $text, ?array $buttons = null): Response
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $buttons === null ? null : ['inline_keyboard' => $buttons],
            'link_preview_options' => ['is_disabled' => true],
        ], fn ($value) => $value !== null));
    }

    /**
     * Take the buttons off a message the bot sent, once they have been used.
     */
    public function removeButtons(int|string $chatId, int $messageId): Response
    {
        return $this->call('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => []],
        ]);
    }

    /**
     * Tell Telegram a button tap was handled, so the button stops spinning.
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): Response
    {
        return $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]));
    }

    /**
     * Have Telegram send the bot's updates to the given URL, signed with the secret.
     */
    public function setWebhook(string $url, string $secret): Response
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function call(string $method, array $parameters): Response
    {
        return $this->request()->post($method, $parameters)->throw();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl('https://api.telegram.org/bot'.config('services.telegram.bot_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->retry(2, 500, throw: false);
    }
}
