@extends('layouts.management')

@section('brand', 'GK Admin')

@section('sidebar')
    <x-ui.nav-link :href="route('admin.home')" icon="layout-dashboard" :active="request()->routeIs('admin.home')">
        Overview
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.settings.edit')" icon="settings" :active="request()->routeIs('admin.settings.*')">
        Settings
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.pricing.index')" icon="tags" :active="request()->routeIs('admin.pricing.*')">
        Pricing
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.fleet.index')" icon="truck" :active="request()->routeIs('admin.fleet.*')">
        Fleet
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.users.index')" icon="users" :active="request()->routeIs('admin.users.*')">
        Users
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.bookings.index')" icon="clipboard-list" :active="request()->routeIs('admin.bookings.*')">
        Bookings
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.earnings.index')" icon="chart-column" :active="request()->routeIs('admin.earnings.*')">
        Earnings
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('admin.activity-logs.index')" icon="activity" :active="request()->routeIs('admin.activity-logs.*')">
        Activity logs
    </x-ui.nav-link>
@endsection
