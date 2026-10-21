<?php

namespace App\Filament\Resources\Payments;

use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * The approve and reject buttons, shared by the payments table and a payment's page.
 */
class PaymentActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('قبول')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Payment $record): bool => $record->isPending())
            ->requiresConfirmation()
            ->modalHeading('قبول الإشعار')
            ->modalDescription(fn (Payment $record): string => "سيُمدَّد اشتراك {$record->user->name} {$record->plan->name}. تأكد أن المبلغ وصل الحساب.")
            ->modalSubmitActionLabel('قبول')
            ->action(function (Payment $record, PaymentService $payments): void {
                $payments->approve($record);

                Notification::make()->title('تم قبول الإشعار وتفعيل الاشتراك.')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Payment $record): bool => $record->isPending())
            ->modalHeading('رفض الإشعار')
            ->modalSubmitActionLabel('رفض')
            ->schema([
                Textarea::make('reason')
                    ->label('سبب الرفض')
                    ->helperText('يظهر للمستخدم في صفحة الاشتراك.')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Payment $record, array $data, PaymentService $payments): void {
                $payments->reject($record, $data['reason']);

                Notification::make()->title('تم رفض الإشعار.')->success()->send();
            });
    }
}
