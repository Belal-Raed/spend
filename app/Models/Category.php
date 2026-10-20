<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A label for grouping expenses or income.
 *
 * A category without a user is a default shared by everyone, so each user
 * sees the shared defaults alongside their own.
 */
#[Fillable(['user_id', 'name', 'type'])]
class Category extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * The name of the default catch-all category of each type.
     */
    public const OTHER = 'أخرى';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Whether this category is a default shared by every user.
     */
    public function isDefault(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Limit the query to the shared defaults and the tenant's own categories.
     *
     * @param  Builder<Category>  $query
     */
    protected function limitToTenant(Builder $query, User $tenant): void
    {
        $query->where(fn (Builder $query) => $query->whereNull($query->qualifyColumn('user_id'))->orWhereBelongsTo($tenant));
    }

    /**
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, TransactionType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Sort by name, with the catch-all «أخرى» last.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function inDisplayOrder(Builder $query): void
    {
        $query->orderByRaw('name = ?', [self::OTHER])->orderBy('name');
    }
}
