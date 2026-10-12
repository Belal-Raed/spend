@props(['title'])

<x-layout :title="$title">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-2xl flex-wrap items-center justify-between gap-4 p-4">
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('home') }}" @class(['hover:text-gray-900', 'font-semibold' => request()->routeIs('home'), 'text-gray-600' => ! request()->routeIs('home')])>الرئيسية</a>
                <a href="{{ route('transactions.index') }}" @class(['hover:text-gray-900', 'font-semibold' => request()->routeIs('transactions.*'), 'text-gray-600' => ! request()->routeIs('transactions.*')])>الحركات</a>
            </nav>

            <div class="flex items-center gap-4 text-sm">
                <span class="font-medium">{{ auth()->user()->name }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="cursor-pointer text-gray-600 underline hover:text-gray-900">تسجيل الخروج</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-2xl flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-xl font-semibold">{{ $title }}</h1>

            @unless (request()->routeIs('transactions.create'))
                <a href="{{ route('transactions.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">حركة جديدة</a>
            @endunless
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        {{ $slot }}
    </main>
</x-layout>
