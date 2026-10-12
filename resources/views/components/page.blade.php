@props(['title'])

@php
    // Each link as [route, label, icon, pattern of the routes it is active on].
    $links = [
        ['home', 'الرئيسية', 'home', 'home'],
        ['transactions.index', 'الحركات', 'list', 'transactions.*'],
        ['debts.index', 'الديون', 'exchange', 'debts.*'],
        ['categories.index', 'التصنيفات', 'tag', 'categories.*'],
    ];
@endphp

<x-layout :title="$title">
    <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}"><x-logo /></a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="التنقل الرئيسي">
                @foreach ($links as [$route, $label, $icon, $pattern])
                    <a
                        href="{{ route($route) }}"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-emerald-50 text-emerald-700' => request()->routeIs($pattern),
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($pattern),
                        ])
                        @if (request()->routeIs($pattern)) aria-current="page" @endif
                    >{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <span class="max-w-32 truncate text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" title="تسجيل الخروج" class="flex cursor-pointer items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                        <x-icon name="logout" />
                        <span class="sr-only">تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-3xl flex-col gap-5 px-4 pt-6 pb-28 md:pb-10">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">{{ $title }}</h1>

            @unless (request()->routeIs('transactions.create'))
                <x-button :href="route('transactions.create')" class="hidden md:inline-flex">
                    <x-icon name="plus" class="size-4" />
                    حركة جديدة
                </x-button>
            @endunless
        </div>

        @if (session('status'))
            <p role="status" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <x-icon name="check" class="size-4" />
                {{ session('status') }}
            </p>
        @endif

        {{ $slot }}
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-10 border-t border-slate-200 bg-white md:hidden" aria-label="التنقل السفلي">
        <div class="mx-auto grid max-w-3xl grid-cols-5 items-end px-2 pb-[env(safe-area-inset-bottom)]">
            @foreach ($links as $index => [$route, $label, $icon, $pattern])
                @if ($index === 2)
                    <a href="{{ route('transactions.create') }}" class="flex flex-col items-center gap-1 pb-2 text-xs font-medium text-emerald-700">
                        <span class="-mt-5 flex size-12 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg ring-4 ring-slate-50">
                            <x-icon name="plus" class="size-6" />
                        </span>
                        جديدة
                    </a>
                @endif

                <a
                    href="{{ route($route) }}"
                    @class([
                        'flex flex-col items-center gap-1 py-2 text-xs font-medium',
                        'text-emerald-700' => request()->routeIs($pattern),
                        'text-slate-500' => ! request()->routeIs($pattern),
                    ])
                    @if (request()->routeIs($pattern)) aria-current="page" @endif
                >
                    <x-icon :name="$icon" class="size-6" />
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>
</x-layout>
