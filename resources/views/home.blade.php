<x-page title="الرئيسية" :subtitle="'أهلًا، '.auth()->user()->name">
    <section class="flex flex-col gap-5 rounded-2xl bg-linear-to-bl from-emerald-500 to-emerald-800 p-5 text-white shadow-lg shadow-emerald-900/20">
        <div class="flex flex-col gap-1">
            <h2 class="text-[15px] font-medium text-white/75">الرصيد</h2>
            <x-amount :value="$balance['cash']" class="text-[40px] leading-tight font-bold" />
        </div>

        <dl class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-0.5 rounded-xl bg-white/12 px-3 py-2.5">
                <dt class="text-[13px] text-white/75">ناقص الديون اللي عليّ</dt>
                <dd><x-amount :value="0 - $balance['owed_by_me']" class="text-[17px] font-semibold" /></dd>
            </div>
            <div class="flex flex-col gap-0.5 rounded-xl bg-white/12 px-3 py-2.5">
                <dt class="text-[13px] text-white/75">زائد الديون اللي إلي</dt>
                <dd><x-amount :value="$balance['owed_to_me']" class="text-[17px] font-semibold" /></dd>
            </div>
        </dl>
    </section>

    <dl class="grid grid-cols-2 gap-3">
        <div class="flex flex-col gap-1 rounded-xl bg-surface p-4">
            <dt class="text-[13px] text-muted">الرصيد بعد الديون</dt>
            <dd><x-amount :value="$balance['after_debts']" class="text-[22px] font-bold" /></dd>
        </div>
        <div class="flex flex-col gap-1 rounded-xl bg-surface p-4">
            <dt class="text-[13px] text-muted">مصاريف هذا الشهر</dt>
            <dd><x-amount :value="$monthExpensesTotal" class="text-[22px] font-bold text-negative" /></dd>
        </div>
    </dl>

    <x-debt-list title="ديون عليّ" :debts="$debtsOwedByMe" empty="ما عليك ديون مفتوحة." />
    <x-debt-list title="ديون إلي" :debts="$debtsOwedToMe" empty="ما إلك ديون مفتوحة." />

    <x-group title="آخر الحركات">
        <x-slot:action>
            <a href="{{ route('transactions.index') }}" class="text-[15px] text-tint active:opacity-50">عرض الكل</a>
        </x-slot:action>

        @forelse ($recentTransactions as $transaction)
            <x-transaction-item :transaction="$transaction" show-date />
        @empty
            <x-row>
                <span class="text-muted">لا توجد حركات بعد.</span>
            </x-row>
        @endforelse
    </x-group>
</x-page>
