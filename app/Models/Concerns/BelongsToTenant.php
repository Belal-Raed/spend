<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\CurrentTenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Keeps each user's records apart from everyone else's.
 *
 * Every query on the model sees only the current tenant's records, and throws
 * when no tenant is set instead of returning everyone's. While a tenant is
 * set, saving a record that belongs to another user throws too. A record
 * without a user, such as a shared default category, belongs to no one.
 *
 * Code that must see every user's records, such as the admin panel, opts out
 * explicitly with acrossTenants().
 *
 * @see CurrentTenant
 */
trait BelongsToTenant
{
    public const TENANT_SCOPE = 'tenant';

    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(self::TENANT_SCOPE, function (Builder $query): void {
            $query->getModel()->limitToTenant($query, app(CurrentTenant::class)->getOrFail());
        });

        static::saving(function (self $model): void {
            $tenant = app(CurrentTenant::class)->get();

            if ($tenant !== null && $model->user_id !== null && $model->user_id !== $tenant->id) {
                throw new LogicException(sprintf(
                    'Cannot save %s #%s for user #%d while user #%d is the tenant.',
                    $model::class, $model->getKey() ?? 'new', $model->user_id, $tenant->id,
                ));
            }
        });
    }

    /**
     * Limit the query to the records the tenant owns.
     *
     * @param  Builder<static>  $query
     */
    protected function limitToTenant(Builder $query, User $tenant): void
    {
        $query->whereBelongsTo($tenant);
    }

    /**
     * Lift the tenant limit, for code that must see every user's records.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function acrossTenants(Builder $query): void
    {
        $query->withoutGlobalScope(self::TENANT_SCOPE);
    }
}
