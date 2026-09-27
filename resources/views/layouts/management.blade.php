<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GK Trucking Services') — GK Trucking Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface text-text">
    <div
        class="min-h-screen lg:flex"
        x-data="{ sidebarOpen: false }"
        @keydown.escape.window="sidebarOpen = false"
    >
        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-neutral-950/50 lg:hidden"
            @click="sidebarOpen = false"
            x-cloak
        ></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-sidebar-border bg-sidebar transition-transform duration-200 lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-14 items-center gap-2 border-b border-sidebar-border px-4">
                <x-ui.icon name="truck" size="size-5" class="text-sidebar-accent" />
                <span class="text-sm font-semibold text-sidebar-text-active">@yield('brand', 'GK Trucking')</span>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                @yield('sidebar')
            </nav>

            <div class="border-t border-sidebar-border p-3 lg:hidden">
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

        {{-- Main column --}}
        <div class="flex min-h-screen flex-1 flex-col lg:min-w-0">
            <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-border bg-surface-elevated px-4">
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

                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-text-muted sm:inline">{{ auth()->user()?->name }}</span>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" class="!py-1.5 !text-xs">
                            <x-ui.icon name="log-out" size="size-3.5" />
                            Log out
                        </x-ui.button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <x-ui.flash />
                @yield('content')
            </main>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
