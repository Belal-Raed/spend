<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The bank transfer receipts users sent, for the admin to approve or reject.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'إشعار دفع';

    protected static ?string $pluralModelLabel = 'إشعارات الدفع';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Every user's payments: the admin reviews them all, so the tenant limit is lifted here.
     *
     * @return Builder<Payment>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->acrossTenants()->with(['user', 'plan']);
    }

    /**
     * How many receipts are waiting for review.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = Payment::query()->acrossTenants()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }
}
