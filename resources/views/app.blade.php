<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#f2f2f7" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">

        {{-- Applies the theme picked in Settings before the page is drawn, so it never flashes the other one. --}}
        <script>
            try {
                const theme = localStorage.getItem('theme');

                if (theme === 'light' || theme === 'dark') {
                    document.documentElement.dataset.theme = theme;
                    document.querySelectorAll('meta[name=theme-color]').forEach((meta) => {
                        meta.content = theme === 'dark' ? '#000000' : '#f2f2f7';
                    });
                }
            } catch {}
        </script>

        {{-- Added to the home screen, the app opens full screen without the browser bars. --}}
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
        <link rel="icon" href="/icons/favicon-32.png" type="image/png" sizes="32x32">

        <title inertia>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="min-h-dvh bg-canvas font-sans text-[17px] text-label antialiased">
        @inertia
    </body>
</html>
