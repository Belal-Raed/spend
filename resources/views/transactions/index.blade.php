@use('App\Enums\Period')
@use('App\Enums\TransactionType')

<x-page title="الحركات">
    <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <x-select name="period" label="الفترة">
            <option value="">كل الفترات</option>
            @foreach (Period::cases() as $option)
                <option value="{{ $option->value }}" @selected($option === $period)>{{ $option->label() }}</option>
            @endforeach
        </x-select>

        <x-select name="category" label="التصنيف">
            <option value="">كل التصنيفات</option>
            @foreach ($categories as $type => $categoriesOfType)
                <optgroup label="{{ TransactionType::from($type)->label() }}">
                    @foreach ($categoriesOfType as $option)
                        <option value="{{ $option->id }}" @selected($option->is($category))>{{ $option->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </x-select>

        <x-button>فلترة</x-button>
    </form>

    <section class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
        @forelse ($transactions as $transaction)
            <article class="flex items-start justify-between gap-4 border-t border-gray-100 p-4 first:border-t-0">
                <div class="flex flex-col gap-1">
                    <span class="font-medium">
                        @include('transactions.kind', ['transaction' => $transaction])
                    </span>

                    @if ($transaction->note)
                        <span class="text-sm text-gray-600">{{ $transaction->note }}</span>
                    @endif

                    <span class="text-sm text-gray-600">
                        {{ $transaction->occurred_on->toDateString() }}
                        @if ($transaction->isDebt() && $transaction->isSettled())
                            · انسدّ
                        @endif
                    </span>
                </div>

                <div class="flex flex-col items-end gap-2">
                    <x-amount
                        :value="$transaction->addsToBalance() ? $transaction->amount : 0 - $transaction->amount"
                        @class(['font-semibold', 'text-green-700' => $transaction->addsToBalance(), 'text-red-700' => ! $transaction->addsToBalance()])
                    />

                    <div class="flex items-center gap-3 text-sm">
                        <a href="{{ route('transactions.edit', $transaction) }}" class="text-gray-600 underline hover:text-gray-900">تعديل</a>

                        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm('حذف هذه الحركة؟')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="cursor-pointer text-red-700 underline hover:text-red-900">حذف</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <p class="p-4 text-sm text-gray-600">لا توجد حركات.</p>
        @endforelse
    </section>

    @if ($transactions->hasPages())
        <nav class="flex items-center justify-between text-sm">
            @if ($transactions->onFirstPage())
                <span class="text-gray-400">الأحدث</span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}" class="underline hover:text-gray-900">الأحدث</a>
            @endif

            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}" class="underline hover:text-gray-900">الأقدم</a>
            @else
                <span class="text-gray-400">الأقدم</span>
            @endif
        </nav>
    @endif
</x-page>
