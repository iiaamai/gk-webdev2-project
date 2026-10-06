<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GK Trucking Services') — GK Trucking Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $authMode = trim($__env->yieldContent('auth_mode', 'single'));
    $showcaseTagline = trim($__env->yieldContent('showcase_tagline', 'Urban & regional truck logistics'));
@endphp
<body class="min-h-screen bg-surface text-text">
    @if ($authMode === 'single')
        <div class="relative min-h-screen overflow-hidden bg-primary-tone-1">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-primary-tint-1 to-transparent"></div>

            <div class="relative mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-10 sm:px-6">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-primary text-text-on-primary shadow-sm">
                        <x-ui.icon name="truck" size="size-6" />
                    </div>
                    <p class="text-lg font-semibold tracking-tight text-text">GK Trucking Services</p>
                    <p class="mt-1 text-sm text-text-muted">@yield('subtitle', 'Sign in to continue')</p>
                </div>

                <x-ui.card>
                    @yield('content')
                </x-ui.card>

                @hasSection('footer')
                    <div class="mt-6 text-center text-sm text-text-muted">
                        @yield('footer')
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Split: mobile showcase on top; desktop order by auth_mode --}}
        <div class="grid min-h-screen md:grid-cols-2">
            @php
                $formFirst = $authMode === 'form-first';
                $showcaseOrder = $formFirst ? 'order-1 md:order-2' : 'order-1';
                $formOrder = $formFirst ? 'order-2 md:order-1' : 'order-2';
            @endphp

            <aside class="{{ $showcaseOrder }} border-b border-border bg-surface-elevated md:border-b-0 {{ $formFirst ? 'md:border-l' : 'md:border-r' }} border-border">
                <div class="md:hidden">
                    <x-ui.auth-showcase :tagline="$showcaseTagline" compact />
                </div>
                <div class="hidden md:block">
                    <x-ui.auth-showcase :tagline="$showcaseTagline" />
                </div>
            </aside>

            <section class="{{ $formOrder }} flex flex-col justify-center bg-primary-tint-1 px-4 py-8 sm:px-8 lg:px-12">
                <div class="mx-auto w-full max-w-md">
                    <x-ui.card>
                        @yield('content')
                    </x-ui.card>

                    @hasSection('footer')
                        <div class="mt-6 text-center text-sm text-text-muted">
                            @yield('footer')
                        </div>
                    @endif
                </div>
            </section>
        </div>
    @endif

    <x-ui.flash />
    <style>[x-cloak]{display:none!important}</style>
    @stack('scripts')
</body>
</html>
