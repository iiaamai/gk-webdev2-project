@extends('layouts.admin')

@section('title', 'New booking')

@section('content')
    @php
        $locationPicker = \App\Support\MapboxIntegration::isConfigured();
    @endphp

    <x-ui.page-header
        title="Create booking"
        subtitle="Set status and route, then trip details at the bottom."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            @include('bookings._create_summary')
        </x-ui.card>

        <x-ui.card>
            @if ($locationPicker)
                <x-ui.section-heading
                    icon="map-pin"
                    title="Destination & route"
                    description="Search or click the map. Pickup and dropoff addresses below stay in sync."
                />
                <div class="mt-4">
                    @include('bookings._location_picker')
                </div>
            @else
                @include('bookings._create_destination')
            @endif
        </x-ui.card>
    </div>

    <div x-data="{ open: false }">
        <form
            method="post"
            action="{{ route('admin.bookings.store') }}"
            enctype="multipart/form-data"
            class="space-y-4"
            x-ref="bookingForm"
        >
            @csrf

            @include('bookings._workspace_status_gatepass', [
                'booking' => null,
                'statuses' => $statuses,
                'mode' => 'create',
            ])

            <x-ui.card>
                <x-ui.section-heading
                    icon="package"
                    title="Trip details"
                    description="Customer, driver, vehicle type, schedule, and addresses."
                />
                <div class="mt-4 space-y-4">
                    @include('bookings._trip_fields', [
                        'booking' => null,
                        'customers' => $customers,
                        'drivers' => $drivers,
                        'pricings' => $pricings,
                    'showCustomer' => true,
                    'suppressTripMap' => $locationPicker,
                ])
                    <x-ui.button type="button" @click="open = true">
                        <x-ui.icon name="save" size="size-4" />
                        Create booking
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <x-ui.confirm-dialog
            title="Create this booking?"
            description="A booking and unpaid invoice will be created for the selected customer."
            confirm-label="Create booking"
            cancel-label="Cancel"
            form-ref="bookingForm"
        />
    </div>
@endsection
