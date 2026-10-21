<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The plans users can pay for and their prices.
 *
 * Plans are never deleted, since past payments point at them; the admin
 * stops offering one instead.
 */
class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'باقة';

    protected static ?string $pluralModelLabel = 'الباقات';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
                TextInput::make('months')
                    ->label('المدة بالأشهر')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(36),
                TextInput::make('price')
                    ->label('السعر')
                    ->helperText('اتركه فارغًا لتظهر الباقة «قريبًا» ولا تُشترى.')
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(99999999.99),
                Toggle::make('is_active')
                    ->label('معروضة للمستخدمين')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('months')
            ->columns([
                TextColumn::make('name')->label('الاسم'),
                TextColumn::make('months')->label('المدة بالأشهر'),
                TextColumn::make('price')->label('السعر')->numeric(decimalPlaces: 2)->placeholder('بدون سعر'),
                IconColumn::make('is_active')->label('معروضة')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }
}
