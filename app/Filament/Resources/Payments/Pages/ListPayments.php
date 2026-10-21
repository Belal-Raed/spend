<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    /**
     * One tab per status, opening on the receipts waiting for review.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [];

        foreach (PaymentStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->label())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return [...$tabs, 'all' => Tab::make('الكل')];
    }

    public function getDefaultActiveTab(): string
    {
        return PaymentStatus::Pending->value;
    }
}
