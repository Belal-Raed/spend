{{-- A text field as a row of a grouped list: its label at the start and the field filling the rest. --}}
@props(['name', 'label', 'type' => 'text', 'value' => null])

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
                {{ $attributes->merge(['class' => 'min-w-0 flex-1 bg-transparent py-1.5 outline-none placeholder:text-faint']) }}
            >
        </span>

        @error($name)
            <span class="text-[13px] text-negative">{{ $message }}</span>
        @enderror
    </span>
</label>
