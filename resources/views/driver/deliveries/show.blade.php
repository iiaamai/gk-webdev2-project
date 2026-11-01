@extends('layouts.driver')

@section('title', $booking->booking_number)

@section('content')
    @php
        $statusTone = $booking->status->badgeTone();
        $customer = $booking->customer;
    @endphp

    <x-ui.page-header :title="$booking->booking_number" subtitle="Delivery workspace">
        <x-slot:actions>
            <x-ui.button href="{{ route('driver.deliveries.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to deliveries
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <x-ui.badge :tone="$statusTone">{{ $booking->status->label() }}</x-ui.badge>
        <span class="text-sm text-text-muted">{{ $booking->vehicle_type }}</span>
    </div>

    @include('bookings._trip_stepper', ['booking' => $booking])

    @include('bookings._round_trip_laps', ['booking' => $booking])

    <x-ui.card class="mb-6">
        <x-ui.section-heading icon="map-pin" title="Trip details" />
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-text-muted">Pickup</dt>
                <dd class="mt-1 font-medium text-text">{{ $booking->pickup_address }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Dropoff</dt>
                <dd class="mt-1 font-medium text-text">{{ $booking->dropoff_address }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Preferred pickup</dt>
                <dd class="mt-1 text-text">{{ $booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Payout</dt>
                <dd class="mt-1 font-medium text-text">₱{{ number_format((float) $booking->payout, 2) }}</dd>
            </div>
            @if ($customer)
                <div>
                    <dt class="text-text-muted">Customer</dt>
                    <dd class="mt-1 font-medium text-text">{{ $customer->name }}</dd>
                </div>
                <div>
                    <dt class="text-text-muted">Customer mobile</dt>
                    <dd class="mt-1 text-text">
                        @if (filled($customer->mobile))
                            <a href="tel:{{ $customer->mobile }}" class="font-medium text-primary hover:text-primary-shade-1">{{ $customer->mobile }}</a>
                        @else
                            <span class="text-text-muted">Not provided</span>
                        @endif
                    </dd>
                </div>
            @endif
            @if ($booking->cargo_desc)
                <div class="sm:col-span-2">
                    <dt class="text-text-muted">Cargo</dt>
                    <dd class="mt-1 text-text">{{ $booking->cargo_desc }}</dd>
                </div>
            @endif
            @if ($booking->additional_requirements)
                <div class="sm:col-span-2">
                    <dt class="text-text-muted">Requirements</dt>
                    <dd class="mt-1 text-text">{{ $booking->additional_requirements }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <x-ui.card class="mb-6">
        @include('bookings._route_map', [
            'booking' => $booking,
            'routeMap' => $routeMap,
            'showNavActions' => true,
        ])
    </x-ui.card>

    @include('driver.deliveries._documents', ['booking' => $booking])

    @include('bookings._pickup_capacity', ['booking' => $booking])

    @include('driver.deliveries._next_step', ['booking' => $booking])

    @can('downloadReceipt', $booking)
        <p class="mb-4 text-sm">
            <a href="{{ route('driver.deliveries.receipt', $booking) }}" class="inline-flex items-center gap-1.5 font-medium text-primary hover:text-primary-shade-1">
                <x-ui.icon name="download" size="size-4" />
                Download delivery receipt (PDF)
            </a>
        </p>
    @endcan
@endsection
