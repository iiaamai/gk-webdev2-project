@extends('layouts.driver')

@section('title', $booking->booking_number)

@section('content')
    <h1>{{ $booking->booking_number }}</h1>
    <p><a href="{{ route('driver.deliveries.index') }}">Back to deliveries</a></p>

    <p>Status: <strong>{{ $booking->status->value }}</strong></p>

    <dl>
        <dt>Vehicle type</dt><dd>{{ $booking->vehicle_type }}</dd>
        <dt>Pickup</dt><dd>{{ $booking->pickup_address }}</dd>
        <dt>Dropoff</dt><dd>{{ $booking->dropoff_address }}</dd>
        <dt>Payout</dt><dd>₱{{ number_format((float) $booking->payout, 2) }}</dd>
        @if ($booking->cargo_desc)
            <dt>Cargo</dt><dd>{{ $booking->cargo_desc }}</dd>
        @endif
    </dl>

    @can('viewGatepass', $booking)
        <p><a href="{{ route('documents.bookings.gatepass', $booking) }}">Download gatepass</a></p>
    @endcan

    @include('bookings._eir_pod_links', ['booking' => $booking])

    @can('downloadReceipt', $booking)
        <p><a href="{{ route('driver.deliveries.receipt', $booking) }}">Download delivery receipt (PDF)</a></p>
    @endcan

    @if ($booking->driver_id)
        @include('bookings._route_map', ['booking' => $booking, 'routeMap' => $routeMap])
    @endif

    @can('accept', $booking)
        <form method="post" action="{{ route('driver.deliveries.accept', $booking) }}" onsubmit="return confirm('Accept this delivery?');">
            @csrf
            <button type="submit">Accept delivery</button>
        </form>
    @endcan

    @include('bookings._eir_pod_upload', [
        'booking' => $booking,
        'eirAction' => route('driver.deliveries.eir.store', $booking),
        'podAction' => route('driver.deliveries.pod.store', $booking),
    ])

    @can('updateDeliveryStatus', $booking)
        @if ($booking->status === \App\Enums\BookingStatus::Accepted)
            <form method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="in_transit">
                <button type="submit">Mark in transit</button>
            </form>
        @endif

        @if ($booking->status === \App\Enums\BookingStatus::InTransit)
            <form method="post" action="{{ route('driver.deliveries.status.update', $booking) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="completed">
                <button type="submit">Mark completed</button>
            </form>
            <p><em>Upload EIR and POD before completing.</em></p>
        @endif
    @endcan
@endsection
