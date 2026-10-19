<x-auth-card title="تسجيل الدخول">
    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
        @csrf

        <x-group>
            <x-input name="phone" type="tel" label="رقم الجوال" dir="ltr" class="text-end" autocomplete="username" placeholder="0599123456" required autofocus />
            <x-input name="password" type="password" label="كلمة المرور" dir="ltr" class="text-end" autocomplete="current-password" required />

            <label class="ios-row flex w-full cursor-pointer items-center ps-4">
                <span class="ios-row-body flex min-h-11 flex-1 items-center justify-between gap-3 py-1.5 pe-4">
                    تذكرني
                    <x-switch name="remember" :checked="old('remember')" />
                </span>
            </label>
        </x-group>

        <x-button class="w-full">دخول</x-button>
    </form>

    <p class="text-center text-[15px] text-muted">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="font-semibold text-tint active:opacity-50">أنشئ حسابًا</a>
    </p>
</x-auth-card>
