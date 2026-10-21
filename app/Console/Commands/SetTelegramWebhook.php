<?php

namespace App\Console\Commands;

use App\Services\TelegramClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:set-webhook')]
#[Description('Have Telegram send the bot\'s messages to this app')]
class SetTelegramWebhook extends Command
{
    /**
     * Execute the console command.
     *
     * Run it once after deploying, and again whenever APP_URL or the secret changes.
     */
    public function handle(TelegramClient $telegram): int
    {
        $secret = (string) config('services.telegram.webhook_secret');

        if (blank(config('services.telegram.bot_token')) || $secret === '') {
            $this->error('Set TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET first.');

            return self::FAILURE;
        }

        $url = route('telegram.webhook');

        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram only sends to an HTTPS address, and APP_URL gives {$url}.");

            return self::FAILURE;
        }

        $telegram->setWebhook($url, $secret);

        $this->info("Telegram now sends the bot's messages to {$url}.");

        return self::SUCCESS;
    }
}
