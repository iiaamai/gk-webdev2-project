<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GK Trucking Services') — GK Trucking Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-screen overflow-hidden bg-surface text-text print:h-auto print:overflow-visible">
    <div
        class="flex h-full print:block"
        x-data="{ sidebarOpen: false }"
        @keydown.escape.window="sidebarOpen = false"
    >
        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-neutral-950/50 print:hidden lg:hidden"
            @click="sidebarOpen = false"
            x-cloak
        ></div>

        {{-- Sidebar: fixed viewport height; nav scrolls inside --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex h-screen w-64 flex-col border-r border-sidebar-border bg-sidebar transition-transform duration-200 print:hidden lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex h-14 shrink-0 items-center gap-2 border-b border-sidebar-border px-4">
                <x-ui.icon name="truck" size="size-5" class="text-sidebar-accent" />
                <span class="text-sm font-semibold text-sidebar-text-active">@yield('brand', 'GK Trucking')</span>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto p-3">
                @yield('sidebar')
            </nav>

            <div class="shrink-0 border-t border-sidebar-border p-3 lg:hidden">
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-sidebar-text hover:bg-neutral-800"
                    @click="sidebarOpen = false"
                >
                    <x-ui.icon name="x" size="size-4" />
                    Close menu
                </button>
            </div>
        </aside>

        {{-- Main column scrolls independently on desktop --}}
        <div class="flex min-h-0 min-w-0 flex-1 flex-col print:ml-0 lg:ml-64">
            <header class="z-30 flex h-14 shrink-0 items-center gap-3 border-b border-border bg-surface-elevated px-4 print:hidden">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md p-2 text-text-muted hover:bg-surface-inset lg:hidden"
                    @click="sidebarOpen = true"
                    aria-label="Open sidebar"
                >
                    <x-ui.icon name="menu" size="size-5" />
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-text">@yield('title', 'Overview')</p>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="hidden text-sm text-text-muted sm:inline">{{ auth()->user()?->name }}</span>
                    @hasSection('header-actions')
                        @yield('header-actions')
                    @endif
                </div>
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6 print:overflow-visible print:p-0">
                @yield('content')
            </main>
        </div>
    </div>

    <x-ui.flash />

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
