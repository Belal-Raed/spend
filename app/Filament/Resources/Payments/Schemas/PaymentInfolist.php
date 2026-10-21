<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A payment's details beside its receipt.
 *
 * The receipt lives on the private disk, so it is shown through a link that
 * expires after a few minutes rather than a public URL.
 */
class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الإشعار')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label('المستخدم'),
                        TextEntry::make('user.phone')->label('الجوال'),
                        TextEntry::make('plan.name')->label('الباقة'),
                        TextEntry::make('amount')->label('المبلغ')->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                        TextEntry::make('created_at')->label('أُرسل في')->dateTime('Y-m-d H:i'),
                        TextEntry::make('reviewed_at')->label('رُوجع في')->dateTime('Y-m-d H:i')->placeholder('لم يُراجع بعد'),
                        TextEntry::make('rejection_reason')->label('سبب الرفض')->visible(fn (Payment $record): bool => $record->rejection_reason !== null),
                    ]),
                Section::make('صورة التحويل')
                    ->schema([
                        ImageEntry::make('receipt_path')
                            ->hiddenLabel()
                            ->disk('local')
                            ->visibility('private')
                            ->imageHeight(480)
                            ->visible(fn (Payment $record): bool => ! self::isPdf($record)),
                        TextEntry::make('receipt_path')
                            ->hiddenLabel()
                            ->formatStateUsing(fn (): string => 'فتح ملف الإشعار (PDF)')
                            ->url(fn (Payment $record): string => Storage::disk('local')->temporaryUrl($record->receipt_path, now()->addMinutes(10)))
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->visible(fn (Payment $record): bool => self::isPdf($record)),
                    ]),
            ]);
    }

    private static function isPdf(Payment $payment): bool
    {
        return Str::endsWith(Str::lower($payment->receipt_path), '.pdf');
    }
}
