@extends('layouts.customer')

@section('title', 'New Booking')

@section('content')
    @php
        $pickupLat = old('pickup_lat', '14.5547');
        $pickupLng = old('pickup_lng', '121.0244');
        $dropoffLat = old('dropoff_lat', '14.6760');
        $dropoffLng = old('dropoff_lng', '121.0437');
    @endphp

    <x-ui.page-header
        title="New booking"
        subtitle="Prices come from the pricing list. Pay Later invoice is created with your booking."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('customer.bookings.index') }}" variant="secondary">
                <x-ui.icon name="arrow-left" size="size-4" />
                Back to list
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="post" action="{{ route('customer.bookings.store') }}" class="mx-auto w-full max-w-3xl space-y-6">
        @csrf

        <input type="hidden" name="pickup_lat" value="{{ $pickupLat }}">
        <input type="hidden" name="pickup_lng" value="{{ $pickupLng }}">
        <input type="hidden" name="dropoff_lat" value="{{ $dropoffLat }}">
        <input type="hidden" name="dropoff_lng" value="{{ $dropoffLng }}">

        <x-ui.card>
            <x-ui.section-heading icon="truck" title="Vehicle & schedule" description="Choose a vehicle type and preferred pickup time." />
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-ui.label for="pricing_id">Vehicle type</x-ui.label>
                    <x-ui.select id="pricing_id" name="pricing_id" required>
                        <option value="">Select type</option>
                        @foreach ($pricings as $pricing)
                            <option value="{{ $pricing->id }}" @selected((string) old('pricing_id') === (string) $pricing->id)>
                                {{ $pricing->vehicle_type }} — ₱{{ number_format((float) $pricing->amount, 2) }}
                            </option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.field-error name="pricing_id" />
                </div>
                <div class="sm:col-span-2">
                    <x-ui.label for="booking_datetime">Preferred pickup datetime</x-ui.label>
                    <x-ui.input
                        id="booking_datetime"
                        type="datetime-local"
                        name="booking_datetime"
                        value="{{ old('booking_datetime') }}"
                        required
                    />
                    <x-ui.field-error name="booking_datetime" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="map-pin" title="Pickup" />
            <div class="mt-4 space-y-4">
                <p class="text-xs text-text-muted">
                    Lat {{ number_format((float) $pickupLat, 4) }} · Lng {{ number_format((float) $pickupLng, 4) }}
                </p>
                @include('bookings._map_placeholder', [
                    'placeholderCaption' => 'Pickup location map preview.',
                ])
                <x-ui.field-error name="pickup_lat" />
                <x-ui.field-error name="pickup_lng" />
                <div>
                    <x-ui.label for="pickup_address">Address</x-ui.label>
                    <x-ui.input id="pickup_address" name="pickup_address" value="{{ old('pickup_address') }}" required />
                    <x-ui.field-error name="pickup_address" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-ui.label for="pickup_port_number">Port number</x-ui.label>
                        <x-ui.input
                            id="pickup_port_number"
                            name="pickup_port_number"
                            value="{{ old('pickup_port_number') }}"
                            required
                        />
                        <x-ui.field-error name="pickup_port_number" />
                    </div>
                    <div>
                        <x-ui.label for="pickup_container_number">Container number</x-ui.label>
                        <x-ui.input
                            id="pickup_container_number"
                            name="pickup_container_number"
                            value="{{ old('pickup_container_number') }}"
                            required
                        />
                        <x-ui.field-error name="pickup_container_number" />
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="map-pin" title="Dropoff" />
            <div class="mt-4 space-y-4">
                <p class="text-xs text-text-muted">
                    Lat {{ number_format((float) $dropoffLat, 4) }} · Lng {{ number_format((float) $dropoffLng, 4) }}
                </p>
                @include('bookings._map_placeholder', [
                    'placeholderCaption' => 'Dropoff location map preview.',
                ])
                <x-ui.field-error name="dropoff_lat" />
                <x-ui.field-error name="dropoff_lng" />
                <div>
                    <x-ui.label for="dropoff_address">Address</x-ui.label>
                    <x-ui.input id="dropoff_address" name="dropoff_address" value="{{ old('dropoff_address') }}" required />
                    <x-ui.field-error name="dropoff_address" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-heading icon="package" title="Cargo details" description="Optional notes for the trip." />
            <div class="mt-4 space-y-4">
                <div>
                    <x-ui.label for="cargo_desc">Cargo description</x-ui.label>
                    <x-ui.textarea id="cargo_desc" name="cargo_desc" rows="3">{{ old('cargo_desc') }}</x-ui.textarea>
                    <x-ui.field-error name="cargo_desc" />
                </div>
                <div>
                    <x-ui.label for="additional_requirements">Additional requirements</x-ui.label>
                    <x-ui.textarea id="additional_requirements" name="additional_requirements" rows="2">{{ old('additional_requirements') }}</x-ui.textarea>
                    <x-ui.field-error name="additional_requirements" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.button type="submit" class="m-0">
            Submit booking
        </x-ui.button>
    </form>
@endsection
