<?php

namespace Tests\Feature\Services;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ExpenseAgent;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExpenseAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 09:00');
        $this->seed(DefaultCategoriesSeeder::class);
        $this->user = $this->signIn();
    }

    /**
     * Groq's answer when the model calls tools.
     *
     * @param  array<string, array<string, mixed>>  $calls  Tool name => arguments.
     * @return array<string, mixed>
     */
    private function toolCalls(array $calls): array
    {
        $toolCalls = [];

        foreach ($calls as $name => $arguments) {
            $toolCalls[] = ['id' => 'call_'.count($toolCalls), 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)]];
        }

        return ['choices' => [['message' => ['role' => 'assistant', 'content' => null, 'tool_calls' => $toolCalls]]]];
    }

    /**
     * Groq's answer when the model writes to the user.
     *
     * @return array<string, mixed>
     */
    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     */
    private function groqAnswers(array $answers): void
    {
        $sequence = Http::sequence();

        foreach ($answers as $answer) {
            $sequence->push($answer);
        }

        Http::fake(['api.groq.com/*' => $sequence]);
    }

    /**
     * @return array{text: string, recorded: list<Transaction>}
     */
    private function reply(string $text): array
    {
        return app(ExpenseAgent::class)->reply($this->user, $text);
    }

    public function test_records_an_expense_in_the_named_category_and_says_what_was_saved(): void
    {
        $this->groqAnswers([
            $this->toolCalls(['record_transaction' => ['type' => 'expense', 'amount' => 50, 'category' => 'أكل', 'note' => 'غداء']]),
            $this->text('تمام سجلتها'),
        ]);

        $reply = $this->reply('صرفت 50 على غدا');

        $expense = $this->user->transactions()->sole();
        $this->assertSame(TransactionType::Expense, $expense->type);
        $this->assertSame('50.00', $expense->amount);
        $this->assertSame('أكل', $expense->category->name);
        $this->assertSame('صرفت 50 على غدا', $expense->raw_text);
        $this->assertSame('✅ سجّلت مصروف 50.00 · أكل · غداء · اليوم', $reply['text']);
        $this->assertTrue($expense->is($reply['recorded'][0]));
    }

    public function test_an_unknown_category_falls_back_to_other(): void
    {
        $this->groqAnswers([
            $this->toolCalls(['record_transaction' => ['type' => 'expense', 'amount' => 20, 'category' => 'هدايا']]),
            $this->text('تم'),
        ]);

        $this->reply('اشتريت هدية ب 20');

        $this->assertSame('أخرى', $this->user->transactions()->sole()->category->name);
    }

    public function test_records_a_debt_with_its_direction_and_date(): void
    {
        $this->groqAnswers([
            $this->toolCalls(['record_transaction' => ['type' => 'debt', 'amount' => 200, 'counterparty' => 'خالد', 'debt_direction' => 'owed_by_me', 'occurred_on' => '2026-10-20']]),
            $this->text('تم'),
        ]);

        $reply = $this->reply('مبارح تدينت 200 من خالد');

        $debt = $this->user->transactions()->sole();
        $this->assertSame(DebtDirection::OwedByMe, $debt->debt_direction);
        $this->assertSame('2026-10-20', $debt->occurred_on->toDateString());
        $this->assertSame('✅ سجّلت دين عليّ 200.00 · خالد · 2026-10-20', $reply['text']);
    }

    public function test_pays_off_an_open_debt_from_the_list_it_was_given(): void
    {
        $debt = Transaction::factory()->for($this->user)->debt(DebtDirection::OwedToMe)->create(['amount' => '300.00', 'counterparty' => 'سامي']);
        $this->groqAnswers([
            $this->toolCalls(['repay_debt' => ['debt_id' => $debt->id, 'amount' => 100]]),
            $this->text('تم'),
        ]);

        $reply = $this->reply('سامي رجعلي 100');

        $this->assertSame(20000, $debt->fresh()->remainingInCents());
        $this->assertSame('✅ سجّلت دفعة 100.00 من دين سامي، والمتبقي 200.00.', $reply['text']);
        Http::assertSent(fn (Request $request): bool => str_contains($request['messages'][0]['content'], "#{$debt->id} سامي (إلي) المتبقي 300"));
    }

    public function test_refused_record_goes_back_to_the_model_which_asks_the_user(): void
    {
        $this->groqAnswers([
            $this->toolCalls(['record_transaction' => ['type' => 'debt', 'amount' => 200, 'debt_direction' => 'owed_by_me']]),
            $this->text('من مين تدينت؟'),
        ]);

        $reply = $this->reply('تدينت 200');

        $this->assertSame('من مين تدينت؟', $reply['text']);
        $this->assertSame([], $reply['recorded']);
        $this->assertSame(0, $this->user->transactions()->count());
        Http::assertSent(fn (Request $request): bool => collect($request['messages'])->contains(
            fn (array $message): bool => $message['role'] === 'tool' && str_contains($message['content'], 'counterparty'),
        ));
    }

    public function test_answers_a_question_from_the_tool_result(): void
    {
        Transaction::factory()->for($this->user)->income()->create(['amount' => '1000.00']);
        $this->groqAnswers([
            $this->toolCalls(['get_balance' => []]),
            $this->text('رصيدك 1000'),
        ]);

        $reply = $this->reply('قديش رصيدي؟');

        $this->assertSame('رصيدك 1000', $reply['text']);
        Http::assertSent(fn (Request $request): bool => collect($request['messages'])->contains(
            fn (array $message): bool => $message['role'] === 'tool' && str_contains($message['content'], '"cash":1000'),
        ));
    }

    public function test_changes_the_last_transaction(): void
    {
        $expense = Transaction::factory()->for($this->user)->create(['amount' => '50.00', 'note' => 'غداء']);
        $this->groqAnswers([
            $this->toolCalls(['update_last_transaction' => ['amount' => 60]]),
            $this->text('تم'),
        ]);

        $reply = $this->reply('غلط، كانت 60');

        $this->assertSame('60.00', $expense->fresh()->amount);
        $this->assertStringStartsWith('✏️ عدّلت آخر حركة: مصروف 60.00', $reply['text']);
    }

    public function test_deletes_the_last_transaction_and_not_another_users(): void
    {
        $mine = Transaction::factory()->for($this->user)->create(['amount' => '50.00']);
        $this->groqAnswers([
            $this->toolCalls(['delete_last_transaction' => []]),
            $this->text('تم'),
        ]);

        $reply = $this->reply('احذف آخر حركة');

        $this->assertModelMissing($mine);
        $this->assertStringStartsWith('🗑️ حذفت مصروف 50.00', $reply['text']);
        $this->assertSame([], $reply['recorded']);
    }

    public function test_remembers_the_last_messages_so_a_short_answer_completes_the_request(): void
    {
        $this->groqAnswers([
            $this->text('قديش صرفت؟'),
            $this->toolCalls(['record_transaction' => ['type' => 'expense', 'amount' => 40, 'category' => 'مواصلات']]),
            $this->text('تم'),
        ]);

        $this->reply('عبيت بنزين');
        $this->reply('40');

        Http::assertSent(fn (Request $request): bool => array_column($request['messages'], 'content') === [
            $request['messages'][0]['content'],
            'عبيت بنزين',
            'قديش صرفت؟',
            '40',
        ]);
        $this->assertSame('40.00', $this->user->transactions()->sole()->amount);
    }

    public function test_stops_calling_tools_after_five_rounds(): void
    {
        $this->groqAnswers(array_fill(0, 6, $this->toolCalls(['get_balance' => []])));

        $reply = $this->reply('قديش رصيدي؟');

        $this->assertSame('ما فهمت عليك، جرّب تكتبها بطريقة ثانية.', $reply['text']);
        Http::assertSentCount(5);
    }
}
