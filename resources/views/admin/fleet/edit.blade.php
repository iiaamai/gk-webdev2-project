@extends('layouts.admin')

@section('title', 'Edit vehicle')

@section('content')
    <x-ui.page-header title="Edit vehicle" subtitle="{{ $vehicle->plate_number }} — {{ $vehicle->brand }}">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.fleet.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to fleet
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="max-w-xl">
        @include('admin.fleet._form', ['vehicle' => $vehicle, 'action' => route('admin.fleet.update', $vehicle)])
    </x-ui.card>
@endsection
