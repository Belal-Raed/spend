<x-auth-card title="تسجيل الدخول">
    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
        @csrf

        <x-input name="phone" type="tel" label="رقم الجوال" dir="ltr" autocomplete="username" placeholder="0599123456" required autofocus />
        <x-input name="password" type="password" label="كلمة المرور" dir="ltr" autocomplete="current-password" required />

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            تذكرني
        </label>

        <x-button>دخول</x-button>
    </form>

    <p class="text-sm text-slate-600">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="font-semibold text-emerald-700 hover:underline">أنشئ حسابًا</a>
    </p>
</x-auth-card>
