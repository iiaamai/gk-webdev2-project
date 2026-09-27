@php
    $routeMap = $routeMap ?? null;
    $editMapSlot = $editMapSlot ?? false;
    $mapSize = $mapSize ?? 'default';
@endphp

<div class="space-y-4">
    <x-ui.section-heading
        icon="map-pin"
        title="Destination & route"
        description="Pickup and dropoff for this booking."
    />

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Pickup</p>
            <p class="mt-1 text-sm font-medium text-text">{{ $booking->pickup_address }}</p>
        </div>
        <div class="rounded-md border border-border bg-surface-inset p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Dropoff</p>
            <p class="mt-1 text-sm font-medium text-text">{{ $booking->dropoff_address }}</p>
        </div>
    </div>

    @if ($editMapSlot)
        @include('bookings._map_placeholder', [
            'booking' => $booking,
            'routeMap' => $routeMap,
            'mapSize' => $mapSize,
        ])
    @elseif ($routeMap !== null)
        @if ($routeMap->hasImage())
            <div class="overflow-hidden rounded-md border border-dashed border-border-strong bg-primary-tone-1">
                <div class="border-b border-border bg-surface-elevated px-4 py-2 text-sm text-text-muted">
                    @if ($routeMap->distanceKm !== null && $routeMap->durationMinutes !== null)
                        Approx. {{ $routeMap->distanceKm }} km · {{ $routeMap->durationMinutes }} min driving
                    @else
                        Route preview
                    @endif
                </div>
                <img
                    src="{{ $routeMap->imageUrl }}"
                    alt="Route from pickup to dropoff for {{ $booking->booking_number }}"
                    class="h-auto w-full max-h-80 object-cover"
                    width="640"
                    height="400"
                >
            </div>
        @else
            @include('bookings._map_placeholder', [
                'booking' => $booking,
                'routeMap' => $routeMap,
                'mapSize' => $mapSize,
            ])
        @endif
    @endif
</div>
