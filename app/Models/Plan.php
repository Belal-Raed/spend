<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subscription length the user can pay for, such as one month or three.
 *
 * @property ?string $price
 */
#[Fillable(['name', 'months', 'price', 'is_active'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'months' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Whether the user can pay for this plan now: it is offered and has a price.
     */
    public function isPurchasable(): bool
    {
        return $this->is_active && $this->price !== null;
    }

    /**
     * Plans shown to users, shortest first.
     *
     * @param  Builder<Plan>  $query
     */
    #[Scope]
    protected function offered(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('months');
    }
}
