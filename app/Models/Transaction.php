<?php

namespace App\Models;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Services\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An expense, an income, or a debt.
 *
 * @property TransactionType $type
 * @property string $amount
 * @property ?DebtDirection $debt_direction
 * @property ?CarbonInterface $settled_on
 * @property CarbonInterface $occurred_on
 */
#[Fillable([
    'user_id',
    'type',
    'amount',
    'category_id',
    'note',
    'counterparty',
    'debt_direction',
    'settled_on',
    'settles_id',
    'occurred_on',
    'raw_text',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'debt_direction' => DebtDirection::class,
        ];
    }

    /**
     * Stored as a plain Y-m-d date so range queries compare correctly.
     *
     * @return Attribute<?CarbonImmutable, CarbonInterface|string|null>
     */
    protected function occurredOn(): Attribute
    {
        return $this->dateOnly();
    }

    /**
     * @return Attribute<?CarbonImmutable, CarbonInterface|string|null>
     */
    protected function settledOn(): Attribute
    {
        return $this->dateOnly();
    }

    /**
     * @return Attribute<?CarbonImmutable, CarbonInterface|string|null>
     */
    private function dateOnly(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : CarbonImmutable::parse($value)->startOfDay(),
            set: fn (CarbonInterface|string|null $value) => $value === null ? null : CarbonImmutable::parse($value)->toDateString(),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The debt this transaction pays off, when it is a repayment.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'settles_id');
    }

    /**
     * The payments made against this debt.
     *
     * @return HasMany<Transaction, $this>
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(Transaction::class, 'settles_id');
    }

    public function isDebt(): bool
    {
        return $this->type === TransactionType::Debt;
    }

    public function isRepayment(): bool
    {
        return $this->settles_id !== null;
    }

    public function isSettled(): bool
    {
        return $this->settled_on !== null;
    }

    public function amountInCents(): int
    {
        return Money::toCents($this->amount);
    }

    /**
     * How much of this debt has been paid so far, in cents.
     */
    public function repaidInCents(): int
    {
        return Money::toCents($this->repayments()->sum('amount'));
    }

    /**
     * How much of this debt is still unpaid, in cents.
     */
    public function remainingInCents(): int
    {
        return $this->amountInCents() - $this->repaidInCents();
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, TransactionType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Debts that are not yet fully paid.
     *
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function openDebts(Builder $query): void
    {
        $query->where('type', TransactionType::Debt)->whereNull('settled_on');
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function occurredBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()]);
    }
}
