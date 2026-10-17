<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GK Logistics') — GK Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
@php
    $howActive = request()->routeIs('home', 'marketing.how');
    $pricingActive = request()->routeIs('marketing.pricing');
    $faqActive = request()->routeIs('marketing.faq');
@endphp
<body class="flex min-h-screen flex-col bg-brand-white text-brand-charcoal antialiased" x-data="{ menuOpen: false }">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-navy">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-brand-white">
                <x-brand.seal logo="1" surface="dark" size="sm" alt="" />
                <span class="text-sm font-semibold tracking-tight sm:text-base">GK Logistics</span>
            </a>

            <nav class="hidden items-center gap-6 text-sm text-white/80 md:flex">
                <a href="{{ route('marketing.how') }}" class="{{ $howActive ? 'text-brand-white' : 'hover:text-brand-white' }}" @if ($howActive) aria-current="page" @endif>How it works</a>
                <a href="{{ route('marketing.pricing') }}" class="{{ $pricingActive ? 'text-brand-white' : 'hover:text-brand-white' }}" @if ($pricingActive) aria-current="page" @endif>Pricing</a>
                <a href="{{ route('marketing.faq') }}" class="{{ $faqActive ? 'text-brand-white' : 'hover:text-brand-white' }}" @if ($faqActive) aria-current="page" @endif>FAQ</a>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-brand-white hover:bg-white/10">Sign in</a>
                <a href="{{ route('register.customer') }}" class="inline-flex items-center rounded-md bg-brand-crimson px-4 py-2 text-sm font-medium text-brand-white hover:bg-brand-crimson/90">Sign up</a>
            </div>

            <button type="button" class="inline-flex rounded-md p-2 text-brand-white md:hidden" @click="menuOpen = ! menuOpen" aria-label="Open menu">
                <x-ui.icon name="menu" size="size-6" />
            </button>
        </div>

        <div x-show="menuOpen" x-cloak class="border-t border-white/10 px-4 py-3 md:hidden">
            <div class="flex flex-col gap-3 text-sm text-white/90">
                <a href="{{ route('marketing.how') }}" @click="menuOpen = false">How it works</a>
                <a href="{{ route('marketing.pricing') }}" @click="menuOpen = false">Pricing</a>
                <a href="{{ route('marketing.faq') }}" @click="menuOpen = false">FAQ</a>
                <a href="{{ route('login') }}" class="font-medium">Sign in</a>
                <a href="{{ route('register.customer') }}" class="font-medium text-brand-crimson">Sign up</a>
            </div>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-brand-navy text-white/80">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <x-brand.lockup surface="dark" size="md" />
            <p class="text-sm">© GK Logistics</p>
        </div>
    </footer>
</body>
</html>
