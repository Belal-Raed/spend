@use('App\Enums\DebtDirection')
@use('App\Enums\TransactionType')

<x-page title="حركة جديدة">
    <nav class="flex gap-2">
        @foreach (TransactionType::cases() as $option)
            <a
                href="{{ route('transactions.create', ['type' => $option->value]) }}"
                @class([
                    'rounded-lg border px-4 py-2 text-sm font-medium',
                    'border-gray-900 bg-gray-900 text-white' => $option === $type,
                    'border-gray-300 bg-white text-gray-600 hover:text-gray-900' => $option !== $type,
                ])
                @if ($option === $type) aria-current="page" @endif
            >{{ $option->label() }}</a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('transactions.store') }}" class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        @csrf

        <input type="hidden" name="type" value="{{ $type->value }}">

        @if ($type === TransactionType::Debt)
            <fieldset class="flex flex-col gap-1">
                <legend class="text-sm font-medium">اتجاه الدين</legend>

                <div class="flex gap-4 pt-1">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="debt_direction" value="{{ DebtDirection::OwedByMe->value }}" @checked(old('debt_direction') === DebtDirection::OwedByMe->value) required>
                        عليّ (استدنت)
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="debt_direction" value="{{ DebtDirection::OwedToMe->value }}" @checked(old('debt_direction') === DebtDirection::OwedToMe->value) required>
                        إلي (داينت)
                    </label>
                </div>

                @error('debt_direction')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>
        @endif

        @include('transactions.fields', [
            'transaction' => null,
            'categories' => $categories,
            'counterpartyLabel' => match ($type) {
                TransactionType::Debt => 'مع مين',
                TransactionType::Income => 'من وين (اختياري)',
                TransactionType::Expense => null,
            },
        ])

        @error('type')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <x-button>حفظ</x-button>
    </form>
</x-page>
