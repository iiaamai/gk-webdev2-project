@extends('layouts.staff')

@section('title', $booking->booking_number)

@section('content')
    <h1>{{ $booking->booking_number }}</h1>
    <p><a href="{{ route('staff.bookings.index') }}">Back to list</a></p>

    <p>Customer: <strong>{{ $booking->customer->name }}</strong> ({{ $booking->customer->email }})</p>
    <p>Status: <strong>{{ $booking->status->value }}</strong></p>

    @if ($booking->hasGatepass())
        <p>Gatepass: <a href="{{ route('documents.bookings.gatepass', $booking) }}">Download</a></p>
    @else
        <p>Gatepass: not uploaded yet.</p>
    @endif

    @if ($booking->driver_id)
        @include('bookings._route_map', ['booking' => $booking, 'routeMap' => $routeMap])
    @endif

    @include('bookings._eir_pod_links', ['booking' => $booking])

    @include('bookings._invoice', [
        'booking' => $booking,
        'markPaidAction' => route('staff.bookings.invoice.mark-paid', $booking),
    ])

    @can('update', $booking)
        <h2>Update booking</h2>
        @include('bookings._fields', [
            'action' => route('staff.bookings.update', $booking),
            'method' => 'PUT',
            'booking' => $booking,
            'pricings' => $pricings,
            'showCustomer' => false,
        ])
    @else
        <p><em>Booking is locked for staff edits after gatepass upload.</em></p>
    @endcan

    @can('uploadGatepass', $booking)
        <h2>Upload gatepass (first time)</h2>
        <form method="post" action="{{ route('staff.bookings.gatepass.store', $booking) }}" enctype="multipart/form-data">
            @csrf
            <label for="gatepass">Gatepass image</label>
            <input id="gatepass" type="file" name="gatepass" accept="image/jpeg,image/png,image/webp,image/gif" required>
            <button type="submit">Upload gatepass</button>
        </form>
    @endcan

    @can('cancel', $booking)
        <form method="post" action="{{ route('staff.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?');">
            @csrf
            <button type="submit">Cancel booking</button>
        </form>
    @endcan
@endsection
