@extends('layouts.management')

@section('brand', 'GK Staff')

@section('sidebar')
    <x-ui.nav-link :href="route('staff.home')" icon="layout-dashboard" :active="request()->routeIs('staff.home')">
        Overview
    </x-ui.nav-link>
    <x-ui.nav-link :href="route('staff.bookings.index')" icon="clipboard-list" :active="request()->routeIs('staff.bookings.*')">
        Bookings
    </x-ui.nav-link>
@endsection
