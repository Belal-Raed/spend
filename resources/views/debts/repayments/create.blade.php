<x-page title="دفعة سداد">
    <form method="POST" action="{{ route('debts.repayments.store', $debt) }}" class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        @csrf

        <p class="text-sm text-gray-600">
            @include('transactions.kind', ['transaction' => $debt])
            · المتبقي <x-amount :value="$remaining" class="font-semibold text-gray-900" />
        </p>

        <x-input
            name="amount"
            type="number"
            label="مبلغ الدفعة"
            :value="$remaining"
            step="0.01"
            min="0.01"
            :max="$remaining"
            inputmode="decimal"
            dir="ltr"
            required
            autofocus
        />

        <x-input name="occurred_on" type="date" label="التاريخ" :value="today()->toDateString()" dir="ltr" required />
        <x-input name="note" label="ملاحظة (اختياري)" />

        <div class="flex items-center gap-4">
            <x-button>تسجيل الدفعة</x-button>
            <a href="{{ route('debts.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">إلغاء</a>
        </div>
    </form>
</x-page>
