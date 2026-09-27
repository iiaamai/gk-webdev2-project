@php
    $pickupLat = old('pickup_lat', $booking?->pickup_lat ?? 14.5547);
    $pickupLng = old('pickup_lng', $booking?->pickup_lng ?? 121.0244);
    $dropoffLat = old('dropoff_lat', $booking?->dropoff_lat ?? 14.6760);
    $dropoffLng = old('dropoff_lng', $booking?->dropoff_lng ?? 121.0437);
    $tripRouteMap = $routeMap ?? null;
    $drivers = $drivers ?? collect();
    $initialPricingId = old('pricing_id', $booking?->pricing_id);
    if (blank($initialPricingId) && $pricings->isNotEmpty()) {
        $initialPricingId = $pricings->first()->id;
    }
    $initialDriverId = old('driver_id', $booking?->driver_id);
    $driverOptions = $drivers->map(fn ($driver) => [
        'id' => $driver->id,
        'label' => $driver->name.' — '.$driver->email,
        'pricing_id' => $driver->assignedVehicle?->pricing_id,
    ])->values();
    $showDriverSelect = ($showDriver ?? true) && $drivers->isNotEmpty();
@endphp

<input type="hidden" name="pickup_lat" value="{{ $pickupLat }}">
<input type="hidden" name="pickup_lng" value="{{ $pickupLng }}">
<input type="hidden" name="dropoff_lat" value="{{ $dropoffLat }}">
<input type="hidden" name="dropoff_lng" value="{{ $dropoffLng }}">

<div
    class="grid gap-4 md:grid-cols-2"
    x-data="{
        pricingId: @js($initialPricingId !== null && $initialPricingId !== '' ? (string) $initialPricingId : ''),
        driverId: @js($initialDriverId !== null && $initialDriverId !== '' ? (string) $initialDriverId : ''),
        drivers: @js($driverOptions),
        driverVisible(driver) {
            if (! driver.pricing_id) {
                return false;
            }
            if (String(driver.pricing_id) === String(this.pricingId)) {
                return true;
            }
            return this.driverId !== '' && String(driver.id) === String(this.driverId);
        },
    }"
>
    @if ($showCustomer ?? false)
        <div>
            <x-ui.label for="customer_id">Customer</x-ui.label>
            <x-ui.select id="customer_id" name="customer_id" required>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $booking?->customer_id) == $customer->id)>
                        {{ $customer->name }} — {{ $customer->email }}
                    </option>
                @endforeach
            </x-ui.select>
            <x-ui.field-error name="customer_id" />
        </div>
    @endif

    <div @class(['md:col-span-2' => ! ($showCustomer ?? false)])>
        <x-ui.label for="pricing_id">Vehicle type</x-ui.label>
        <x-ui.select id="pricing_id" name="pricing_id" required x-model="pricingId">
            @foreach ($pricings as $pricing)
                <option value="{{ $pricing->id }}" @selected((string) old('pricing_id', $booking?->pricing_id) === (string) $pricing->id)>
                    {{ $pricing->vehicle_type }} — ₱{{ number_format((float) $pricing->amount, 2) }}
                </option>
            @endforeach
        </x-ui.select>
        <x-ui.field-error name="pricing_id" />
    </div>

    @if ($showDriverSelect)
        <div class="md:col-span-2">
            <x-ui.label for="driver_id">Driver</x-ui.label>
            <select
                id="driver_id"
                name="driver_id"
                x-model="driverId"
                class="block w-full rounded-md border border-border bg-surface-elevated px-3 py-2 text-sm text-text shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
                <option value="">Unassigned</option>
                <template x-for="driver in drivers.filter(d => driverVisible(d))" :key="driver.id">
                    <option :value="String(driver.id)" x-text="driver.label"></option>
                </template>
            </select>
            <x-ui.field-error name="driver_id" />
        </div>
    @endif

    <div class="md:col-span-2">
        <x-ui.label for="booking_datetime">Preferred pickup datetime</x-ui.label>
        <x-ui.input
            id="booking_datetime"
            type="datetime-local"
            name="booking_datetime"
            value="{{ old('booking_datetime', isset($booking) ? $booking->booking_datetime->timezone('Asia/Manila')->format('Y-m-d\TH:i') : '') }}"
            required
        />
        <x-ui.field-error name="booking_datetime" />
    </div>

    <div>
        <x-ui.label for="pickup_address">Pickup address</x-ui.label>
        <x-ui.input id="pickup_address" name="pickup_address" value="{{ old('pickup_address', $booking?->pickup_address) }}" required />
        <x-ui.field-error name="pickup_address" />
    </div>

    <div>
        <x-ui.label for="dropoff_address">Dropoff address</x-ui.label>
        <x-ui.input id="dropoff_address" name="dropoff_address" value="{{ old('dropoff_address', $booking?->dropoff_address) }}" required />
        <x-ui.field-error name="dropoff_address" />
    </div>

    <div class="md:col-span-2">
        @include('bookings._map_placeholder', [
            'booking' => $booking ?? null,
            'routeMap' => $tripRouteMap,
        ])
        <x-ui.field-error name="pickup_lat" />
        <x-ui.field-error name="pickup_lng" />
        <x-ui.field-error name="dropoff_lat" />
        <x-ui.field-error name="dropoff_lng" />
    </div>

    <div>
        <x-ui.label for="cargo_desc">Cargo description</x-ui.label>
        <x-ui.textarea id="cargo_desc" name="cargo_desc" rows="3">{{ old('cargo_desc', $booking?->cargo_desc) }}</x-ui.textarea>
        <x-ui.field-error name="cargo_desc" />
    </div>

    <div>
        <x-ui.label for="additional_requirements">Additional requirements</x-ui.label>
        <x-ui.textarea id="additional_requirements" name="additional_requirements" rows="2">{{ old('additional_requirements', $booking?->additional_requirements) }}</x-ui.textarea>
        <x-ui.field-error name="additional_requirements" />
    </div>
</div>
