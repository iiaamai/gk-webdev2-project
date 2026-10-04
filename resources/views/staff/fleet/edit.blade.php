@extends('layouts.staff')

@section('title', 'Edit vehicle')

@section('content')
    <x-ui.page-header
        title="{{ $isReadOnly ? 'View vehicle' : 'Edit vehicle' }}"
        subtitle="{{ $vehicle->plate_number }} — {{ $vehicle->brand }}"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('staff.fleet.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to fleet
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.fleet._form', [
        'vehicle' => $vehicle,
        'action' => route('staff.fleet.update', $vehicle),
        'drivers' => $drivers,
        'pricings' => $pricings,
        'lockDetails' => $lockDetails,
        'canEditStatus' => $canEditStatus,
        'canEditDriver' => $canEditDriver,
        'showSubmit' => $showSubmit,
        'isReadOnly' => $isReadOnly,
    ])
@endsection
