@extends('layouts.admin')

@section('title', $booking->booking_number)

@section('content')
    <h1>{{ $booking->booking_number }}</h1>
    <p>
        <a href="{{ route('admin.bookings.index') }}">Back to list</a>
        · <a href="{{ route('admin.bookings.edit', $booking) }}">Edit</a>
    </p>

    <p>Customer: <strong>{{ $booking->customer->name }}</strong></p>
    <p>Status: <strong>{{ $booking->status->value }}</strong></p>
    <p>Payout snapshot: ₱{{ number_format((float) $booking->payout, 2) }}</p>

    @if ($booking->hasGatepass())
        <p>Gatepass: <a href="{{ route('documents.bookings.gatepass', $booking) }}">Download</a></p>
    @else
        <p>Gatepass: not uploaded.</p>
    @endif

    @include('bookings._eir_pod_links', ['booking' => $booking])

    @include('bookings._invoice', [
        'booking' => $booking,
        'markPaidAction' => route('admin.bookings.invoice.mark-paid', $booking),
    ])

    @include('bookings._eir_pod_upload', [
        'booking' => $booking,
        'eirAction' => route('admin.bookings.eir.store', $booking),
        'podAction' => route('admin.bookings.pod.store', $booking),
    ])

    <h2>Upload or replace gatepass</h2>
    <form method="post" action="{{ route('admin.bookings.gatepass.store', $booking) }}" enctype="multipart/form-data">
        @csrf
        <label for="gatepass">Gatepass image</label>
        <input id="gatepass" type="file" name="gatepass" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <button type="submit">{{ $booking->hasGatepass() ? 'Replace gatepass' : 'Upload gatepass' }}</button>
    </form>

    <h2>Update status</h2>
    <form method="post" action="{{ route('admin.bookings.status.update', $booking) }}">
        @csrf
        @method('PATCH')
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($booking->status === $status)>{{ $status->value }}</option>
            @endforeach
        </select>
        <button type="submit">Apply status</button>
    </form>

    @can('cancel', $booking)
        <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?');">
            @csrf
            <button type="submit">Cancel booking</button>
        </form>
    @endcan

    <form method="post" action="{{ route('admin.bookings.destroy', $booking) }}" onsubmit="return confirm('Archive this booking?');">
        @csrf
        @method('DELETE')
        <button type="submit">Archive booking</button>
    </form>
@endsection
