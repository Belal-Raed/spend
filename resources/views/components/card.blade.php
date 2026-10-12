@props(['title' => null])

<section {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    @if ($title)
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-sm font-semibold text-slate-500">{{ $title }}</h2>

            {{ $action ?? '' }}
        </div>
    @endif

    {{ $slot }}
</section>
