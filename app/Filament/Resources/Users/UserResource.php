<?php

namespace App\Filament\Resources\Users;

use App\Enums\AccessStatus;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\PaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Users' accounts and subscriptions.
 *
 * Only account details are shown: users' transactions and categories stay
 * private to them, even from the admin.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الجوال')
                    ->searchable()
                    ->placeholder('تيليجرام فقط'),
                TextColumn::make('access_status')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (User $record): AccessStatus => $record->accessStatus())
                    ->formatStateUsing(fn (AccessStatus $state): string => $state->label())
                    ->color(fn (AccessStatus $state): string => match ($state) {
                        AccessStatus::Subscribed => 'success',
                        AccessStatus::Trial => 'info',
                        AccessStatus::ReadOnly => 'warning',
                    }),
                TextColumn::make('access_ends_at')
                    ->label('ينتهي الوصول')
                    ->state(fn (User $record) => $record->accessEndsAt())
                    ->date('Y-m-d'),
                TextColumn::make('created_at')
                    ->label('سجّل في')
                    ->date('Y-m-d')
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->label('أدمن')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedShieldCheck)
                    ->falseIcon(null),
            ])
            ->filters([
                SelectFilter::make('access')
                    ->label('الحالة')
                    ->options(collect(AccessStatus::cases())->mapWithKeys(fn (AccessStatus $status): array => [$status->value => $status->label()]))
                    ->query(fn (Builder $query, array $data) => match (AccessStatus::tryFrom((string) $data['value'])) {
                        AccessStatus::Subscribed => $query->where('subscription_ends_at', '>', now()),
                        AccessStatus::Trial => $query->where('trial_ends_at', '>', now())
                            ->where(fn (Builder $query) => $query->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now())),
                        AccessStatus::ReadOnly => $query
                            ->where(fn (Builder $query) => $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now()))
                            ->where(fn (Builder $query) => $query->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now())),
                        null => $query,
                    }),
            ])
            ->recordActions([
                Action::make('grantDays')
                    ->label('إضافة أيام')
                    ->icon(Heroicon::OutlinedGift)
                    ->modalHeading(fn (User $record): string => "إضافة أيام لـ {$record->name}")
                    ->modalDescription('تُضاف بعد نهاية التجربة أو الاشتراك الحالي، أو من اليوم إن كان الحساب منتهيًا.')
                    ->modalSubmitActionLabel('إضافة')
                    ->schema([
                        TextInput::make('days')
                            ->label('عدد الأيام')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(365),
                    ])
                    ->action(function (User $record, array $data, PaymentService $payments): void {
                        $payments->grantDays($record, (int) $data['days']);

                        Notification::make()->title('تمت إضافة الأيام.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
