@extends('layouts.management')

@section('brand', 'GK Staff')

@section('header-actions')
    <a
        href="{{ route('staff.settings.edit') }}"
        class="inline-flex items-center justify-center rounded-md p-2 text-text-muted hover:bg-surface-inset hover:text-text"
        aria-label="Settings"
        title="Settings"
    >
        <x-ui.icon name="settings" size="size-5" />
    </a>
    <x-ui.logout-button
        variant="ghost"
        label=""
        icon-size="size-5"
        class="!px-2"
        aria-label="Log out"
        title="Log out"
    />
@endsection

@section('sidebar')
    <x-ui.nav-link :href="route('staff.home')" icon="layout-dashboard" :active="request()->routeIs('staff.home')">
        Overview
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('staff.bookings.index')" icon="clipboard-list" :active="request()->routeIs('staff.bookings.*')">
        Bookings
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('staff.fleet.index')" icon="truck" :active="request()->routeIs('staff.fleet.*')">
        Fleet
    </x-ui.nav-link>
@endsection
