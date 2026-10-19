{{--
    One row of a grouped list. It is a link when given `href`, a button when
    given `button` (any attributes, such as data-sheet-open, pass through), and
    plain text otherwise. `icon` draws a coloured square at the start, and the
    `detail` slot sits at the end, before the chevron that links show.
--}}
@props(['href' => null, 'button' => false, 'icon' => null, 'tone' => 'bg-tint', 'chevron' => null, 'destructive' => false])

@php
    $tag = $href ? 'a' : ($button ? 'button' : 'div');
    $isTappable = $tag !== 'div';
    $showsChevron = $chevron ?? $href !== null;
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    @if ($button) type="button" @endif
    {{ $attributes->class([
        'ios-row flex w-full items-center gap-3 ps-4 text-start',
        'cursor-pointer transition-colors active:bg-pressed' => $isTappable,
        'text-negative' => $destructive,
    ]) }}
>
    @if ($icon)
        <span class="flex size-[30px] shrink-0 items-center justify-center rounded-[7px] text-white {{ $tone }}">
            <x-icon :name="$icon" class="size-[18px]" stroke-width="2" />
        </span>
    @endif

    <span class="ios-row-body flex min-h-11 min-w-0 flex-1 items-center gap-3 py-2.5 pe-4">
        <span class="flex min-w-0 flex-1 flex-col">{{ $slot }}</span>

        {{ $detail ?? '' }}

        @if ($showsChevron)
            <x-icon name="chevron-left" class="size-4 text-faint" stroke-width="2.5" />
        @endif
    </span>
</{{ $tag }}>
