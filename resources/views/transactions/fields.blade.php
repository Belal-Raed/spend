{{--
    The fields shared by the create and edit forms.

    $transaction is null on the create form. $categories is empty when the
    transaction carries no category, and $counterpartyLabel is null when it
    carries no counterparty.
--}}

<x-amount-field :value="$transaction?->amount" :autofocus="$transaction === null" />

<x-group>
    @if ($categories->isNotEmpty())
        <x-select name="category_id" label="التصنيف" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $transaction?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-select>
    @endif

    @if ($counterpartyLabel !== null)
        <x-input name="counterparty" :label="$counterpartyLabel" :value="$transaction?->counterparty" :placeholder="$counterpartyPlaceholder" />
    @endif

    <x-input
        name="occurred_on"
        type="date"
        label="التاريخ"
        :value="($transaction?->occurred_on ?? today())->toDateString()"
        dir="ltr"
        class="text-end"
        required
    />

    <x-input name="note" label="ملاحظة" :value="$transaction?->note" placeholder="اختياري" />
</x-group>
