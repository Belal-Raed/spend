<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SetTelegramWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.webhook_secret' => 'webhook-secret']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    public function test_points_telegram_at_the_webhook_with_the_secret(): void
    {
        URL::forceRootUrl('https://masarifi.test');
        URL::forceScheme('https');

        $this->artisan('telegram:set-webhook')->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bottest-token/setWebhook'
            && $request['url'] === 'https://masarifi.test/telegram/webhook'
            && $request['secret_token'] === 'webhook-secret');
    }

    public function test_refuses_an_address_without_https(): void
    {
        URL::forceRootUrl('http://localhost:8000');

        $this->artisan('telegram:set-webhook')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_needs_the_token_and_the_secret(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $this->artisan('telegram:set-webhook')
            ->expectsOutput('Set TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET first.')
            ->assertFailed();
    }
}
