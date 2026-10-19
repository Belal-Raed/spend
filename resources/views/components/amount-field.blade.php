{{-- The amount of a transaction, typed in large digits in its own block. --}}
@props(['name' => 'amount', 'label' => 'المبلغ', 'value' => null])

<section class="flex flex-col items-center gap-1 rounded-xl bg-surface px-4 py-5">
    <label for="{{ $name }}" class="text-[13px] text-muted">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="number"
        value="{{ old($name, $value) }}"
        step="0.01"
        min="0.01"
        inputmode="decimal"
        dir="ltr"
        placeholder="0.00"
        required
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->merge(['class' => 'w-full [appearance:textfield] bg-transparent text-center text-5xl font-bold tabular-nums outline-none placeholder:text-faint [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none']) }}
    >

    @error($name)
        <p class="text-[13px] text-negative">{{ $message }}</p>
    @enderror
</section>
