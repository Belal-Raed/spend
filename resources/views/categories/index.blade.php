@use('App\Enums\TransactionType')

<x-page title="التصنيفات" :back="route('settings')" back-label="الإعدادات">
    <x-slot:trailing></x-slot:trailing>

    @error('category')
        <x-alert>{{ $message }}</x-alert>
    @enderror

    @foreach (['تصنيفات المصاريف' => TransactionType::Expense, 'تصنيفات القبض' => TransactionType::Income] as $title => $type)
        <x-group :title="$title" :footer="$loop->last ? 'التصنيفات الافتراضية مشتركة ولا تُعدَّل. اضغط على تصنيفك لتعديله أو حذفه.' : null">
            @foreach ($categories->get($type->value, []) as $category)
                @if ($category->isDefault())
                    <x-row>
                        {{ $category->name }}

                        <x-slot:detail>
                            <span class="text-[15px] text-muted">افتراضي</span>
                        </x-slot:detail>
                    </x-row>
                @else
                    <x-row :href="route('categories.edit', $category)">{{ $category->name }}</x-row>
                @endif
            @endforeach
        </x-group>
    @endforeach

    <form method="POST" action="{{ route('categories.store') }}" class="flex flex-col gap-3">
        @csrf

        <x-group title="تصنيف جديد">
            <x-input name="name" label="الاسم" placeholder="مثلًا: أقساط" required />
        </x-group>

        <x-segmented
            name="type"
            label="نوع التصنيف"
            :options="[TransactionType::Expense->value => 'مصروف', TransactionType::Income->value => 'قبض']"
            :value="TransactionType::Expense->value"
        />

        <x-button class="w-full">إضافة التصنيف</x-button>
    </form>
</x-page>
