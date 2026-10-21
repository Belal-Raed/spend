<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * Counts the admin checks first: users by access, receipts to review and this month's income.
 */
class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $payments = Payment::query()->acrossTenants();

        return [
            Stat::make('المستخدمون', User::query()->count()),
            Stat::make('مشتركون الآن', User::query()->where('subscription_ends_at', '>', now())->count()),
            Stat::make('في فترة التجربة', User::query()
                ->where('trial_ends_at', '>', now())
                ->where(fn ($query) => $query->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now()))
                ->count()),
            Stat::make('إشعارات بانتظار المراجعة', (clone $payments)->pending()->count())
                ->color('warning'),
            Stat::make('دخل هذا الشهر', Number::format((float) (clone $payments)
                ->where('status', PaymentStatus::Approved)
                ->whereBetween('reviewed_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'), precision: 2))
                ->description('من الإشعارات المقبولة'),
        ];
    }
}
