{{-- A transaction as a row of a grouped list. Tapping it opens the transaction for editing. --}}
@props(['transaction', 'showDate' => false])

@php
    $addsToBalance = $transaction->addsToBalance();

    [$icon, $tone] = match (true) {
        $transaction->isDebt(), $transaction->isRepayment() => ['exchange', 'bg-warning'],
        $addsToBalance => ['arrow-down', 'bg-positive'],
        default => ['arrow-up', 'bg-negative'],
    };

    $details = array_filter([
        $showDate ? $transaction->occurred_on->toDateString() : null,
        $transaction->isDebt() && $transaction->isSettled() ? 'انسدّ' : null,
        $transaction->note,
    ]);
@endphp

<x-row :href="route('transactions.edit', $transaction)" :icon="$icon" :tone="$tone" :chevron="false" {{ $attributes }}>
    <span class="truncate">
        @include('transactions.kind', ['transaction' => $transaction])
    </span>

    @if ($details)
        <span class="truncate text-[13px] text-muted">{{ implode(' · ', $details) }}</span>
    @endif

    <x-slot:detail>
        <x-amount
            :value="$addsToBalance ? $transaction->amount : 0 - $transaction->amount"
            @class(['shrink-0 font-semibold', 'text-positive' => $addsToBalance])
        />
    </x-slot:detail>
</x-row>
