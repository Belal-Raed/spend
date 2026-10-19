{{--
    The app shell around every signed-in screen.

    A main screen has a large title, the tab bar, and a + button for a new
    transaction. A modal screen (a form) has a small title between a cancel
    link and a save button that submits the form with the id given in
    `submit`, and no tab bar. `back` adds a back link to a main screen.
    The `trailing` slot replaces the + button.
--}}
@props(['title', 'modal' => false, 'cancel' => null, 'submit' => null, 'submitLabel' => 'حفظ', 'back' => null, 'backLabel' => null])

@php
    // Each tab as [route, label, icon, pattern of the routes it is active on].
    $tabs = [
        ['home', 'الرئيسية', 'home', 'home'],
        ['transactions.index', 'الحركات', 'list', 'transactions.*'],
        ['debts.index', 'الديون', 'exchange', 'debts.*'],
        ['settings', 'الإعدادات', 'cog', 'settings|categories.*'],
    ];
@endphp

<x-layout :title="$title">
    <div class="mx-auto flex min-h-dvh max-w-lg flex-col">
        <header
            data-nav-bar
            @class([
                'group sticky top-0 z-20 border-b-[0.5px] border-transparent pt-[env(safe-area-inset-top)] transition-colors duration-200 select-none',
                'data-scrolled:border-separator data-scrolled:bg-bar data-scrolled:backdrop-blur-xl',
                'bg-canvas' => ! $modal,
                'bg-bar backdrop-blur-xl' => $modal,
            ])
        >
            <div class="grid h-11 grid-cols-[1fr_auto_1fr] items-center gap-2 px-4">
                <div class="flex justify-start">
                    @if ($modal && $cancel)
                        <a href="{{ $cancel }}" class="text-tint active:opacity-50">إلغاء</a>
                    @elseif ($back)
                        <a href="{{ $back }}" class="-ms-2 flex items-center gap-0.5 text-tint active:opacity-50">
                            <x-icon name="chevron-right" class="size-6" stroke-width="2.25" />
                            {{ $backLabel }}
                        </a>
                    @endif
                </div>

                <span @class([
                    'truncate text-center font-semibold',
                    'opacity-0 transition-opacity duration-200 group-data-scrolled:opacity-100' => ! $modal,
                ])>{{ $title }}</span>

                <div class="flex justify-end">
                    @isset($trailing)
                        {{ $trailing }}
                    @elseif ($modal && $submit)
                        <button type="submit" form="{{ $submit }}" class="cursor-pointer font-semibold text-tint active:opacity-50">{{ $submitLabel }}</button>
                    @elseif (! $modal)
                        <a href="{{ route('transactions.create') }}" class="flex size-8 items-center justify-center rounded-full bg-fill text-tint active:opacity-50" aria-label="حركة جديدة">
                            <x-icon name="plus" class="size-5" stroke-width="2.5" />
                        </a>
                    @endisset
                </div>
            </div>
        </header>

        @unless ($modal)
            <h1 class="px-4 pt-1 pb-2 text-[34px] leading-tight font-bold">{{ $title }}</h1>
        @endunless

        <div data-scroll-sentinel aria-hidden="true"></div>

        <main @class([
            'flex flex-1 flex-col gap-7 px-4',
            'pt-2 pb-[calc(env(safe-area-inset-bottom)+6rem)]' => ! $modal,
            'pt-5 pb-[calc(env(safe-area-inset-bottom)+2.5rem)]' => $modal,
        ])>
            @if ($modal)
                <h1 class="sr-only">{{ $title }}</h1>
            @endif

            {{ $slot }}
        </main>
    </div>

    @if (session('status'))
        <p role="status" class="toast pointer-events-none fixed inset-x-0 top-[calc(env(safe-area-inset-top)+0.5rem)] z-40 mx-auto flex w-fit items-center gap-2 rounded-full bg-raised px-5 py-3 text-[15px] font-semibold shadow-xl shadow-black/15 ring-[0.5px] ring-separator">
            <x-icon name="check-circle" class="size-5 text-positive" />
            {{ session('status') }}
        </p>
    @endif

    @unless ($modal)
        <nav class="fixed inset-x-0 bottom-0 z-20 border-t-[0.5px] border-separator bg-bar backdrop-blur-xl select-none" aria-label="التنقل الرئيسي">
            <div class="mx-auto grid max-w-lg grid-cols-4 pb-[max(env(safe-area-inset-bottom),0.375rem)]">
                @foreach ($tabs as [$route, $label, $icon, $pattern])
                    @php($isActive = request()->routeIs(...explode('|', $pattern)))

                    <a
                        href="{{ route($route) }}"
                        @class([
                            'flex flex-col items-center gap-0.5 pt-1.5 pb-1 text-[10px] font-medium active:opacity-60',
                            'text-tint' => $isActive,
                            'text-muted' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                    >
                        <x-icon :name="$icon" class="size-7" :stroke-width="$isActive ? 2 : 1.5" />
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endunless
</x-layout>
