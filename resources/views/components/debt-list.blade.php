{{--
    Open debts as a grouped list, each with what is left on it. With `actions`,
    tapping a debt opens a sheet to settle it, pay part of it, or edit it;
    without, it links to the debts screen.
--}}
@props(['title' => null, 'debts', 'empty', 'actions' => false])

@use('App\Services\Money')

@php
    $remainingInCents = array_sum(array_map(fn (array $debt): int => Money::toCents($debt['remaining']), $debts));
@endphp

<x-group :title="$title" :footer="$debts ? 'المجموع المتبقي: '.number_format(Money::fromCents($remainingInCents), 2) : null">
    @forelse ($debts as $debt)
        @php($percentRepaid = round($debt['repaid'] * 100 / $debt['amount']))

        <x-row
            :href="$actions ? null : route('debts.index')"
            :button="$actions"
            :chevron="true"
            :data-sheet-open="$actions ? 'debt-'.$debt['id'] : null"
        >
            <span class="truncate font-medium">{{ $debt['counterparty'] }}</span>

            <span class="truncate text-[13px] text-muted">
                {{ $debt['occurred_on'] }}
                @if ($debt['note'])
                    · {{ $debt['note'] }}
                @endif
            </span>

            @if ($debt['repaid'] > 0)
                <span class="mt-2 h-1 overflow-hidden rounded-full bg-fill" role="progressbar" aria-label="نسبة المسدَّد" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentRepaid }}">
                    <span class="block h-full rounded-full bg-positive" style="width: {{ $percentRepaid }}%"></span>
                </span>
            @endif

            <x-slot:detail>
                <span class="flex shrink-0 flex-col items-end">
                    <x-amount :value="$debt['remaining']" class="font-semibold" />

                    @if ($debt['repaid'] > 0)
                        <span class="text-[13px] text-muted">من أصل <x-amount :value="$debt['amount']" /></span>
                    @endif
                </span>
            </x-slot:detail>
        </x-row>
    @empty
        <x-row>
            <span class="text-muted">{{ $empty }}</span>
        </x-row>
    @endforelse
</x-group>

@if ($actions)
    @foreach ($debts as $debt)
        <x-action-sheet :id="'debt-'.$debt['id']" :title="$debt['counterparty']" :message="'المتبقي '.number_format($debt['remaining'], 2)">
            <x-sheet-action :action="route('debts.repayments.store', $debt['id'])">انسدّ، سداد كامل</x-sheet-action>
            <x-sheet-action :href="route('debts.repayments.create', $debt['id'])">دفعة جزئية</x-sheet-action>
            <x-sheet-action :href="route('transactions.edit', $debt['id'])">تعديل الدين</x-sheet-action>
        </x-action-sheet>
    @endforeach
@endif
