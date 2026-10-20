@php
    // Each tab as [id, label, debts, message when there are none].
    $tabs = [
        ['owed-by-me', 'ديون عليّ', $debtsOwedByMe, 'ما عليك ديون مفتوحة.'],
        ['owed-to-me', 'ديون إلي', $debtsOwedToMe, 'ما إلك ديون مفتوحة.'],
    ];
@endphp

<x-page title="الديون">
    @error('amount')
        <x-alert>{{ $message }}</x-alert>
    @enderror

    {{-- The segmented control is a pair of radio buttons, so switching tabs needs no script. --}}
    <div class="group/debts flex flex-col gap-7">
        <div class="grid grid-cols-2 gap-0.5 rounded-[9px] bg-fill p-0.5 select-none" role="radiogroup" aria-label="اتجاه الدين">
            @foreach ($tabs as [$id, $label, $debts])
                <label class="flex min-h-8 cursor-pointer items-center justify-center gap-1.5 rounded-[7px] text-[13px] font-semibold transition has-checked:bg-segment has-checked:shadow-sm has-checked:shadow-black/10 has-focus-visible:ring-2 has-focus-visible:ring-tint">
                    <input type="radio" name="debts-tab" id="tab-{{ $id }}" class="sr-only" @checked($loop->first)>
                    {{ $label }}
                    <span class="font-normal tabular-nums text-muted">{{ count($debts) }}</span>
                </label>
            @endforeach
        </div>

        @foreach ($tabs as [$id, $label, $debts, $empty])
            <div @class([
                'flex flex-col gap-7',
                'group-has-[#tab-owed-to-me:checked]/debts:hidden' => $id === 'owed-by-me',
                'hidden group-has-[#tab-owed-to-me:checked]/debts:flex' => $id === 'owed-to-me',
            ])>
                <x-debt-list :debts="$debts" :empty="$empty" actions />
            </div>
        @endforeach
    </div>

    <p class="px-4 text-center text-[13px] text-muted">اضغط على الدين لتسديده كاملًا أو بدفعة جزئية.</p>
</x-page>
