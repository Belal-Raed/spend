<x-layout title="الرئيسية">
    <main class="mx-auto flex max-w-2xl flex-col gap-6 p-4">
        <header class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">أهلًا {{ auth()->user()->name }}</h1>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-button class="text-sm">تسجيل الخروج</x-button>
            </form>
        </header>
    </main>
</x-layout>
