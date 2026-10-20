@use('App\Enums\DebtDirection')

{{-- Cancelling goes back to the debts tab this debt is listed under. --}}
<x-page
    title="دفعة سداد"
    modal
    :cancel="route('debts.index').($debt->debt_direction === DebtDirection::OwedToMe ? '#owed-to-me' : '')"
    submit="repayment-form"
    submit-label="تسجيل"
>
    <form id="repayment-form" method="POST" action="{{ route('debts.repayments.store', $debt) }}" class="flex flex-col gap-7">
        @csrf

        <p class="-mb-4 text-center text-[15px] text-muted">
            @include('transactions.kind', ['transaction' => $debt])
            · المتبقي <x-amount :value="$remaining" class="font-semibold text-label" />
        </p>

        <x-amount-field label="مبلغ الدفعة" :value="$remaining" :max="$remaining" autofocus />

        <x-group>
            <x-input name="occurred_on" type="date" label="التاريخ" :value="today()->toDateString()" dir="ltr" required />
            <x-input name="note" label="ملاحظة" placeholder="اختياري" />
        </x-group>
    </form>
</x-page>
