<x-auth-card title="إنشاء حساب">
    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
        @csrf

        <x-input name="name" label="الاسم" autocomplete="name" required autofocus />
        <x-input name="email" type="email" label="البريد الإلكتروني" dir="ltr" autocomplete="username" required />
        <x-input name="password" type="password" label="كلمة المرور" dir="ltr" autocomplete="new-password" required />
        <x-input name="password_confirmation" type="password" label="تأكيد كلمة المرور" dir="ltr" autocomplete="new-password" required />

        <x-button>إنشاء الحساب</x-button>
    </form>

    <p class="text-sm text-slate-600">
        لديك حساب؟
        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:underline">سجّل الدخول</a>
    </p>
</x-auth-card>
