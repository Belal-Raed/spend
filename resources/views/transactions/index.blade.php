@use('App\Enums\Period')
@use('App\Enums\TransactionType')
@use('Illuminate\Support\Carbon')

@php
    $periodLinks = [['الكل', null], ...array_map(fn (Period $option): array => [$option->label(), $option], Period::cases())];

    $days = $transactions->getCollection()->groupBy(fn ($transaction): string => $transaction->occurred_on->toDateString());

    $dayTitle = function (string $date): string {
        $day = Carbon::parse($date)->locale('ar');

        return match (true) {
            $day->isToday() => 'اليوم',
            $day->isYesterday() => 'أمس',
            $day->isCurrentYear() => $day->translatedFormat('l j F'),
            default => $day->translatedFormat('l j F Y'),
        };
    };
@endphp

<x-page title="الحركات">
    <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-col gap-4">
        <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4" aria-label="الفترة">
            @foreach ($periodLinks as [$label, $option])
                <a
                    href="{{ route('transactions.index', array_filter(['period' => $option?->value, 'category' => $category?->id])) }}"
                    @class([
                        'shrink-0 rounded-full px-4 py-1.5 text-[15px] font-medium whitespace-nowrap transition active:opacity-60',
                        'bg-emerald-600 text-white' => $option === $period,
                        'bg-surface text-label' => $option !== $period,
                    ])
                    @if ($option === $period) aria-current="true" @endif
                >{{ $label }}</a>
            @endforeach
        </nav>

        @if ($period)
            <input type="hidden" name="period" value="{{ $period->value }}">
        @endif

        <x-group>
            <x-select name="category" label="التصنيف" data-auto-submit>
                <option value="">كل التصنيفات</option>
                @foreach ($categories as $type => $categoriesOfType)
                    <optgroup label="{{ TransactionType::from($type)->label() }}">
                        @foreach ($categoriesOfType as $option)
                            <option value="{{ $option->id }}" @selected($option->is($category))>{{ $option->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </x-select>
        </x-group>

        <noscript>
            <x-button class="w-full">فلترة</x-button>
        </noscript>
    </form>

    @forelse ($days as $date => $transactionsOfDay)
        <x-group :title="$dayTitle($date)">
            @foreach ($transactionsOfDay as $transaction)
                <x-transaction-item :transaction="$transaction" />
            @endforeach
        </x-group>
    @empty
        <div class="flex flex-col items-center gap-2 py-16 text-center">
            <x-icon name="list" class="size-12 text-faint" />
            <p class="text-[17px] font-semibold">لا توجد حركات.</p>
            <p class="text-[15px] text-muted">اضغط + فوق لتسجيل أول حركة.</p>
        </div>
    @endforelse

    @if ($transactions->hasPages())
        <nav class="grid grid-cols-2 gap-3" aria-label="الصفحات">
            @if ($transactions->onFirstPage())
                <span class="rounded-xl bg-surface py-3 text-center text-faint">الأحدث</span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}" class="rounded-xl bg-surface py-3 text-center text-tint active:bg-pressed">الأحدث</a>
            @endif

            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}" class="rounded-xl bg-surface py-3 text-center text-tint active:bg-pressed">الأقدم</a>
            @else
                <span class="rounded-xl bg-surface py-3 text-center text-faint">الأقدم</span>
            @endif
        </nav>
    @endif
</x-page>
