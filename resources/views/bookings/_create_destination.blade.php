@php
    $pickupAddress = old('pickup_address');
    $dropoffAddress = old('dropoff_address');
@endphp

<div class="space-y-4">
    <x-ui.section-heading
        icon="map-pin"
        title="Destination & route"
        description="Pickup and dropoff for this booking."
    />

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="flex items-center gap-1.5 text-sm font-medium uppercase tracking-wide text-text-subtle">
                <x-ui.icon name="map-pin" size="size-5" class="fill-primary stroke-white" />
                Pickup
            </p>
            <p class="mt-1 text-sm font-medium text-text">{{ $pickupAddress ?: '—' }}</p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="flex items-center gap-1.5 text-sm font-medium uppercase tracking-wide text-text-subtle">
                <x-ui.icon name="map-pin" size="size-5" class="fill-success stroke-white" />
                Dropoff
            </p>
            <p class="mt-1 text-sm font-medium text-text">{{ $dropoffAddress ?: '—' }}</p>
        </div>
    </div>

    @include('bookings._map_placeholder')
</div>
