@extends('layouts.customer')

@section('title', $booking->booking_number)

@section('content')
    @php
        $showDriverContact = $booking->driver_id
            && in_array($booking->status, [
                \App\Enums\BookingStatus::Accepted,
                \App\Enums\BookingStatus::InTransit,
                \App\Enums\BookingStatus::Completed,
            ], true);
        $driver = $booking->driver;
    @endphp

    <h1>{{ $booking->booking_number }}</h1>
    <p><a href="{{ route('customer.bookings.index') }}">Back to list</a></p>

    <dl>
        <dt>Status</dt><dd>{{ $booking->status->value }}</dd>
        <dt>Vehicle type</dt><dd>{{ $booking->vehicle_type }}</dd>
        <dt>Amount</dt><dd>₱{{ number_format((float) $booking->payout, 2) }}</dd>
        <dt>Pickup</dt><dd>{{ $booking->pickup_address }} ({{ $booking->pickup_lat }}, {{ $booking->pickup_lng }})</dd>
        <dt>Dropoff</dt><dd>{{ $booking->dropoff_address }} ({{ $booking->dropoff_lat }}, {{ $booking->dropoff_lng }})</dd>
        <dt>Preferred pickup</dt><dd>{{ $booking->booking_datetime->timezone('Asia/Manila')->format('Y-m-d H:i') }}</dd>
        @if ($booking->cargo_desc)
            <dt>Cargo</dt><dd>{{ $booking->cargo_desc }}</dd>
        @endif
        @if ($booking->additional_requirements)
            <dt>Requirements</dt><dd>{{ $booking->additional_requirements }}</dd>
        @endif
    </dl>

    @if ($showDriverContact && $driver)
        <x-ui.card class="my-6 max-w-xl">
            <x-ui.section-heading icon="user" title="Your driver" description="Contact details for this delivery." />
            <div class="mt-4 flex items-start gap-4">
                <x-ui.user-avatar :user="$driver" size="lg" />
                <dl class="grid min-w-0 flex-1 grid-cols-[6.5rem_1fr] gap-x-3 gap-y-2 text-sm">
                    <dt class="text-text-muted">Name</dt>
                    <dd class="font-medium text-text">{{ $driver->name }}</dd>
                    <dt class="text-text-muted">Mobile</dt>
                    <dd class="text-text">
                        @if (filled($driver->mobile))
                            <a href="tel:{{ $driver->mobile }}" class="font-medium text-primary hover:text-primary-shade-1">{{ $driver->mobile }}</a>
                        @else
                            <span class="text-text-muted">Not provided</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </x-ui.card>
    @elseif ($booking->driver_id)
        {{-- Driver assigned but not yet accepted for contact visibility --}}
    @else
        <p>Waiting for gatepass and driver assignment.</p>
    @endif

    @if ($booking->driver_id)
        @include('bookings._route_map', ['booking' => $booking, 'routeMap' => $routeMap])
    @endif

    <p><em>Gatepass is not visible to customers per document ACL.</em></p>

    @include('bookings._eir_pod_links', ['booking' => $booking])

    @include('bookings._invoice', ['booking' => $booking])

    @include('bookings._rating', [
        'booking' => $booking,
        'canRate' => auth()->user()?->can('create', [\App\Models\Rating::class, $booking]),
    ])
@endsection
