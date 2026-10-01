@extends('layouts.admin')

@section('title', 'Add vehicle')

@section('content')
    <x-ui.page-header title="Add vehicle" subtitle="Register a new fleet unit.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.fleet.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to fleet
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.fleet._form', [
        'vehicle' => null,
        'action' => route('admin.fleet.store'),
        'drivers' => $drivers,
        'pricings' => $pricings,
    ])
@endsection
