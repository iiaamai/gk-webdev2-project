@props([
    'booking',
    'compact' => false,
])

@php
    use App\Enums\BookingStatus;

    $hasPickup = $booking->pickup_lat !== null && $booking->pickup_lng !== null;
    $hasDropoff = $booking->dropoff_lat !== null && $booking->dropoff_lng !== null;
    $isCancelled = $booking->status === BookingStatus::Cancelled;
@endphp

@if (! $isCancelled && ($hasPickup || $hasDropoff))
    <div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
        @if ($hasPickup)
            <x-ui.button
                href="{{ route('driver.deliveries.show', [$booking, 'nav' => 'pickup']) }}"
                variant="secondary"
                class="{{ $compact ? '!py-1.5 !text-xs' : '' }}"
            >
                <x-ui.icon name="map-pin" size="size-4" />
                Navigate to pickup
            </x-ui.button>
        @endif
        @if ($hasDropoff)
            <x-ui.button
                href="{{ route('driver.deliveries.show', [$booking, 'nav' => 'dropoff']) }}"
                variant="secondary"
                class="{{ $compact ? '!py-1.5 !text-xs' : '' }}"
            >
                <x-ui.icon name="map-pin" size="size-4" />
                Navigate to dropoff
            </x-ui.button>
        @endif
    </div>
@endif
