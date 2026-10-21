<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Answers what users send the Telegram bot.
 *
 * A user links their Telegram account from the app's settings, which opens
 * the bot with a one-time code. Linked users with full access write to the
 * bot in everyday Arabic and ExpenseAgent does the rest; what it records
 * comes back with a button to undo it.
 */
class TelegramBot
{
    /**
     * How long the code in the settings link works.
     */
    private const LINK_MINUTES = 10;

    public function __construct(
        private TelegramClient $telegram,
        private ExpenseAgent $agent,
        private TransactionService $transactions,
        private CurrentTenant $tenant,
    ) {}

    /**
     * The link that opens the bot and ties the chat to the user, usable once.
     */
    public function linkUrl(User $user): string
    {
        $code = Str::random(32);

        Cache::put("telegram:link:{$code}", $user->id, now()->addMinutes(self::LINK_MINUTES));

        return 'https://t.me/'.config('services.telegram.bot_username')."?start={$code}";
    }

    /**
     * Handle one update Telegram sent. Each update is handled once, even if Telegram sends it again.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        if (isset($update['update_id']) && ! Cache::add("telegram:update:{$update['update_id']}", true, now()->addDay())) {
            return;
        }

        if (isset($update['callback_query'])) {
            $this->handleButton($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;

        // The bot keeps people's money private, so it only talks in one-to-one chats.
        if ($message === null || ($message['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $this->handleMessage($message['chat']['id'], (string) $message['from']['id'], trim((string) ($message['text'] ?? '')));
    }

    private function handleMessage(int|string $chatId, string $telegramId, string $text): void
    {
        if (preg_match('/^\/start\s+(\S+)$/', $text, $matches)) {
            $this->link($chatId, $telegramId, $matches[1]);

            return;
        }

        $user = User::query()->where('telegram_id', $telegramId)->first();

        if ($user === null) {
            $this->telegram->sendMessage($chatId, implode("\n", [
                'أهلًا بك في مصاريفي 👋',
                'لتستخدم البوت أنشئ حسابًا من هنا:',
                route('register'),
                'وبعدها افتح الإعدادات في التطبيق واضغط «ربط تيليجرام».',
            ]));

            return;
        }

        if ($text === '/start' || $text === '/help') {
            $this->telegram->sendMessage($chatId, $this->examples($user));

            return;
        }

        if ($text === '' || str_starts_with($text, '/')) {
            $this->telegram->sendMessage($chatId, 'اكتب لي رسالة عادية، مثل: «صرفت 50 على أكل».');

            return;
        }

        if (! $user->hasFullAccess()) {
            $this->telegram->sendMessage($chatId, "انتهت فترة {$this->endedPeriod($user)}، فالبوت متوقف لحسابك. اشترك من هنا:\n".route('subscription'));

            return;
        }

        if (! RateLimiter::attempt("telegram:{$user->id}", config('services.telegram.messages_per_hour'), fn (): bool => true, 3600)) {
            $this->telegram->sendMessage($chatId, 'وصلت للحد المسموح من الرسائل هذه الساعة. جرّب بعد شوي.');

            return;
        }

        $this->answer($chatId, $user, $text);
    }

    private function answer(int|string $chatId, User $user, string $text): void
    {
        try {
            $reply = $this->tenant->runAs($user, fn (): array => $this->agent->reply($user, $text));
        } catch (RequestException $exception) {
            if ($exception->response->status() !== 429) {
                report($exception);
            }

            $this->telegram->sendMessage($chatId, 'ما قدرت أفهم رسالتك هلق، الخدمة مشغولة. جرّب بعد دقيقة.');

            return;
        } catch (ConnectionException $exception) {
            report($exception);
            $this->telegram->sendMessage($chatId, 'ما قدرت أفهم رسالتك هلق. جرّب بعد دقيقة.');

            return;
        }

        $recordedIds = array_map(fn (Transaction $transaction): int => $transaction->id, $reply['recorded']);

        $this->telegram->sendMessage($chatId, $reply['text'], $recordedIds === [] ? null : [[
            ['text' => '↩️ تراجع', 'callback_data' => 'undo:'.implode(',', $recordedIds)],
        ]]);
    }

    /**
     * Tie the Telegram account to the user whose settings link carried the code.
     *
     * A Telegram account belongs to one user at a time, so it moves if another had it.
     */
    private function link(int|string $chatId, string $telegramId, string $code): void
    {
        $user = User::query()->find(Cache::pull("telegram:link:{$code}"));

        if ($user === null) {
            $this->telegram->sendMessage($chatId, 'انتهت صلاحية رابط الربط. افتح الإعدادات في التطبيق واضغط «ربط تيليجرام» مرة ثانية.');

            return;
        }

        DB::transaction(function () use ($user, $telegramId): void {
            User::query()->where('telegram_id', $telegramId)->whereKeyNot($user->id)->update(['telegram_id' => null]);

            $user->telegram_id = $telegramId;
            $user->save();
        });

        $this->telegram->sendMessage($chatId, "تم ربط حسابك يا {$user->name} ✅\n\n".$this->examples($user));
    }

    /**
     * Undo what a reply recorded, when the user taps its button.
     *
     * @param  array<string, mixed>  $callback
     */
    private function handleButton(array $callback): void
    {
        $user = User::query()->where('telegram_id', (string) $callback['from']['id'])->first();

        if ($user === null || ! preg_match('/^undo:([\d,]+)$/', (string) ($callback['data'] ?? ''), $matches)) {
            $this->telegram->answerCallbackQuery($callback['id']);

            return;
        }

        if (! $user->hasFullAccess()) {
            $this->telegram->answerCallbackQuery($callback['id'], 'حسابك للقراءة فقط.');

            return;
        }

        $undone = $this->tenant->runAs($user, function () use ($matches): int {
            $transactions = Transaction::query()->whereKey(explode(',', $matches[1]))->get();
            $transactions->each(fn (Transaction $transaction) => $this->transactions->delete($transaction));

            return $transactions->count();
        });

        $this->telegram->answerCallbackQuery($callback['id'], $undone > 0 ? 'تم التراجع.' : 'الحركة محذوفة من قبل.');

        if (isset($callback['message'])) {
            $this->telegram->removeButtons($callback['message']['chat']['id'], $callback['message']['message_id']);

            if ($undone > 0) {
                $this->telegram->sendMessage($callback['message']['chat']['id'], '↩️ تراجعت، وما ضل شي من هالحركة.');
            }
        }
    }

    private function examples(User $user): string
    {
        return implode("\n", [
            "اكتب لي بكلامك العادي يا {$user->name}، مثل:",
            '• صرفت 50 على أكل',
            '• قبضت الراتب 3000',
            '• تدينت 200 من خالد',
            '• خالد رجّعلي 100',
            '• قديش صرفت هالشهر؟',
            '• شو الديون اللي عليّ؟',
        ]);
    }

    private function endedPeriod(User $user): string
    {
        return $user->subscription_ends_at === null ? 'التجربة' : 'اشتراكك';
    }
}
