<?php

namespace App\Services;

use App\Enums\DebtDirection;
use App\Enums\Period;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Balances, period summaries and open debts for a user.
 *
 * All amounts are returned as floats rounded to two decimals.
 */
class ReportService
{
    /**
     * The user's balance before and after open debts.
     *
     * - cash: income − expenses − money lent + money borrowed.
     * - after_debts: cash − what the user still owes + what is still owed to them.
     *
     * Debt payments are recorded as income or expenses, so they already count in cash.
     *
     * @return array{cash: float, owed_by_me: float, owed_to_me: float, after_debts: float}
     */
    public function balance(User $user): array
    {
        $totals = $user->transactions()
            ->selectRaw('type, debt_direction, sum(amount) as total')
            ->groupBy('type', 'debt_direction')
            ->get()
            ->mapWithKeys(fn (Transaction $row): array => [
                $row->type->value.':'.$row->debt_direction?->value => Money::toCents($row->getAttribute('total')),
            ]);

        $cashInCents = $totals->get(TransactionType::Income->value.':', 0)
            - $totals->get(TransactionType::Expense->value.':', 0)
            + $totals->get(TransactionType::Debt->value.':'.DebtDirection::OwedByMe->value, 0)
            - $totals->get(TransactionType::Debt->value.':'.DebtDirection::OwedToMe->value, 0);

        $openDebts = $this->openDebtsQuery($user)->get();
        $owedByMeInCents = $this->remainingInCents($openDebts->where('debt_direction', DebtDirection::OwedByMe));
        $owedToMeInCents = $this->remainingInCents($openDebts->where('debt_direction', DebtDirection::OwedToMe));

        return [
            'cash' => Money::fromCents($cashInCents),
            'owed_by_me' => Money::fromCents($owedByMeInCents),
            'owed_to_me' => Money::fromCents($owedToMeInCents),
            'after_debts' => Money::fromCents($cashInCents - $owedByMeInCents + $owedToMeInCents),
        ];
    }

    /**
     * Totals for a named period such as this week or last month.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     expenses_total: float,
     *     income_total: float,
     *     expenses_by_category: list<array{category_id: ?int, category: ?string, total: float, percentage: float}>,
     * }
     */
    public function summaryForPeriod(User $user, Period $period, ?Category $category = null, ?CarbonInterface $today = null): array
    {
        [$from, $to] = $period->range($today ?? today());

        return $this->summary($user, $from, $to, $category);
    }

    /**
     * Totals between two days, inclusive, with expenses broken down by category.
     *
     * Categories are ordered from the largest share of spending to the smallest.
     * Debt payments the user made have no category, so they appear with a null one.
     * Passing a category limits every total to that category.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     expenses_total: float,
     *     income_total: float,
     *     expenses_by_category: list<array{category_id: ?int, category: ?string, total: float, percentage: float}>,
     * }
     */
    public function summary(User $user, CarbonInterface $from, CarbonInterface $to, ?Category $category = null): array
    {
        $inPeriod = fn () => $user->transactions()
            ->occurredBetween($from, $to)
            ->when($category, fn ($query) => $query->whereBelongsTo($category));

        $expenseRows = $inPeriod()
            ->ofType(TransactionType::Expense)
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get();

        $expensesInCents = $expenseRows->sum(fn (Transaction $row): int => Money::toCents($row->getAttribute('total')));
        $incomeInCents = Money::toCents($inPeriod()->ofType(TransactionType::Income)->sum('amount'));

        $byCategory = $expenseRows
            ->map(function (Transaction $row) use ($expensesInCents): array {
                $totalInCents = Money::toCents($row->getAttribute('total'));

                return [
                    'category_id' => $row->category_id,
                    'category' => $row->category?->name,
                    'total' => Money::fromCents($totalInCents),
                    'percentage' => $expensesInCents > 0 ? round($totalInCents * 100 / $expensesInCents, 1) : 0.0,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'expenses_total' => Money::fromCents($expensesInCents),
            'income_total' => Money::fromCents($incomeInCents),
            'expenses_by_category' => $byCategory,
        ];
    }

    /**
     * Debts that are not fully paid yet, oldest first.
     *
     * @return list<array{
     *     id: int,
     *     counterparty: string,
     *     direction: string,
     *     amount: float,
     *     repaid: float,
     *     remaining: float,
     *     occurred_on: string,
     *     note: ?string,
     * }>
     */
    public function openDebts(User $user, ?DebtDirection $direction = null): array
    {
        return $this->openDebtsQuery($user)
            ->when($direction, fn ($query) => $query->where('debt_direction', $direction))
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get()
            ->map(function (Transaction $debt): array {
                $repaidInCents = Money::toCents($debt->getAttribute('repayments_sum_amount'));

                return [
                    'id' => $debt->id,
                    'counterparty' => $debt->counterparty,
                    'direction' => $debt->debt_direction->value,
                    'amount' => Money::fromCents($debt->amountInCents()),
                    'repaid' => Money::fromCents($repaidInCents),
                    'remaining' => Money::fromCents($debt->amountInCents() - $repaidInCents),
                    'occurred_on' => $debt->occurred_on->toDateString(),
                    'note' => $debt->note,
                ];
            })
            ->all();
    }

    /**
     * @return HasMany<Transaction, User>
     */
    private function openDebtsQuery(User $user): HasMany
    {
        return $user->transactions()->openDebts()->withSum('repayments', 'amount');
    }

    /**
     * @param  iterable<Transaction>  $debts  Loaded with the repayments_sum_amount aggregate.
     */
    private function remainingInCents(iterable $debts): int
    {
        $total = 0;

        foreach ($debts as $debt) {
            $total += $debt->amountInCents() - Money::toCents($debt->getAttribute('repayments_sum_amount'));
        }

        return $total;
    }
}
