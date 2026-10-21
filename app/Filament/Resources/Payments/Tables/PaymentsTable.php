<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->description(fn ($record): ?string => $record->user->phone)
                    ->searchable(['name', 'phone']),
                TextColumn::make('plan.name')
                    ->label('الباقة'),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Approved => 'success',
                        PaymentStatus::Rejected => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('أُرسل في')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                PaymentActions::approve(),
                PaymentActions::reject(),
                ViewAction::make(),
            ]);
    }
}
