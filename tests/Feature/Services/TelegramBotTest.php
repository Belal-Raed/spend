<?php

namespace Tests\Feature\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TelegramBot;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    private const TELEGRAM_ID = '777000';

    private int $updateId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_username' => 'masarifi_bot', 'services.telegram.bot_token' => 'test-token']);
        $this->seed(DefaultCategoriesSeeder::class);
    }

    private function bot(): TelegramBot
    {
        return app(TelegramBot::class);
    }

    private function fakeTelegram(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    /**
     * @param  list<array<string, mixed>>  $groqAnswers
     */
    private function fakeTelegramAndGroq(array $groqAnswers): void
    {
        $sequence = Http::sequence();

        foreach ($groqAnswers as $answer) {
            $sequence->push($answer);
        }

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true]), 'api.groq.com/*' => $sequence]);
    }

    private function send(string $text, string $chatType = 'private'): void
    {
        $this->bot()->handle([
            'update_id' => ++$this->updateId,
            'message' => [
                'message_id' => $this->updateId,
                'from' => ['id' => (int) self::TELEGRAM_ID, 'first_name' => 'Abu'],
                'chat' => ['id' => (int) self::TELEGRAM_ID, 'type' => $chatType],
                'text' => $text,
            ],
        ]);
    }

    private function tap(string $data, string $telegramId = self::TELEGRAM_ID): void
    {
        $this->bot()->handle([
            'update_id' => ++$this->updateId,
            'callback_query' => [
                'id' => 'tap-'.$this->updateId,
                'from' => ['id' => (int) $telegramId],
                'message' => ['message_id' => 5, 'chat' => ['id' => (int) $telegramId]],
                'data' => $data,
            ],
        ]);
    }

    /**
     * The text of every message the bot sent.
     *
     * @return list<string>
     */
    private function sentTexts(): array
    {
        return Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage'))
            ->map(fn (array $pair): string => $pair[0]['text'])
            ->values()
            ->all();
    }

    private function linkedUser(): User
    {
        return User::factory()->create(['telegram_id' => self::TELEGRAM_ID]);
    }

    public function test_settings_link_ties_the_chat_to_the_user(): void
    {
        $this->fakeTelegram();
        $user = User::factory()->create(['name' => 'أبو محمد']);
        $code = str($this->bot()->linkUrl($user))->after('https://t.me/masarifi_bot?start=')->toString();

        $this->send("/start {$code}");

        $this->assertSame(self::TELEGRAM_ID, $user->fresh()->telegram_id);
        $this->assertStringStartsWith('تم ربط حسابك يا أبو محمد ✅', $this->sentTexts()[0]);
    }

    public function test_link_code_works_once(): void
    {
        $this->fakeTelegram();
        $user = User::factory()->create();
        $code = str($this->bot()->linkUrl($user))->afterLast('=')->toString();
        $this->send("/start {$code}");
        $user->forceFill(['telegram_id' => null])->save();

        $this->send("/start {$code}");

        $this->assertNull($user->fresh()->telegram_id);
        $this->assertStringStartsWith('انتهت صلاحية رابط الربط', $this->sentTexts()[1]);
    }

    public function test_linking_moves_the_telegram_account_from_another_user(): void
    {
        $this->fakeTelegram();
        $previous = $this->linkedUser();
        $user = User::factory()->create();

        $this->send('/start '.str($this->bot()->linkUrl($user))->afterLast('='));

        $this->assertNull($previous->fresh()->telegram_id);
        $this->assertSame(self::TELEGRAM_ID, $user->fresh()->telegram_id);
    }

    public function test_someone_without_an_account_gets_the_sign_up_link(): void
    {
        $this->fakeTelegram();

        $this->send('صرفت 50');

        $this->assertStringContainsString(route('register'), $this->sentTexts()[0]);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'groq'));
    }

    public function test_read_only_account_is_sent_to_subscribe_without_asking_groq(): void
    {
        $this->fakeTelegram();
        User::factory()->readOnly()->create(['telegram_id' => self::TELEGRAM_ID]);

        $this->send('صرفت 50 على أكل');

        $this->assertStringContainsString(route('subscription'), $this->sentTexts()[0]);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'groq'));
    }

    public function test_records_what_the_user_writes_and_offers_to_undo_it(): void
    {
        $user = $this->linkedUser();
        $this->fakeTelegramAndGroq([
            ['choices' => [['message' => ['role' => 'assistant', 'content' => null, 'tool_calls' => [
                ['id' => 'call_0', 'type' => 'function', 'function' => ['name' => 'record_transaction', 'arguments' => '{"type":"expense","amount":50,"category":"أكل"}']],
            ]]]]],
            ['choices' => [['message' => ['role' => 'assistant', 'content' => 'تم']]]],
        ]);

        $this->send('صرفت 50 على أكل');

        $expense = Transaction::query()->acrossTenants()->whereBelongsTo($user)->sole();
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && str_starts_with($request['text'], '✅ سجّلت مصروف 50.00 · أكل')
            && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === "undo:{$expense->id}");
    }

    public function test_undo_button_deletes_what_was_recorded(): void
    {
        $this->fakeTelegram();
        $user = $this->linkedUser();
        $expense = Transaction::factory()->for($user)->create();

        $this->tap("undo:{$expense->id}");

        $this->assertModelMissing($expense);
        $this->assertSame(['↩️ تراجعت، وما ضل شي من هالحركة.'], $this->sentTexts());
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/editMessageReplyMarkup'));
    }

    public function test_undo_button_cannot_delete_another_users_transaction(): void
    {
        $this->fakeTelegram();
        $this->linkedUser();
        $strangers = Transaction::factory()->create();

        $this->tap("undo:{$strangers->id}");

        $this->assertModelExists($strangers);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery') && $request['text'] === 'الحركة محذوفة من قبل.');
    }

    public function test_stops_answering_after_the_hourly_limit(): void
    {
        config(['services.telegram.messages_per_hour' => 1]);
        $this->linkedUser();
        $this->fakeTelegramAndGroq([['choices' => [['message' => ['role' => 'assistant', 'content' => 'أهلين']]]]]);

        $this->send('مرحبا');
        $this->send('مرحبا كمان');

        $this->assertSame(['أهلين', 'وصلت للحد المسموح من الرسائل هذه الساعة. جرّب بعد شوي.'], $this->sentTexts());
    }

    public function test_tells_the_user_to_try_later_when_groq_is_busy(): void
    {
        $this->linkedUser();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true]), 'api.groq.com/*' => Http::response([], 429)]);

        $this->send('صرفت 50');

        $this->assertSame(['ما قدرت أفهم رسالتك هلق، الخدمة مشغولة. جرّب بعد دقيقة.'], $this->sentTexts());
    }

    public function test_handles_an_update_telegram_sends_twice_only_once(): void
    {
        $this->fakeTelegram();
        $update = ['update_id' => 42, 'message' => ['from' => ['id' => 1], 'chat' => ['id' => 1, 'type' => 'private'], 'text' => 'مرحبا']];

        $this->bot()->handle($update);
        $this->bot()->handle($update);

        $this->assertCount(1, $this->sentTexts());
    }

    public function test_ignores_group_chats(): void
    {
        $this->fakeTelegram();
        $this->linkedUser();

        $this->send('صرفت 50', chatType: 'group');

        Http::assertNothingSent();
    }
}
