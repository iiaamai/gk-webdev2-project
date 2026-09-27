@extends('layouts.admin')

@section('title', 'New booking')

@section('content')
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
            @include('bookings._create_destination')
        </x-ui.card>
    </div>

    <form method="post" action="{{ route('admin.bookings.store') }}" enctype="multipart/form-data" class="space-y-4">
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
                ])
                <x-ui.button type="submit">
                    <x-ui.icon name="save" size="size-4" />
                    Create booking
                </x-ui.button>
            </div>
        </x-ui.card>
    </form>
@endsection
