@use('App\Enums\TransactionType')

<x-page title="تعديل الحركة" modal :cancel="route('transactions.index')" submit="transaction-form">
    <form id="transaction-form" method="POST" action="{{ route('transactions.update', $transaction) }}" class="flex flex-col gap-7">
        @csrf
        @method('PUT')

        <p class="-mb-4 text-center text-[15px] text-muted">
            @include('transactions.kind', ['transaction' => $transaction])
        </p>

        @include('transactions.fields', [
            'transaction' => $transaction,
            'categories' => $transaction->isGroupedByCategory() ? $categories : collect(),
            'counterpartyLabel' => match (true) {
                $transaction->isDebt() => 'مع مين',
                $transaction->type === TransactionType::Income && ! $transaction->isRepayment() => 'من وين',
                default => null,
            },
            'counterpartyPlaceholder' => $transaction->isDebt() ? 'اسم الشخص' : 'اختياري',
        ])
    </form>

    <x-group>
        <x-row button data-sheet-open="delete-transaction" destructive :chevron="false">
            <span class="text-center">حذف الحركة</span>
        </x-row>
    </x-group>

    <x-action-sheet
        id="delete-transaction"
        :message="$transaction->isDebt() ? 'ستُحذف معه كل دفعات السداد المسجّلة عليه.' : 'لا يمكن التراجع عن الحذف.'"
    >
        <x-sheet-action :action="route('transactions.destroy', $transaction)" method="DELETE" destructive>حذف الحركة</x-sheet-action>
    </x-action-sheet>
</x-page>
