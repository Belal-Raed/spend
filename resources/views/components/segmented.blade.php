{{--
    An iOS segmented control. With `name` it is a set of radio buttons for a
    form, with `options` as [value => label] and `value` checked. Without a
    name it switches between pages, with `links` as [[label, href, isActive]].
    `title` shows a small heading above it.
--}}
@props(['name' => null, 'options' => [], 'value' => null, 'links' => [], 'label' => null, 'title' => null])

@php
    $segment = 'flex min-h-8 cursor-pointer items-center justify-center rounded-[7px] px-2 text-center text-[13px] font-semibold transition';
    $selected = 'bg-segment shadow-sm shadow-black/10';
@endphp

<div class="flex flex-col gap-1.5">
    @if ($title)
        <p class="px-4 text-[13px] text-muted" aria-hidden="true">{{ $title }}</p>
    @endif

    <div
        {{ $attributes->merge(['class' => 'grid auto-cols-fr grid-flow-col gap-0.5 rounded-[9px] bg-fill p-0.5 select-none']) }}
        @if ($name) role="radiogroup" @endif
        @if ($label) aria-label="{{ $label }}" @endif
    >
        @if ($name)
            @foreach ($options as $optionValue => $optionLabel)
                <label class="{{ $segment }} has-checked:bg-segment has-checked:shadow-sm has-checked:shadow-black/10 has-focus-visible:ring-2 has-focus-visible:ring-tint">
                    <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" class="sr-only" @checked((string) old($name, $value) === (string) $optionValue) required>
                    {{ $optionLabel }}
                </label>
            @endforeach
        @else
            @foreach ($links as [$linkLabel, $href, $isActive])
                <a href="{{ $href }}" @class([$segment, $selected => $isActive]) @if ($isActive) aria-current="page" @endif>{{ $linkLabel }}</a>
            @endforeach
        @endif
    </div>

    @if ($name)
        @error($name)
            <p class="px-4 text-[13px] text-negative">{{ $message }}</p>
        @enderror
    @endif
</div>
