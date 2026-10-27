@extends('layouts.driver-nav')

@section('title', 'Navigate · '.$booking->booking_number)

@section('content')
    @php
        $destinationLabel = $navDestination === 'pickup' ? 'Pickup' : 'Dropoff';
        $destinationAddress = $navDestination === 'pickup'
            ? $booking->pickup_address
            : $booking->dropoff_address;
    @endphp

    <div class="relative h-dvh w-full">
        @if ($routeMap?->hasRoute())
            @include('bookings._interactive_route_map', [
                'booking' => $booking,
                'routeMap' => $routeMap,
                'mapSize' => 'fullscreen',
                'liveNav' => true,
                'navDestination' => $navDestination,
            ])
        @else
            @include('bookings._map_placeholder', [
                'booking' => $booking,
                'routeMap' => $routeMap,
                'mapSize' => 'fullscreen',
            ])
        @endif

        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-20 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
            <div class="pointer-events-auto rounded-md border border-border bg-surface-elevated/95 p-4 shadow-lg">
                <p class="text-sm font-medium uppercase tracking-wide text-text-subtle">{{ $destinationLabel }}</p>
                <p class="mt-1 text-base font-semibold text-text">{{ $destinationAddress }}</p>
                <p class="mt-2 text-lg font-semibold text-text" data-nav-eta>— km · — min</p>
                <p class="mt-1 text-sm text-text-muted" data-driver-nav-status>Allow location to start following the route.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" variant="secondary" data-nav-recenter>
                        Recenter
                    </x-ui.button>
                    <x-ui.button href="{{ route('driver.deliveries.show', $booking) }}" variant="primary">
                        Exit
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
@endsection
