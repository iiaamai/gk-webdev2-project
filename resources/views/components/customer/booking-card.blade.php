@props([
    'booking',
])

@php
    $statusTone = match ($booking->status->value) {
        'pending' => 'warning',
        'accepted', 'in_transit' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
        default => 'neutral',
    };
@endphp

<x-ui.card {{ $attributes->class(['flex h-full flex-col']) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <p class="text-base font-semibold text-text">{{ $booking->booking_number }}</p>
        <x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge>
    </div>

    <dl class="mt-4 grid flex-1 gap-2 text-sm">
        <div>
            <dt class="text-text-muted">Route</dt>
            <dd class="mt-0.5 text-text">
                {{ Str::limit($booking->pickup_address, 40) }}
                →
                {{ Str::limit($booking->dropoff_address, 40) }}
            </dd>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <dt class="text-text-muted">Vehicle type</dt>
                <dd class="mt-0.5 text-text">{{ $booking->vehicle_type }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Amount</dt>
                <dd class="mt-0.5 font-medium text-text">₱{{ number_format((float) $booking->payout, 2) }}</dd>
            </div>
        </div>
        @if ($booking->booking_datetime)
            <div>
                <dt class="text-text-muted">Preferred pickup</dt>
                <dd class="mt-0.5 text-text">{{ $booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}</dd>
            </div>
        @endif
    </dl>

    <div class="mt-4">
        <x-ui.button href="{{ route('customer.bookings.show', $booking) }}" variant="secondary" class="!py-1.5 !text-xs">
            View details
        </x-ui.button>
    </div>
</x-ui.card>
