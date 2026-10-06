@extends('layouts.management')

@section('brand', 'GK Admin')

@section('header-actions')
    <a
        href="{{ route('admin.settings.edit') }}"
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
    <x-ui.nav-link :href="route('admin.home')" icon="layout-dashboard" :active="request()->routeIs('admin.home')">
        Overview
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.earnings.index')" icon="chart-column" :active="request()->routeIs('admin.earnings.*')">
        Earnings
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.bookings.index')" icon="clipboard-list" :active="request()->routeIs('admin.bookings.*')">
        Bookings
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.fleet.index')" icon="truck" :active="request()->routeIs('admin.fleet.*')">
        Fleet
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.users.index')" icon="users" :active="request()->routeIs('admin.users.*')">
        Users
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.activity-logs.index')" icon="activity" :active="request()->routeIs('admin.activity-logs.*')">
        Activity logs
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.pricing.index')" icon="tags" :active="request()->routeIs('admin.pricing.*')">
        Pricing
    </x-ui.nav-link>
@endsection
