<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * The user whose records every tenant-owned model query is limited to.
 *
 * On the web it is the signed-in user. Code that runs without one, such as
 * the Telegram bot, a queued job or a seeder, names the tenant with runAs().
 * A fresh instance is made for every request and every queued job.
 */
#[Scoped]
class CurrentTenant
{
    private ?User $user = null;

    public function get(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $signedIn = Auth::user();

        return $signedIn instanceof User ? $signedIn : null;
    }

    /**
     * @throws LogicException When no tenant is set.
     */
    public function getOrFail(): User
    {
        return $this->get() ?? throw new LogicException(
            'No tenant is set. Sign a user in, or run the code through CurrentTenant::runAs().'
        );
    }

    /**
     * Run the callback with the given user as the tenant, then restore the previous one.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function runAs(User $user, callable $callback): mixed
    {
        $previous = $this->user;
        $this->user = $user;

        try {
            return $callback();
        } finally {
            $this->user = $previous;
        }
    }
}
