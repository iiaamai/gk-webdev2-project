@props([
    'booking',
    'ctaLabel' => 'View',
    'viewOnly' => false,
])

@php
    $statusTone = match ($booking->status->value) {
        'in_transit' => 'info',
        'accepted' => 'success',
        'completed' => 'neutral',
        'cancelled' => 'danger',
        default => 'warning',
    };
@endphp

<x-ui.card {{ $attributes->class([
    'flex h-full flex-col',
    'opacity-70' => $viewOnly,
]) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <p class="text-base font-semibold text-text">{{ $booking->booking_number }}</p>
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($viewOnly)
                <x-ui.badge tone="neutral">View only</x-ui.badge>
            @endif
            <x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge>
        </div>
    </div>

    <dl class="mt-4 grid flex-1 gap-2 text-sm">
        <div>
            <dt class="text-text-muted">Pickup</dt>
            <dd class="mt-0.5 text-text">{{ Str::limit($booking->pickup_address, 64) }}</dd>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <dt class="text-text-muted">Vehicle type</dt>
                <dd class="mt-0.5 text-text">{{ $booking->vehicle_type }}</dd>
            </div>
            <div>
                <dt class="text-text-muted">Payout</dt>
                <dd class="mt-0.5 font-medium text-text">₱{{ number_format((float) $booking->payout, 2) }}</dd>
            </div>
        </div>
    </dl>

    <div class="mt-4">
        <x-ui.button
            href="{{ route('driver.deliveries.show', $booking) }}"
            :variant="$viewOnly ? 'ghost' : 'secondary'"
            class="!py-1.5 !text-xs"
        >
            {{ $ctaLabel }}
        </x-ui.button>
    </div>
</x-ui.card>
