{{-- A choice as a row of a grouped list: its label at the start and the chosen option at the end. --}}
@props(['name', 'label'])

<label class="ios-row flex w-full cursor-pointer items-center gap-3 ps-4 transition-colors active:bg-pressed">
    <span class="ios-row-body flex min-h-11 min-w-0 flex-1 flex-col justify-center gap-0.5 py-1.5 pe-4">
        <span class="flex items-center gap-3">
            <span class="flex-1">{{ $label }}</span>

            <span class="relative flex min-w-0 items-center gap-1 text-muted">
                <select
                    id="{{ $name }}"
                    name="{{ $name }}"
                    @error($name) aria-invalid="true" @enderror
                    {{ $attributes->merge(['class' => 'max-w-48 min-w-0 cursor-pointer appearance-none truncate bg-transparent py-1.5 text-end outline-none']) }}
                >
                    {{ $slot }}
                </select>

                <x-icon name="chevron-up-down" class="pointer-events-none size-4" stroke-width="2" />
            </span>
        </span>

        @error($name)
            <span class="text-[13px] text-negative">{{ $message }}</span>
        @enderror
    </span>
</label>
