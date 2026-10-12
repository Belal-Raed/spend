@props(['title'])

<x-layout :title="$title">
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="flex w-full max-w-sm flex-col gap-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold">{{ $title }}</h1>

            {{ $slot }}
        </div>
    </main>
</x-layout>
