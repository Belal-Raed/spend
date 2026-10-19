@use('App\Enums\DebtDirection')
@use('App\Enums\TransactionType')

<x-page title="حركة جديدة" modal :cancel="route('transactions.index')" submit="transaction-form">
    <x-segmented
        label="نوع الحركة"
        :links="array_map(fn (TransactionType $option): array => [$option->label(), route('transactions.create', ['type' => $option->value]), $option === $type], TransactionType::cases())"
    />

    <form id="transaction-form" method="POST" action="{{ route('transactions.store') }}" class="flex flex-col gap-7">
        @csrf

        <input type="hidden" name="type" value="{{ $type->value }}">

        @error('type')
            <x-alert>{{ $message }}</x-alert>
        @enderror

        @if ($type === TransactionType::Debt)
            <x-segmented
                name="debt_direction"
                label="اتجاه الدين"
                :options="[DebtDirection::OwedByMe->value => 'عليّ (استدنت)', DebtDirection::OwedToMe->value => 'إلي (داينت)']"
            />
        @endif

        @include('transactions.fields', [
            'transaction' => null,
            'categories' => $categories,
            'counterpartyLabel' => match ($type) {
                TransactionType::Debt => 'مع مين',
                TransactionType::Income => 'من وين',
                TransactionType::Expense => null,
            },
            'counterpartyPlaceholder' => $type === TransactionType::Debt ? 'اسم الشخص' : 'اختياري',
        ])
    </form>
</x-page>
