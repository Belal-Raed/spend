{{--
    A text field as a row of a grouped list: its label at the start and the
    field filling the rest. A date shows as a small grey pill at the end of
    the row instead, as on iOS.
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null])

@php
    // The date field is left-to-right inside the right-to-left page, so its
    // push to the end of the row is a physical left margin, not ms-auto.
    $fieldClasses = $type === 'date'
        ? 'mr-auto cursor-pointer rounded-lg bg-fill px-2.5 py-1 tabular-nums outline-none'
        : 'min-w-0 flex-1 bg-transparent py-1.5 outline-none placeholder:text-faint';
@endphp

<label class="ios-row flex w-full items-center gap-3 ps-4">
    <span class="ios-row-body flex min-h-11 min-w-0 flex-1 flex-col justify-center gap-0.5 py-1.5 pe-4">
        <span class="flex items-center gap-3">
            <span class="w-28 shrink-0">{{ $label }}</span>

            <input
                id="{{ $name }}"
                name="{{ $name }}"
                type="{{ $type }}"
                @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
                @error($name) aria-invalid="true" @enderror
                {{ $attributes->merge(['class' => $fieldClasses]) }}
            >
        </span>

        @error($name)
            <span class="text-[13px] text-negative">{{ $message }}</span>
        @enderror
    </span>
</label>
