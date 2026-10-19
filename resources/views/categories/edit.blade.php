<x-page title="تعديل التصنيف" modal :cancel="route('categories.index')" submit="category-form">
    <form id="category-form" method="POST" action="{{ route('categories.update', $category) }}">
        @csrf
        @method('PUT')

        <x-group :title="'تصنيف '.$category->type->label()">
            <x-input name="name" label="الاسم" :value="$category->name" required autofocus />
        </x-group>
    </form>

    <x-group footer="لا يمكن حذف تصنيف مستخدم في حركات.">
        <x-row button data-sheet-open="delete-category" destructive :chevron="false">
            <span class="text-center">حذف التصنيف</span>
        </x-row>
    </x-group>

    <x-action-sheet id="delete-category" :title="$category->name" message="لا يمكن التراجع عن الحذف.">
        <x-sheet-action :action="route('categories.destroy', $category)" method="DELETE" destructive>حذف التصنيف</x-sheet-action>
    </x-action-sheet>
</x-page>
