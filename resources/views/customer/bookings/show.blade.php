@extends('layouts.customer')

@section('title', $booking->booking_number)

@section('content')
    <h1>{{ $booking->booking_number }}</h1>
    <p><a href="{{ route('customer.bookings.index') }}">Back to list</a></p>

    <dl>
        <dt>Status</dt><dd>{{ $booking->status->value }}</dd>
        <dt>Vehicle type</dt><dd>{{ $booking->vehicle_type }}</dd>
        <dt>Payout (snapshot)</dt><dd>₱{{ number_format((float) $booking->payout, 2) }}</dd>
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

    @if ($booking->driver_id)
        @include('bookings._route_map', ['booking' => $booking, 'routeMap' => $routeMap])
    @else
        <p>Waiting for gatepass and driver assignment.</p>
    @endif

    <p><em>Gatepass is not visible to customers per document ACL.</em></p>

    @include('bookings._eir_pod_links', ['booking' => $booking])

    @include('bookings._invoice', ['booking' => $booking])
@endsection
