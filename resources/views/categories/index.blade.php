@use('App\Enums\TransactionType')

<x-page title="التصنيفات">
    @error('category')
        <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('categories.store') }}" class="flex flex-wrap items-start gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        @csrf

        <x-input name="name" label="تصنيف جديد" required />

        <x-select name="type" label="النوع" required>
            @foreach ([TransactionType::Expense, TransactionType::Income] as $option)
                <option value="{{ $option->value }}" @selected(old('type') === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-select>

        <x-button class="mt-6">إضافة</x-button>
    </form>

    @foreach (['تصنيفات المصاريف' => TransactionType::Expense, 'تصنيفات القبض' => TransactionType::Income] as $title => $type)
        <x-card :title="$title">
            @foreach ($categories->get($type->value, []) as $category)
                <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3 first-of-type:border-t-0 first-of-type:pt-0">
                    <span class="font-medium">{{ $category->name }}</span>

                    @if ($category->isDefault())
                        <span class="text-sm text-gray-500">افتراضي</span>
                    @else
                        <div class="flex items-center gap-3 text-sm">
                            <a href="{{ route('categories.edit', $category) }}" class="text-gray-600 underline hover:text-gray-900">تعديل</a>

                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('حذف هذا التصنيف؟')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="cursor-pointer text-red-700 underline hover:text-red-900">حذف</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </x-card>
    @endforeach
</x-page>
