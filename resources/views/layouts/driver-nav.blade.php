<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Navigation') — GK Trucking Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="h-dvh overflow-hidden bg-surface text-text">
    @yield('content')

    <p class="sr-only" data-driver-location-note data-driver-location="{{ route('driver.location.store') }}">
        Sharing location while navigation is open.
    </p>

    @vite('resources/js/driver-location.js')
</body>
</html>
