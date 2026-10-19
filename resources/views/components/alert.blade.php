{{-- A problem the user needs to see, such as a category that could not be deleted. --}}
<p role="alert" {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-xl bg-negative/10 px-4 py-3 text-[15px] text-negative']) }}>
    <x-icon name="exclamation" class="mt-0.5 size-5" />
    <span>{{ $slot }}</span>
</p>
