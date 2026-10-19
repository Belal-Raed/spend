<x-auth-card title="إنشاء حساب">
    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
        @csrf

        <x-group>
            <x-input name="name" label="الاسم" autocomplete="name" placeholder="مثلًا: أبو محمد" required autofocus />
            <x-input name="phone" type="tel" label="رقم الجوال" dir="ltr" class="text-end" autocomplete="username" placeholder="0599123456" required />
        </x-group>

        <x-group footer="8 أحرف على الأقل.">
            <x-input name="password" type="password" label="كلمة المرور" dir="ltr" class="text-end" autocomplete="new-password" required />
            <x-input name="password_confirmation" type="password" label="التأكيد" dir="ltr" class="text-end" autocomplete="new-password" placeholder="أعد كتابتها" required />
        </x-group>

        <x-button class="w-full">إنشاء الحساب</x-button>
    </form>

    <p class="text-center text-[15px] text-muted">
        لديك حساب؟
        <a href="{{ route('login') }}" class="font-semibold text-tint active:opacity-50">سجّل الدخول</a>
    </p>
</x-auth-card>
