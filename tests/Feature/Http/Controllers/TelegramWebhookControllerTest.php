<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'webhook-secret';

    /**
     * @return array<string, mixed>
     */
    private function update(): array
    {
        return [
            'update_id' => 1,
            'message' => ['message_id' => 1, 'from' => ['id' => 777000], 'chat' => ['id' => 777000, 'type' => 'private'], 'text' => 'مرحبا'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.webhook_secret' => self::SECRET, 'services.telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    public function test_answers_telegram_and_handles_the_update(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update(), ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET])
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && $request['chat_id'] === 777000);
    }

    public function test_refuses_an_update_with_the_wrong_secret(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update(), ['X-Telegram-Bot-Api-Secret-Token' => 'guess'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_refuses_an_update_without_a_secret(): void
    {
        $this->postJson(route('telegram.webhook'), $this->update())->assertForbidden();
    }

    public function test_refuses_every_update_until_a_secret_is_configured(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $this->postJson(route('telegram.webhook'), $this->update(), ['X-Telegram-Bot-Api-Secret-Token' => ''])
            ->assertForbidden();
    }
}
