<x-page title="الرئيسية">
    <x-card title="الرصيد">
        <x-amount :value="$balance['cash']" class="text-3xl font-semibold" />

        <dl class="flex flex-col gap-2 border-t border-gray-100 pt-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-gray-600">ناقص الديون اللي عليّ</dt>
                <dd><x-amount :value="0 - $balance['owed_by_me']" /></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-600">زائد الديون اللي إلي</dt>
                <dd><x-amount :value="$balance['owed_to_me']" /></dd>
            </div>
            <div class="flex justify-between gap-4 border-t border-gray-100 pt-2 text-base font-semibold">
                <dt>الرصيد بعد الديون</dt>
                <dd><x-amount :value="$balance['after_debts']" /></dd>
            </div>
        </dl>
    </x-card>

    <x-card title="مصاريف هذا الشهر">
        <x-amount :value="$monthExpensesTotal" class="text-2xl font-semibold" />
    </x-card>

    <x-debt-list title="ديون عليّ" :debts="$debtsOwedByMe" empty="ما عليك ديون مفتوحة." />
    <x-debt-list title="ديون إلي" :debts="$debtsOwedToMe" empty="ما إلك ديون مفتوحة." />
</x-page>
