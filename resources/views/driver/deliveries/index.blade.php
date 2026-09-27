@extends('layouts.driver')

@section('title', 'Deliveries')

@section('content')
    <h1>Deliveries</h1>

    <h2>Active</h2>
    <table>
        <thead>
            <tr>
                <th>Booking</th>
                <th>Status</th>
                <th>Pickup</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($active as $booking)
                <tr>
                    <td>{{ $booking->booking_number }}</td>
                    <td>{{ $booking->status->value }}</td>
                    <td>{{ $booking->pickup_address }}</td>
                    <td><a href="{{ route('driver.deliveries.show', $booking) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="4">No active delivery.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Available jobs</h2>
    <p>Jobs with gatepass matching your vehicle type ({{ $driver->assignedVehicle?->pricing?->vehicle_type ?? 'unassigned' }}).</p>
    <table>
        <thead>
            <tr>
                <th>Booking</th>
                <th>Vehicle type</th>
                <th>Pickup</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($available as $booking)
                <tr>
                    <td>{{ $booking->booking_number }}</td>
                    <td>{{ $booking->vehicle_type }}</td>
                    <td>{{ $booking->pickup_address }}</td>
                    <td><a href="{{ route('driver.deliveries.show', $booking) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="4">No available jobs right now.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
