@props(['title'])

<x-layout :title="$title">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-2xl items-center justify-between gap-4 p-4">
            <span class="font-medium">{{ auth()->user()->name }}</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="cursor-pointer text-sm text-gray-600 underline hover:text-gray-900">تسجيل الخروج</button>
            </form>
        </div>
    </header>

    <main class="mx-auto flex max-w-2xl flex-col gap-6 p-4">
        <h1 class="text-xl font-semibold">{{ $title }}</h1>

        {{ $slot }}
    </main>
</x-layout>
