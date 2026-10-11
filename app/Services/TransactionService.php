<?php

namespace App\Services;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;

/**
 * Records, edits and deletes a user's transactions and pays off debts.
 *
 * Every method validates its input and throws a ValidationException on bad
 * data, so the same rules apply to the web app and the Telegram agent.
 * Callers are responsible for passing transactions that belong to the user.
 */
class TransactionService
{
    /**
     * Record a new expense, income or debt.
     *
     * Payments against a debt are recorded with repayDebt() instead.
     *
     * @param  array{
     *     type: string|TransactionType,
     *     amount: string|int|float,
     *     category_id?: ?int,
     *     note?: ?string,
     *     counterparty?: ?string,
     *     debt_direction?: string|DebtDirection|null,
     *     occurred_on?: ?string,
     *     raw_text?: ?string,
     * }  $attributes
     *
     * @throws ValidationException
     */
    public function record(User $user, array $attributes): Transaction
    {
        $attributes = $this->normalizeEnums($attributes);
        $type = TransactionType::tryFrom((string) ($attributes['type'] ?? ''));

        $validated = Validator::make($attributes, [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', ...$this->amountRules()],
            'category_id' => [
                'nullable',
                'integer',
                'required_unless:type,'.TransactionType::Debt->value,
                'prohibited_if:type,'.TransactionType::Debt->value,
                $this->categoryRule($user, $type),
            ],
            'note' => ['nullable', 'string', 'max:255'],
            'counterparty' => ['nullable', 'string', 'max:255', 'required_if:type,'.TransactionType::Debt->value],
            'debt_direction' => [
                'nullable',
                Rule::enum(DebtDirection::class),
                'required_if:type,'.TransactionType::Debt->value,
                'prohibited_unless:type,'.TransactionType::Debt->value,
            ],
            'occurred_on' => ['nullable', 'date'],
            'raw_text' => ['nullable', 'string'],
        ])->validate();

        return $user->transactions()->create([
            ...$validated,
            'amount' => Money::toDecimal(Money::toCents($validated['amount'])),
            'occurred_on' => $validated['occurred_on'] ?? today(),
        ]);
    }

    /**
     * Change an existing transaction. Only the given attributes change.
     *
     * The type and debt direction cannot change; delete and record again instead.
     * A debt cannot drop below what was already paid, and a payment cannot
     * exceed what is left on its debt.
     *
     * @param  array{
     *     amount?: string|int|float,
     *     category_id?: ?int,
     *     note?: ?string,
     *     counterparty?: ?string,
     *     occurred_on?: string,
     * }  $attributes
     *
     * @throws ValidationException
     */
    public function update(Transaction $transaction, array $attributes): Transaction
    {
        $groupedByCategory = $transaction->type->hasCategory() && ! $transaction->isRepayment();

        $validated = Validator::make($attributes, [
            'type' => ['prohibited'],
            'debt_direction' => ['prohibited'],
            'settles_id' => ['prohibited'],
            'amount' => ['sometimes', 'required', ...$this->amountRules()],
            'category_id' => $groupedByCategory
                ? ['sometimes', 'required', 'integer', $this->categoryRule($transaction->user, $transaction->type)]
                : ['prohibited'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'counterparty' => $transaction->isDebt()
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['sometimes', 'nullable', 'string', 'max:255'],
            'occurred_on' => ['sometimes', 'required', 'date'],
        ])->validate();

        if (array_key_exists('amount', $validated)) {
            $validated['amount'] = Money::toDecimal(Money::toCents($validated['amount']));
        }

        return DB::transaction(function () use ($transaction, $validated): Transaction {
            $debt = $this->lockDebtOf($transaction);
            $newAmountInCents = Money::toCents($validated['amount'] ?? $transaction->amount);

            if ($transaction->isDebt() && $newAmountInCents < $debt->repaidInCents()) {
                throw ValidationException::withMessages([
                    'amount' => 'The debt cannot be less than the '.Money::fromCents($debt->repaidInCents()).' already paid.',
                ]);
            }

            if ($transaction->isRepayment()) {
                $leftBeforeThisPayment = $debt->remainingInCents() + $transaction->amountInCents();

                if ($newAmountInCents > $leftBeforeThisPayment) {
                    throw ValidationException::withMessages([
                        'amount' => 'The payment cannot be more than the '.Money::fromCents($leftBeforeThisPayment).' left on the debt.',
                    ]);
                }
            }

            $transaction->update($validated);

            if ($debt !== null) {
                $this->syncSettlement($debt->refresh());
            }

            return $transaction->refresh();
        });
    }

    /**
     * Delete a transaction.
     *
     * Deleting a debt deletes its payments too. Deleting a payment puts the
     * amount back on its debt and reopens it if it was settled.
     */
    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $debt = $transaction->isRepayment() ? $this->lockDebtOf($transaction) : null;

            $transaction->repayments()->delete();
            $transaction->delete();

            if ($debt !== null) {
                $this->syncSettlement($debt);
            }
        });
    }

    /**
     * Record a payment against a debt, in full or in part.
     *
     * Paying off a debt someone owes the user is recorded as income; paying
     * off a debt the user owes is recorded as an expense. The debt is marked
     * settled once nothing is left on it.
     *
     * @param  array{
     *     amount?: string|int|float|null,
     *     note?: ?string,
     *     occurred_on?: ?string,
     *     raw_text?: ?string,
     * }  $attributes  Leave out the amount to pay off everything that is left.
     *
     * @throws ValidationException
     */
    public function repayDebt(Transaction $debt, array $attributes = []): Transaction
    {
        if (! $debt->isDebt()) {
            throw ValidationException::withMessages(['debt' => 'Only a debt can be paid off.']);
        }

        $validated = Validator::make($attributes, [
            'amount' => ['nullable', ...$this->amountRules()],
            'note' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['nullable', 'date'],
            'raw_text' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($debt, $validated): Transaction {
            $debt = Transaction::query()->lockForUpdate()->findOrFail($debt->id);
            $remainingInCents = $debt->remainingInCents();

            if ($debt->isSettled() || $remainingInCents <= 0) {
                throw ValidationException::withMessages(['debt' => 'This debt is already paid off.']);
            }

            $amountInCents = isset($validated['amount']) ? Money::toCents($validated['amount']) : $remainingInCents;

            if ($amountInCents > $remainingInCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot be more than the '.Money::fromCents($remainingInCents).' left on the debt.',
                ]);
            }

            $repayment = $debt->repayments()->create([
                'user_id' => $debt->user_id,
                'type' => $debt->debt_direction->repaymentType(),
                'amount' => Money::toDecimal($amountInCents),
                'counterparty' => $debt->counterparty,
                'note' => $validated['note'] ?? null,
                'occurred_on' => $validated['occurred_on'] ?? today(),
                'raw_text' => $validated['raw_text'] ?? null,
            ]);

            $this->syncSettlement($debt);

            return $repayment;
        });
    }

    /**
     * The user's most recently recorded transaction, optionally of one type.
     */
    public function latest(User $user, ?TransactionType $type = null): ?Transaction
    {
        return $user->transactions()
            ->when($type, fn ($query) => $query->ofType($type))
            ->latest('id')
            ->first();
    }

    /**
     * Mark the debt settled when nothing is left on it, or reopen it otherwise.
     */
    private function syncSettlement(Transaction $debt): void
    {
        $settledOn = $debt->remainingInCents() <= 0
            ? $debt->repayments()->max('occurred_on')
            : null;

        $debt->update(['settled_on' => $settledOn]);
    }

    /**
     * Lock and return the debt a transaction belongs to: itself for a debt,
     * its debt for a payment, or null for anything else.
     */
    private function lockDebtOf(Transaction $transaction): ?Transaction
    {
        $debtId = $transaction->isDebt() ? $transaction->id : $transaction->settles_id;

        return $debtId === null ? null : Transaction::query()->lockForUpdate()->findOrFail($debtId);
    }

    /**
     * @return list<string>
     */
    private function amountRules(): array
    {
        return ['numeric', 'gt:0', 'max:9999999999.99'];
    }

    /**
     * The category must be one of the defaults or the user's own, and of the
     * same type as the transaction.
     */
    private function categoryRule(User $user, ?TransactionType $type): Exists
    {
        return Rule::exists('categories', 'id')
            ->where('type', $type?->value)
            ->where(fn (QueryBuilder $query) => $query->whereNull('user_id')->orWhere('user_id', $user->id));
    }

    /**
     * Accept enum instances as well as their string values.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeEnums(array $attributes): array
    {
        foreach (['type', 'debt_direction'] as $key) {
            if (($attributes[$key] ?? null) instanceof \BackedEnum) {
                $attributes[$key] = $attributes[$key]->value;
            }
        }

        return $attributes;
    }
}
