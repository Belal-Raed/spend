{{-- A rounded block of rows with an optional heading and note, like the iOS Settings app. --}}
@props(['title' => null, 'footer' => null])

<section class="flex flex-col gap-1.5">
    @if ($title || isset($action))
        <div class="flex items-end justify-between gap-4 px-4">
            @if ($title)
                <h2 class="text-[13px] text-muted">{{ $title }}</h2>
            @endif

            {{ $action ?? '' }}
        </div>
    @endif

    <div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl bg-surface']) }}>
        {{ $slot }}
    </div>

    @if ($footer)
        <p class="px-4 text-[13px] text-muted">{{ $footer }}</p>
    @endif
</section>
