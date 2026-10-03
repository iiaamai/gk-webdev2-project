@extends('layouts.portal')

@section('brand', 'GK Driver')
@section('home_route', route('driver.home'))

@section('header-actions')
    <a
        href="{{ route('driver.settings.edit') }}"
        class="inline-flex items-center justify-center rounded-md p-2 text-text-muted hover:bg-surface-inset hover:text-text"
        aria-label="Settings"
        title="Settings"
    >
        <x-ui.icon name="settings" size="size-5" />
    </a>
@endsection

@section('nav')
    <x-ui.nav-link variant="portal" :href="route('driver.home')" icon="house" :active="request()->routeIs('driver.home')">
        Home
    </x-ui.nav-link>
    <x-ui.nav-link variant="portal" :href="route('driver.deliveries.index')" icon="package" :active="request()->routeIs('driver.deliveries.*')">
        Deliveries
    </x-ui.nav-link>
@endsection
