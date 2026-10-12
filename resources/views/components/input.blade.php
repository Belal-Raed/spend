@props(['name', 'label', 'type' => 'text', 'value' => null])

<div class="flex flex-col gap-1">
    <label for="{{ $name }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        {{ $attributes->merge(['class' => 'rounded-xl border border-slate-300 bg-white px-3 py-2.5 outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20']) }}
    >

    @error($name)
        <p class="text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
