<?php

namespace Database\Seeders;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Services\CurrentTenant;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public const PHONE = '0599000000';

    /**
     * Income and expenses as [category, amount, note, days ago].
     *
     * @var array<string, list<array{0: string, 1: string, 2: string, 3: int}>>
     */
    private const TRANSACTIONS = [
        'income' => [
            ['راتب', '3000.00', 'راتب الشهر', 10],
        ],
        'expense' => [
            ['أكل', '500.00', 'مشتريات الشهر الماضي', 35],
            ['بيت', '1000.00', 'إيجار', 9],
            ['أكل', '300.00', 'مشتريات', 6],
            ['مواصلات', '100.00', 'بنزين', 3],
            ['فواتير', '100.00', 'فاتورة الكهرباء', 1],
        ],
    ];

    /**
     * Debts as [direction, counterparty, amount, days ago, amount repaid, repaid days ago].
     * A null amount repaid leaves the debt untouched.
     *
     * @var list<array{0: DebtDirection, 1: string, 2: string, 3: int, 4: ?string, 5: ?int}>
     */
    private const DEBTS = [
        [DebtDirection::OwedByMe, 'خالد', '500.00', 20, '100.00', 5],
        [DebtDirection::OwedToMe, 'سامي', '200.00', 12, null, null],
        [DebtDirection::OwedToMe, 'محمود', '100.00', 36, '100.00', 30],
    ];

    /**
     * Give the demo user a few round-number income, expense and debt records.
     *
     * Safe to run more than once: a demo user who already has transactions is left alone.
     */
    public function run(CurrentTenant $tenant, TransactionService $transactions): void
    {
        $this->call(DefaultCategoriesSeeder::class);

        $user = User::query()->where('phone', self::PHONE)->first()
            ?? User::factory()->create([
                'name' => 'Test User',
                'phone' => self::PHONE,
                // Set here because DatabaseSeeder runs without the model events that start a trial.
                'trial_ends_at' => now()->addDays(config('subscriptions.trial_days')),
            ]);

        $tenant->runAs($user, function () use ($user, $transactions): void {
            if (! $user->transactions()->exists()) {
                $this->recordTransactions($user, $transactions);
            }
        });
    }

    private function recordTransactions(User $user, TransactionService $transactions): void
    {
        foreach (self::TRANSACTIONS as $type => $rows) {
            foreach ($rows as [$category, $amount, $note, $daysAgo]) {
                $transactions->record($user, [
                    'type' => $type,
                    'amount' => $amount,
                    'category_id' => $this->defaultCategoryId(TransactionType::from($type), $category),
                    'note' => $note,
                    'occurred_on' => today()->subDays($daysAgo)->toDateString(),
                ]);
            }
        }

        foreach (self::DEBTS as [$direction, $counterparty, $amount, $daysAgo, $amountRepaid, $repaidDaysAgo]) {
            $debt = $transactions->record($user, [
                'type' => TransactionType::Debt,
                'amount' => $amount,
                'counterparty' => $counterparty,
                'debt_direction' => $direction,
                'occurred_on' => today()->subDays($daysAgo)->toDateString(),
            ]);

            if ($amountRepaid !== null) {
                $transactions->repayDebt($debt, [
                    'amount' => $amountRepaid,
                    'occurred_on' => today()->subDays($repaidDaysAgo)->toDateString(),
                ]);
            }
        }
    }

    private function defaultCategoryId(TransactionType $type, string $name): int
    {
        return Category::query()->whereNull('user_id')->ofType($type)->where('name', $name)->sole()->id;
    }
}
