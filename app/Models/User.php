<?php

namespace App\Models;

use App\Enums\AccessStatus;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property ?CarbonInterface $trial_ends_at
 * @property ?CarbonInterface $subscription_ends_at
 * @property bool $is_admin
 */
#[Fillable(['telegram_id', 'name', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Every new user starts with a free trial.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->trial_ends_at ??= now()->addDays(config('subscriptions.trial_days'));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Only admins may open the admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    /**
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function admins(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The categories this user created, excluding the shared defaults.
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Whether the user may add, change and delete records. Otherwise the account is read-only.
     */
    public function hasFullAccess(): bool
    {
        return $this->accessStatus() !== AccessStatus::ReadOnly;
    }

    public function accessStatus(): AccessStatus
    {
        if ($this->subscription_ends_at?->isFuture()) {
            return AccessStatus::Subscribed;
        }

        if ($this->trial_ends_at?->isFuture()) {
            return AccessStatus::Trial;
        }

        return AccessStatus::ReadOnly;
    }

    /**
     * When full access ends, or ended: the later of the trial and the subscription.
     */
    public function accessEndsAt(): ?CarbonInterface
    {
        $dates = array_filter([$this->trial_ends_at, $this->subscription_ends_at]);

        return $dates === [] ? null : max($dates);
    }

    /**
     * Where more subscription time starts: when the current trial or
     * subscription ends, so paying early loses nothing, or now once it has ended.
     */
    public function nextSubscriptionStart(): CarbonInterface
    {
        $accessEndsAt = $this->accessEndsAt();

        return $accessEndsAt?->isFuture() ? $accessEndsAt->copy() : now();
    }
}
