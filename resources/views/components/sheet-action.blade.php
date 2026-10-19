{{--
    One choice in an action sheet. It is a link when given `href`, and
    otherwise a form that sends `method` to `action`.
--}}
@props(['href' => null, 'action' => null, 'method' => 'POST', 'destructive' => false])

@php
    $classes = 'block w-full cursor-pointer py-4 text-[19px] active:bg-pressed '.($destructive ? 'text-negative' : 'text-tint');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <form method="POST" action="{{ $action }}">
        @csrf

        @unless (strtoupper($method) === 'POST')
            @method($method)
        @endunless

        <button type="submit" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
    </form>
@endif
