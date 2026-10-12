@props(['title', 'debts', 'empty', 'actions' => false])

<x-card :title="$title">
    @forelse ($debts as $debt)
        <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 first-of-type:border-t-0 first-of-type:pt-0">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 flex-col">
                    <span class="truncate font-semibold">{{ $debt['counterparty'] }}</span>

                    @if ($actions && $debt['note'])
                        <span class="truncate text-xs text-slate-500">{{ $debt['note'] }}</span>
                    @endif

                    <span class="text-xs text-slate-500">{{ $debt['occurred_on'] }}</span>
                </div>

                <div class="flex shrink-0 flex-col items-end">
                    <x-amount :value="$debt['remaining']" class="font-bold" />

                    @if ($debt['repaid'] > 0)
                        <span class="text-xs text-slate-500">من أصل <x-amount :value="$debt['amount']" /></span>
                    @endif
                </div>
            </div>

            @if ($debt['repaid'] > 0)
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="نسبة المسدَّد" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($debt['repaid'] * 100 / $debt['amount']) }}">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ round($debt['repaid'] * 100 / $debt['amount']) }}%"></div>
                </div>
            @endif

            @if ($actions)
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <form method="POST" action="{{ route('debts.repayments.store', $debt['id']) }}" onsubmit="return confirm('تأكيد سداد الدين كاملًا؟')">
                        @csrf

                        <x-button class="px-3 py-1.5">
                            <x-icon name="check" class="size-4" />
                            انسدّ
                        </x-button>
                    </form>

                    <x-button variant="secondary" :href="route('debts.repayments.create', $debt['id'])" class="px-3 py-1.5">دفعة جزئية</x-button>
                    <x-button variant="secondary" :href="route('transactions.edit', $debt['id'])" class="px-3 py-1.5">تعديل</x-button>
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">{{ $empty }}</p>
    @endforelse
</x-card>
