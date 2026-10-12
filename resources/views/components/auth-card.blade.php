@props(['title'])

<x-layout :title="$title">
    <main class="flex min-h-screen flex-col items-center justify-center gap-6 p-4">
        <x-logo class="text-2xl" />

        <div class="flex w-full max-w-sm flex-col gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold">{{ $title }}</h1>

            {{ $slot }}
        </div>
    </main>
</x-layout>
