<?php

namespace App\Services;

use App\Enums\DebtDirection;
use App\Enums\Period;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Turns what a user writes to the bot, in everyday Arabic, into transactions and answers.
 *
 * The model decides which tool to call; the tools run through the same
 * services as the web app, so the same rules apply. Replies about records
 * that changed are written here from the saved records, never by the model,
 * so the amounts the user reads are the amounts that were saved.
 *
 * Run it with the user as the current tenant.
 */
class ExpenseAgent
{
    /**
     * How many rounds of tool calls the model gets before it must answer.
     */
    private const MAX_ROUNDS = 5;

    /**
     * How many earlier messages the model sees, and for how long, so a short
     * answer such as an amount it asked for reads as part of the same request.
     */
    private const HISTORY_MESSAGES = 6;

    private const HISTORY_MINUTES = 10;

    /**
     * What changed during the current reply, as lines for the user.
     *
     * @var list<string>
     */
    private array $changes = [];

    /**
     * The transactions recorded during the current reply, which the user can undo.
     *
     * @var list<Transaction>
     */
    private array $recorded = [];

    public function __construct(
        private GroqClient $groq,
        private TransactionService $transactions,
        private ReportService $reports,
    ) {}

    /**
     * Answer the user's message, recording or changing transactions it asks for.
     *
     * @return array{text: string, recorded: list<Transaction>}
     */
    public function reply(User $user, string $text): array
    {
        $this->changes = [];
        $this->recorded = [];

        $history = Cache::get($this->historyKey($user), []);
        $messages = [
            ['role' => 'system', 'content' => $this->instructions($user)],
            ...$history,
            ['role' => 'user', 'content' => $text],
        ];

        $answer = null;

        for ($round = 0; $round < self::MAX_ROUNDS && $answer === null; $round++) {
            $message = $this->groq->chat($messages, $this->tools());
            $toolCalls = $message['tool_calls'] ?? [];

            if ($toolCalls === []) {
                $answer = trim((string) $message['content']);

                break;
            }

            $messages[] = ['role' => 'assistant', 'content' => $message['content'] ?? null, 'tool_calls' => $toolCalls];

            foreach ($toolCalls as $call) {
                $arguments = json_decode($call['function']['arguments'] ?: '{}', true) ?: [];
                $result = $this->run($user, $call['function']['name'], $arguments, $text);

                $messages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => json_encode($result, JSON_UNESCAPED_UNICODE)];
            }
        }

        $reply = $this->changes !== [] ? implode("\n", $this->changes) : ($answer ?: 'ما فهمت عليك، جرّب تكتبها بطريقة ثانية.');

        Cache::put($this->historyKey($user), array_slice([
            ...$history,
            ['role' => 'user', 'content' => $text],
            ['role' => 'assistant', 'content' => $reply],
        ], -self::HISTORY_MESSAGES), now()->addMinutes(self::HISTORY_MINUTES));

        return ['text' => $reply, 'recorded' => $this->recorded];
    }

    /**
     * Run one tool the model called. Problems go back to the model so it can ask the user.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>|list<mixed>
     */
    private function run(User $user, string $tool, array $arguments, string $rawText): array
    {
        try {
            return match ($tool) {
                'record_transaction' => $this->recordTransaction($user, $arguments, $rawText),
                'repay_debt' => $this->repayDebt($arguments, $rawText),
                'get_balance' => $this->reports->balance($user),
                'get_summary' => $this->reports->summaryForPeriod(
                    $user,
                    Period::tryFrom((string) ($arguments['period'] ?? '')) ?? Period::ThisMonth,
                    $this->category(null, $arguments['category'] ?? null, fallBack: false),
                ),
                'list_open_debts' => $this->reports->openDebts($user, DebtDirection::tryFrom((string) ($arguments['direction'] ?? ''))),
                'update_last_transaction' => $this->updateLastTransaction($user, $arguments),
                'delete_last_transaction' => $this->deleteLastTransaction($user),
                default => ['error' => "No tool is called {$tool}."],
            };
        } catch (ValidationException $exception) {
            return ['error' => $exception->errors()];
        }
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function recordTransaction(User $user, array $arguments, string $rawText): array
    {
        $type = TransactionType::tryFrom((string) ($arguments['type'] ?? ''));

        $transaction = $this->transactions->record($user, [
            'type' => $type?->value ?? '',
            'amount' => $arguments['amount'] ?? null,
            'category_id' => $type?->hasCategory() ? $this->category($type, $arguments['category'] ?? null)?->id : null,
            'note' => $arguments['note'] ?? null,
            'counterparty' => $type === TransactionType::Expense ? null : ($arguments['counterparty'] ?? null),
            'debt_direction' => $type === TransactionType::Debt ? ($arguments['debt_direction'] ?? null) : null,
            'occurred_on' => $arguments['occurred_on'] ?? null,
            'raw_text' => $rawText,
        ]);

        $this->recorded[] = $transaction;
        $this->changes[] = '✅ سجّلت '.$this->describe($transaction);

        return ['recorded' => $this->describe($transaction)];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function repayDebt(array $arguments, string $rawText): array
    {
        $debt = Transaction::query()->openDebts()->find((int) ($arguments['debt_id'] ?? 0));

        if ($debt === null) {
            return ['error' => 'No open debt has that id. Use list_open_debts.'];
        }

        $repayment = $this->transactions->repayDebt($debt, [
            'amount' => $arguments['amount'] ?? null,
            'occurred_on' => $arguments['occurred_on'] ?? null,
            'raw_text' => $rawText,
        ]);

        $debt->refresh();
        $left = $debt->isSettled() ? 'وانسدّ الدين' : 'والمتبقي '.Money::toDecimal($debt->remainingInCents());

        $this->recorded[] = $repayment;
        $this->changes[] = "✅ سجّلت دفعة {$repayment->amount} من دين {$debt->counterparty}، {$left}.";

        return ['recorded' => $this->describe($repayment), 'debt_settled' => $debt->isSettled()];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function updateLastTransaction(User $user, array $arguments): array
    {
        $transaction = $this->transactions->latest($user);

        if ($transaction === null) {
            return ['error' => 'The user has no transactions yet.'];
        }

        $changes = array_filter([
            'amount' => $arguments['amount'] ?? null,
            'note' => $arguments['note'] ?? null,
            'occurred_on' => $arguments['occurred_on'] ?? null,
            'category_id' => isset($arguments['category']) ? $this->category($transaction->type, $arguments['category'])?->id : null,
        ], fn ($value) => $value !== null);

        $transaction = $this->transactions->update($transaction, $changes);
        $this->changes[] = '✏️ عدّلت آخر حركة: '.$this->describe($transaction);

        return ['updated' => $this->describe($transaction)];
    }

    /**
     * @return array<string, mixed>
     */
    private function deleteLastTransaction(User $user): array
    {
        $transaction = $this->transactions->latest($user);

        if ($transaction === null) {
            return ['error' => 'The user has no transactions yet.'];
        }

        $description = $this->describe($transaction);
        $this->transactions->delete($transaction);
        $this->changes[] = '🗑️ حذفت '.$description;

        return ['deleted' => $description];
    }

    /**
     * The category the model named, from the ones the user can see.
     *
     * A name that matches none falls back to «أخرى» when recording, and to no
     * category at all when it only narrows a summary.
     */
    private function category(?TransactionType $type, ?string $name, bool $fallBack = true): ?Category
    {
        $categories = Category::query()->when($type, fn ($query) => $query->ofType($type));

        $category = $name === null ? null : (clone $categories)->where('name', trim($name))->first();

        if ($category === null && $fallBack && $type !== null) {
            return (clone $categories)->where('name', Category::OTHER)->orderByRaw('user_id is not null')->first();
        }

        return $category;
    }

    /**
     * One line about a transaction, such as «مصروف 50.00 · أكل · اليوم».
     */
    private function describe(Transaction $transaction): string
    {
        $transaction->loadMissing('category');

        $parts = match (true) {
            $transaction->isRepayment() => ["دفعة سداد {$transaction->amount}", $transaction->counterparty],
            $transaction->isDebt() => ["دين {$transaction->debt_direction->label()} {$transaction->amount}", $transaction->counterparty],
            default => [
                "{$transaction->type->label()} {$transaction->amount}",
                $transaction->category?->name,
                $transaction->type === TransactionType::Income && $transaction->counterparty ? "من {$transaction->counterparty}" : null,
            ],
        };

        $parts[] = $transaction->note;
        $parts[] = $transaction->occurred_on->isToday() ? 'اليوم' : $transaction->occurred_on->toDateString();

        return implode(' · ', array_filter($parts));
    }

    private function instructions(User $user): string
    {
        $categories = Category::query()->inDisplayOrder()->get()->groupBy(fn (Category $category) => $category->type->value);
        $expenseCategories = $categories->get(TransactionType::Expense->value, collect())->pluck('name')->implode('، ');
        $incomeCategories = $categories->get(TransactionType::Income->value, collect())->pluck('name')->implode('، ');

        $debts = collect($this->reports->openDebts($user))
            ->map(fn (array $debt): string => "- #{$debt['id']} {$debt['counterparty']} (".DebtDirection::from($debt['direction'])->label().") المتبقي {$debt['remaining']}")
            ->implode("\n");

        $today = today()->locale('ar');

        return <<<PROMPT
            أنت مساعد تطبيق «مصاريفي». المستخدم ({$user->name}) يكتب لك بالعامية عن مصاريفه وقبضه وديونه، وأنت تسجّلها وتجاوب أسئلته باستخدام الأدوات فقط.

            القواعد:
            - لكل حركة يذكرها استدعِ record_transaction، ولو ذكر أكثر من حركة استدعِها لكل واحدة.
            - مصروف (expense): صرف، دفع، اشترى. قبض (income): قبض، استلم، وصله، راتب. دين (debt): استلف أو تدين من حدا = owed_by_me، سلّف أو داين حدا = owed_to_me.
            - لما حدا يرجّع دين أو المستخدم يسدّ دين، استدعِ repay_debt برقم الدين من قائمة الديون المفتوحة، ولا تسجّلها قبضًا أو مصروفًا.
            - التصنيف من القائمة فقط بنفس الكتابة، وإذا ما في تصنيف مناسب اترك التصنيف.
            - اليوم {$today->toDateString()} ({$today->locale('ar')->dayName}). «مبارح» يعني أمس. اكتب التاريخ بصيغة YYYY-MM-DD، واتركه فارغًا إذا الحركة اليوم.
            - إذا ناقص المبلغ، أو اسم الشخص في الدين، اسأل سؤالًا قصيرًا ولا تسجّل شيئًا.
            - «عدّل» أو «غلط» عن آخر حركة: update_last_transaction. «احذف» آخر حركة: delete_last_transaction.
            - لأسئلة الرصيد والمصاريف والديون استدعِ الأداة المناسبة، وجاوب من نتيجتها فقط، بالعربي وباختصار. لا تخترع أرقامًا.
            - لا تعرض المبالغ برمز عملة.

            تصنيفات المصاريف: {$expenseCategories}
            تصنيفات القبض: {$incomeCategories}

            الديون المفتوحة:
            {$debts}
            PROMPT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tools(): array
    {
        $date = ['type' => 'string', 'description' => 'YYYY-MM-DD. Leave out for today.'];

        return [
            $this->tool('record_transaction', 'Record an expense, an income or a new debt.', [
                'type' => ['type' => 'string', 'enum' => array_column(TransactionType::cases(), 'value')],
                'amount' => ['type' => 'number', 'description' => 'Always positive.'],
                'category' => ['type' => 'string', 'description' => 'For an expense or income: a category name from the list.'],
                'counterparty' => ['type' => 'string', 'description' => 'For a debt: who it is with. For an income: where it came from.'],
                'debt_direction' => ['type' => 'string', 'enum' => array_column(DebtDirection::cases(), 'value'), 'description' => 'For a debt only.'],
                'note' => ['type' => 'string', 'description' => 'What it was for, in a few words.'],
                'occurred_on' => $date,
            ], required: ['type', 'amount']),
            $this->tool('repay_debt', 'Record a payment against an open debt, in full or in part.', [
                'debt_id' => ['type' => 'integer', 'description' => 'The # of the debt in the open debts list.'],
                'amount' => ['type' => 'number', 'description' => 'Leave out to pay everything left.'],
                'occurred_on' => $date,
            ], required: ['debt_id']),
            $this->tool('get_balance', 'The balance, and the balance after open debts.'),
            $this->tool('get_summary', 'Total expenses and income for a period, with expenses by category.', [
                'period' => ['type' => 'string', 'enum' => array_column(Period::cases(), 'value')],
                'category' => ['type' => 'string', 'description' => 'Limit the totals to one category name.'],
            ], required: ['period']),
            $this->tool('list_open_debts', 'The debts not fully paid yet, with what is left on each.', [
                'direction' => ['type' => 'string', 'enum' => array_column(DebtDirection::cases(), 'value')],
            ]),
            $this->tool('update_last_transaction', 'Change the transaction the user recorded last.', [
                'amount' => ['type' => 'number'],
                'category' => ['type' => 'string'],
                'note' => ['type' => 'string'],
                'occurred_on' => $date,
            ]),
            $this->tool('delete_last_transaction', 'Delete the transaction the user recorded last.'),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function tool(string $name, string $description, array $properties = [], array $required = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => (object) $properties,
                    'required' => $required,
                ],
            ],
        ];
    }

    private function historyKey(User $user): string
    {
        return "telegram:history:{$user->id}";
    }
}
