<x-page title="الرئيسية">
    <section class="flex flex-col gap-5 rounded-3xl bg-linear-to-bl from-emerald-500 to-emerald-800 p-6 text-white shadow-lg">
        <div class="flex flex-col gap-1">
            <h2 class="text-sm font-medium text-emerald-100">الرصيد</h2>
            <x-amount :value="$balance['cash']" class="text-4xl font-bold" />
        </div>

        <dl class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-1 rounded-2xl bg-white/10 p-3">
                <dt class="text-xs text-emerald-100">ناقص الديون اللي عليّ</dt>
                <dd><x-amount :value="0 - $balance['owed_by_me']" class="text-lg font-semibold" /></dd>
            </div>
            <div class="flex flex-col gap-1 rounded-2xl bg-white/10 p-3">
                <dt class="text-xs text-emerald-100">زائد الديون اللي إلي</dt>
                <dd><x-amount :value="$balance['owed_to_me']" class="text-lg font-semibold" /></dd>
            </div>
        </dl>
    </section>

    <div class="grid grid-cols-2 gap-4">
        <x-card title="الرصيد بعد الديون">
            <x-amount :value="$balance['after_debts']" class="text-2xl font-bold" />
        </x-card>

        <x-card title="مصاريف هذا الشهر">
            <x-amount :value="$monthExpensesTotal" class="text-2xl font-bold text-rose-700" />
        </x-card>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <x-debt-list title="ديون عليّ" :debts="$debtsOwedByMe" empty="ما عليك ديون مفتوحة." />
        <x-debt-list title="ديون إلي" :debts="$debtsOwedToMe" empty="ما إلك ديون مفتوحة." />
    </div>

    <x-card title="آخر الحركات">
        <x-slot:action>
            <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline">كل الحركات</a>
        </x-slot:action>

        @forelse ($recentTransactions as $transaction)
            <x-transaction-item :transaction="$transaction" class="border-t border-slate-100 pt-3 first-of-type:border-t-0 first-of-type:pt-0" />
        @empty
            <p class="text-sm text-slate-500">لا توجد حركات بعد.</p>
        @endforelse
    </x-card>
</x-page>
