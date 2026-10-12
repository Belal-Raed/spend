@props(['title'])

<section {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm']) }}>
    <h2 class="text-sm font-medium text-gray-600">{{ $title }}</h2>

    {{ $slot }}
</section>
