@use('App\Enums\TransactionType')

<x-page title="تعديل الحركة">
    <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        @csrf
        @method('PUT')

        <p class="text-sm text-gray-600">
            @include('transactions.kind', ['transaction' => $transaction])
        </p>

        @include('transactions.fields', [
            'transaction' => $transaction,
            'categories' => $transaction->isGroupedByCategory() ? $categories : collect(),
            'counterpartyLabel' => match (true) {
                $transaction->isDebt() => 'مع مين',
                $transaction->type === TransactionType::Income && ! $transaction->isRepayment() => 'من وين (اختياري)',
                default => null,
            },
        ])

        <div class="flex items-center gap-4">
            <x-button>حفظ التعديل</x-button>
            <a href="{{ route('transactions.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">إلغاء</a>
        </div>
    </form>
</x-page>
