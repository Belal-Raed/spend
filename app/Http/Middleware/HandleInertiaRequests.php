<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * `today` is the date in the app's timezone, so the pages agree with the
     * server on which day "today" is.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => fn (): ?array => $request->user()?->only(['id', 'name', 'phone']),
            ],
            'access' => fn (): ?array => $request->user() ? $this->access($request->user()) : null,
            'today' => fn (): string => today()->toDateString(),
        ];
    }

    /**
     * What the user may do with their account, and until when.
     *
     * `is_ending_soon` is set in the last days of a trial or subscription, so
     * the pages can remind the user to subscribe before the account turns read-only.
     *
     * @return array{status: string, status_label: string, ends_on: ?string, days_left: int, is_ending_soon: bool}
     */
    private function access(User $user): array
    {
        $status = $user->accessStatus();
        $endsAt = $user->accessEndsAt();
        $daysLeft = $endsAt?->isFuture() ? (int) ceil(now()->diffInDays($endsAt)) : 0;

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'ends_on' => $endsAt?->toDateString(),
            'days_left' => $daysLeft,
            'is_ending_soon' => $user->hasFullAccess() && $daysLeft <= config('subscriptions.warning_days'),
        ];
    }
}
