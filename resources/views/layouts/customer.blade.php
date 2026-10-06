@extends('layouts.portal')

@section('brand', 'GK Customer')
@section('home_route', route('customer.home'))

@section('header-actions')
    <a
        href="{{ route('customer.settings.edit') }}"
        class="inline-flex items-center justify-center rounded-md p-2 text-text-muted hover:bg-surface-inset hover:text-text"
        aria-label="Settings"
        title="Settings"
    >
        <x-ui.icon name="settings" size="size-5" />
    </a>
@endsection

@section('nav')
    <x-ui.nav-link variant="portal" :href="route('customer.home')" icon="house" :active="request()->routeIs('customer.home')">
        Home
    </x-ui.nav-link>
    <x-ui.nav-link variant="portal" :href="route('customer.bookings.index')" icon="clipboard-list" :active="request()->routeIs('customer.bookings.index') || request()->routeIs('customer.bookings.show')">
        My Bookings
    </x-ui.nav-link>
    <x-ui.nav-link variant="portal" :href="route('customer.bookings.create')" icon="plus" :active="request()->routeIs('customer.bookings.create')">
        New Booking
    </x-ui.nav-link>
    <x-ui.nav-link variant="portal" :href="route('customer.settings.edit')" icon="settings" :active="request()->routeIs('customer.settings.*')" class="sm:hidden">
        Settings
    </x-ui.nav-link>
@endsection
