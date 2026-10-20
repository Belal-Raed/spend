<x-page title="الإعدادات">
    <x-slot:trailing></x-slot:trailing>

    <section class="flex items-center gap-4 rounded-xl bg-surface p-4">
        <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-linear-to-bl from-emerald-400 to-emerald-700 text-2xl font-semibold text-white" aria-hidden="true">
            {{ mb_substr($user->name, 0, 1) }}
        </span>

        <div class="flex min-w-0 flex-col">
            <span class="truncate text-[20px] font-semibold">{{ $user->name }}</span>

            @if ($user->phone)
                <span dir="ltr" class="self-start text-[15px] text-muted tabular-nums">{{ $user->phone }}</span>
            @endif
        </div>
    </section>

    {{-- The choice is saved on this device by app.js, so the form is never submitted. --}}
    <x-segmented
        name="theme"
        label="المظهر"
        title="المظهر"
        :options="['auto' => 'تلقائي', 'light' => 'فاتح', 'dark' => 'داكن']"
        value="auto"
    />

    <x-group>
        <x-row :href="route('categories.index')" icon="tag" tone="bg-warning">
            التصنيفات

            <x-slot:detail>
                @if ($customCategoriesCount > 0)
                    <span class="text-muted tabular-nums">{{ $customCategoriesCount }}</span>
                @endif
            </x-slot:detail>
        </x-row>
    </x-group>

    <x-group>
        <x-row button data-sheet-open="logout-sheet" destructive :chevron="false">
            <span class="text-center">تسجيل الخروج</span>
        </x-row>
    </x-group>

    <x-action-sheet id="logout-sheet" message="تريد تسجيل الخروج من مصاريفي؟">
        <x-sheet-action :action="route('logout')" destructive>تسجيل الخروج</x-sheet-action>
    </x-action-sheet>
</x-page>
