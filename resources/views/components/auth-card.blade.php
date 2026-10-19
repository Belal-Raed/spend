@props(['title'])

<x-layout :title="$title">
    <main class="mx-auto flex min-h-dvh max-w-sm flex-col justify-center gap-8 px-4 pt-[calc(env(safe-area-inset-top)+2rem)] pb-[calc(env(safe-area-inset-bottom)+2rem)]">
        <div class="flex flex-col items-center gap-4 text-center">
            <img src="/icons/icon-192.png" alt="" class="size-20 rounded-[22%] shadow-lg shadow-emerald-900/20">

            <div class="flex flex-col gap-1">
                <p class="text-[28px] font-bold">{{ config('app.name') }}</p>
                <h1 class="text-[17px] text-muted">{{ $title }}</h1>
            </div>
        </div>

        {{ $slot }}
    </main>
</x-layout>
