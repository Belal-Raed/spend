@props(['title', 'debts', 'empty'])

<x-card :title="$title">
    @forelse ($debts as $debt)
        <div class="flex items-start justify-between gap-4 border-t border-gray-100 pt-3 first-of-type:border-t-0 first-of-type:pt-0">
            <div class="flex flex-col">
                <span class="font-medium">{{ $debt['counterparty'] }}</span>
                <span class="text-sm text-gray-600">{{ $debt['occurred_on'] }}</span>
            </div>

            <div class="flex flex-col items-end">
                <x-amount :value="$debt['remaining']" class="font-semibold" />

                @if ($debt['repaid'] > 0)
                    <span class="text-sm text-gray-600">من أصل <x-amount :value="$debt['amount']" /></span>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-600">{{ $empty }}</p>
    @endforelse
</x-card>
