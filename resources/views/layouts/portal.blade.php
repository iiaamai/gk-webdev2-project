<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GK Trucking Services') — GK Trucking Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-surface text-text">
    <header class="sticky top-0 z-30 border-b border-border bg-surface-elevated">
        <div class="mx-auto flex h-14 max-w-5xl items-center gap-3 px-4 sm:px-6">
            <a href="@yield('home_route')" class="flex items-center gap-2 text-sm font-semibold text-text">
                <span class="inline-flex size-8 items-center justify-center rounded-md bg-primary text-text-on-primary">
                    <x-ui.icon name="truck" size="size-4" />
                </span>
                <span>@yield('brand', 'GK Trucking')</span>
            </a>

            <nav class="ms-2 hidden items-center gap-1 sm:flex">
                @yield('nav')
            </nav>

            <div class="ms-auto flex items-center gap-3">
                <span class="hidden text-sm text-text-muted md:inline">{{ auth()->user()?->name }}</span>
                @hasSection('header-actions')
                    @yield('header-actions')
                @endif
                <x-ui.logout-button variant="secondary" icon-size="size-3.5" class="!py-1.5 !text-xs" />
            </div>
        </div>

        {{-- Mobile nav --}}
        <div class="flex gap-1 overflow-x-auto border-t border-border px-4 py-2 sm:hidden">
            @yield('nav')
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        @yield('content')
    </main>

    <x-ui.flash />
</body>
</html>
