@props(['transaction'])

@php
    $addsToBalance = $transaction->addsToBalance();

    [$icon, $tone] = match (true) {
        $transaction->isDebt(), $transaction->isRepayment() => ['exchange', 'bg-amber-50 text-amber-700'],
        $addsToBalance => ['arrow-down', 'bg-emerald-50 text-emerald-700'],
        default => ['arrow-up', 'bg-rose-50 text-rose-700'],
    };
@endphp

<article {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $tone }}">
        <x-icon :name="$icon" />
    </span>

    <div class="flex min-w-0 flex-1 flex-col">
        <span class="truncate font-medium">
            @include('transactions.kind', ['transaction' => $transaction])
        </span>

        <span class="truncate text-xs text-slate-500">
            {{ $transaction->occurred_on->toDateString() }}
            @if ($transaction->isDebt() && $transaction->isSettled())
                · انسدّ
            @endif
            @if ($transaction->note)
                · {{ $transaction->note }}
            @endif
        </span>
    </div>

    <div class="flex shrink-0 flex-col items-end gap-1">
        <x-amount
            :value="$addsToBalance ? $transaction->amount : 0 - $transaction->amount"
            @class(['font-semibold', 'text-emerald-700' => $addsToBalance, 'text-rose-700' => ! $addsToBalance])
        />

        {{ $slot }}
    </div>
</article>
