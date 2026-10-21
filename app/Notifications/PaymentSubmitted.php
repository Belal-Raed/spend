<?php

namespace App\Notifications;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

/**
 * Tells the admins, in the admin panel's notifications, that a receipt is waiting for review.
 */
class PaymentSubmitted extends Notification
{
    public function __construct(public Payment $payment) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('إشعار تحويل جديد')
            ->body("{$this->payment->user->name} · {$this->payment->plan->name}")
            ->icon('heroicon-o-banknotes')
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('مراجعة')
                    ->url(PaymentResource::getUrl('view', ['record' => $this->payment], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
