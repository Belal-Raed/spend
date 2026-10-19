@props(['variant' => 'primary', 'href' => null])

@php
    $classes = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-[17px] font-semibold transition select-none active:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-tint focus-visible:ring-offset-2 focus-visible:ring-offset-canvas '.match ($variant) {
        'primary' => 'bg-tint text-white',
        'secondary' => 'bg-fill text-tint',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
