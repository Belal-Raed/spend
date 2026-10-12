@props(['title', 'debts', 'empty', 'actions' => false])

<x-card :title="$title">
    @forelse ($debts as $debt)
        <div class="flex items-start justify-between gap-4 border-t border-gray-100 pt-3 first-of-type:border-t-0 first-of-type:pt-0">
            <div class="flex flex-col">
                <span class="font-medium">{{ $debt['counterparty'] }}</span>

                @if ($actions && $debt['note'])
                    <span class="text-sm text-gray-600">{{ $debt['note'] }}</span>
                @endif

                <span class="text-sm text-gray-600">{{ $debt['occurred_on'] }}</span>
            </div>

            <div class="flex flex-col items-end gap-1">
                <x-amount :value="$debt['remaining']" class="font-semibold" />

                @if ($debt['repaid'] > 0)
                    <span class="text-sm text-gray-600">من أصل <x-amount :value="$debt['amount']" /></span>
                @endif

                @if ($actions)
                    <div class="flex items-center gap-3 pt-1 text-sm">
                        <form method="POST" action="{{ route('debts.repayments.store', $debt['id']) }}" onsubmit="return confirm('تأكيد سداد الدين كاملًا؟')">
                            @csrf

                            <button type="submit" class="cursor-pointer font-medium text-green-700 underline hover:text-green-900">انسدّ</button>
                        </form>

                        <a href="{{ route('debts.repayments.create', $debt['id']) }}" class="text-gray-600 underline hover:text-gray-900">دفعة جزئية</a>
                        <a href="{{ route('transactions.edit', $debt['id']) }}" class="text-gray-600 underline hover:text-gray-900">تعديل</a>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-600">{{ $empty }}</p>
    @endforelse
</x-card>
