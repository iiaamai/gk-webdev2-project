@extends('layouts.portal')

@section('brand', 'GK Driver')
@section('home_route', route('driver.home'))

@section('nav')
    <x-ui.nav-link variant="portal" :href="route('driver.home')" icon="house" :active="request()->routeIs('driver.home')">
        Home
    </x-ui.nav-link>
    <x-ui.nav-link variant="portal" :href="route('driver.deliveries.index')" icon="package" :active="request()->routeIs('driver.deliveries.*')">
        Deliveries
    </x-ui.nav-link>
@endsection
