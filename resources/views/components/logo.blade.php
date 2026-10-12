<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-lg font-bold text-slate-900']) }}>
    <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
        <x-icon name="wallet" />
    </span>

    {{ config('app.name') }}
</span>
