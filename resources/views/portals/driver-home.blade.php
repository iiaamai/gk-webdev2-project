@extends('layouts.driver')

@section('title', 'Home')

@section('content')
    <x-ui.page-header title="Driver home" :subtitle="'Welcome, '.$name" />

    <x-ui.card>
        <h2 class="text-base font-semibold text-text">Deliveries</h2>
        <p class="mt-1 text-sm text-text-muted">Accept available trips, update status, and complete EIR/POD.</p>
        <div class="mt-4">
            <x-ui.button href="{{ route('driver.deliveries.index') }}">
                <x-ui.icon name="package" size="size-4" />
                Open deliveries
            </x-ui.button>
        </div>
    </x-ui.card>
@endsection
