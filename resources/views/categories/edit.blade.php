<x-page title="تعديل التصنيف">
    <form method="POST" action="{{ route('categories.update', $category) }}" class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        @csrf
        @method('PUT')

        <p class="text-sm text-gray-600">تصنيف {{ $category->type->label() }}</p>

        <x-input name="name" label="الاسم" :value="$category->name" required autofocus />

        <div class="flex items-center gap-4">
            <x-button>حفظ التعديل</x-button>
            <a href="{{ route('categories.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">إلغاء</a>
        </div>
    </form>
</x-page>
