@extends('layouts.customer')

@section('title', 'New Booking')

@section('content')
    <h1>New booking</h1>
    <p><a href="{{ route('customer.bookings.index') }}">Back to list</a></p>
    <p>Prices load from the pricing list. Enter pickup and dropoff addresses for your trip.</p>
    <form method="post" action="{{ route('customer.bookings.store') }}">
        @csrf
        <label for="pricing_id">Vehicle type</label>
        <select id="pricing_id" name="pricing_id" required>
            <option value="">Select type</option>
            @foreach ($pricings as $pricing)
                <option value="{{ $pricing->id }}" @selected((string) old('pricing_id') === (string) $pricing->id)>
                    {{ $pricing->vehicle_type }} — ₱{{ number_format((float) $pricing->amount, 2) }}
                </option>
            @endforeach
        </select>

        <label for="booking_datetime">Preferred pickup datetime</label>
        <input id="booking_datetime" type="datetime-local" name="booking_datetime" value="{{ old('booking_datetime') }}" required>

        <label for="pickup_address">Pickup address</label>
        <input id="pickup_address" name="pickup_address" value="{{ old('pickup_address') }}" required>

        <label for="pickup_lat">Pickup latitude</label>
        <input id="pickup_lat" type="number" step="any" name="pickup_lat" value="{{ old('pickup_lat', '14.5547') }}" required>

        <label for="pickup_lng">Pickup longitude</label>
        <input id="pickup_lng" type="number" step="any" name="pickup_lng" value="{{ old('pickup_lng', '121.0244') }}" required>

        <label for="dropoff_address">Dropoff address</label>
        <input id="dropoff_address" name="dropoff_address" value="{{ old('dropoff_address') }}" required>

        <label for="dropoff_lat">Dropoff latitude</label>
        <input id="dropoff_lat" type="number" step="any" name="dropoff_lat" value="{{ old('dropoff_lat', '14.6760') }}" required>

        <label for="dropoff_lng">Dropoff longitude</label>
        <input id="dropoff_lng" type="number" step="any" name="dropoff_lng" value="{{ old('dropoff_lng', '121.0437') }}" required>

        <label for="cargo_desc">Cargo description</label>
        <textarea id="cargo_desc" name="cargo_desc" rows="3">{{ old('cargo_desc') }}</textarea>

        <label for="additional_requirements">Additional requirements</label>
        <textarea id="additional_requirements" name="additional_requirements" rows="2">{{ old('additional_requirements') }}</textarea>

        <button type="submit">Submit booking</button>
    </form>
@endsection
