<?php

namespace Database\Seeders;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public const EMAIL = 'test@example.com';

    /**
     * Income and expenses as [category, amount, note, days ago].
     *
     * @var array<string, list<array{0: string, 1: string, 2: string, 3: int}>>
     */
    private const TRANSACTIONS = [
        'income' => [
            ['راتب', '3500.00', 'راتب الشهر الماضي', 40],
            ['راتب', '3500.00', 'راتب الشهر', 10],
            ['شغل حر', '600.00', 'تصميم موقع', 4],
        ],
        'expense' => [
            ['بيت', '1200.00', 'إيجار', 38],
            ['أكل', '310.50', 'مشتريات السوبرماركت', 35],
            ['فواتير', '180.00', 'فاتورة الكهرباء', 33],
            ['بيت', '1200.00', 'إيجار', 9],
            ['أكل', '245.75', 'مشتريات السوبرماركت', 8],
            ['مواصلات', '60.00', 'بنزين', 6],
            ['اتصالات', '50.00', 'باقة الإنترنت', 5],
            ['صحة', '85.00', 'دواء', 3],
            ['أكل', '42.50', 'مطعم', 1],
            ['مواصلات', '15.00', 'تاكسي', 0],
        ],
    ];

    /**
     * Debts as [direction, counterparty, amount, days ago, amount repaid, repaid days ago].
     * A null amount repaid leaves the debt untouched.
     *
     * @var list<array{0: DebtDirection, 1: string, 2: string, 3: int, 4: ?string, 5: ?int}>
     */
    private const DEBTS = [
        [DebtDirection::OwedByMe, 'خالد', '500.00', 20, '200.00', 7],
        [DebtDirection::OwedToMe, 'سامي', '300.00', 12, null, null],
        [DebtDirection::OwedToMe, 'محمود', '150.00', 36, '150.00', 30],
    ];

    /**
     * Give the demo user a couple of months of income, expenses and debts.
     *
     * Safe to run more than once: a demo user who already has transactions is left alone.
     */
    public function run(TransactionService $transactions): void
    {
        $this->call(DefaultCategoriesSeeder::class);

        $user = User::query()->where('email', self::EMAIL)->first()
            ?? User::factory()->create(['name' => 'Test User', 'email' => self::EMAIL]);

        if ($user->transactions()->exists()) {
            return;
        }

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
