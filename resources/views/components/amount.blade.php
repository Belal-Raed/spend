@props(['value'])

{{-- Numbers stay left-to-right so a minus sign is not moved to the wrong side in the RTL page. --}}
<span dir="ltr" {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ number_format($value, 2) }}</span>
