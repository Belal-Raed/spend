@props(['name', 'label', 'type' => 'text'])

<div class="flex flex-col gap-1">
    <label for="{{ $name }}" class="text-sm font-medium">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        {{ $attributes->merge(['class' => 'rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900']) }}
    >

    @error($name)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
