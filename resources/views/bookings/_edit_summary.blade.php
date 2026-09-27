@php
    $thin = $thin ?? false;
    $statusTone = match ($booking->status->value) {
        'pending' => 'warning',
        'accepted', 'in_transit' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
        default => 'neutral',
    };
@endphp

<div class="space-y-3">
    @if ($thin)
        <x-ui.section-heading icon="clipboard-list" title="Booking summary" />
    @else
        <x-ui.section-heading
            icon="clipboard-list"
            title="Trip snapshot"
            description="Key facts while you edit. Payout updates when vehicle type is saved."
        />
    @endif

    @if ($thin)
        <dl class="grid grid-cols-[6.5rem_1fr] gap-x-4 gap-y-2 text-sm">
            <dt class="text-text-muted">Customer</dt>
            <dd class="font-medium text-text">{{ $booking->customer->name }}</dd>

            <dt class="text-text-muted">Status</dt>
            <dd><x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge></dd>

            <dt class="text-text-muted">Driver</dt>
            <dd class="font-medium text-text">{{ $booking->driver?->name ?? 'Unassigned' }}</dd>

            <dt class="text-text-muted">Gatepass</dt>
            <dd>
                @if ($booking->hasGatepass())
                    <x-ui.badge tone="success">Uploaded</x-ui.badge>
                @else
                    <x-ui.badge tone="warning">Missing</x-ui.badge>
                @endif
            </dd>
        </dl>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-md border border-border bg-surface-inset p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Customer</p>
                <p class="mt-1 font-medium text-text">{{ $booking->customer->name }}</p>
                <p class="text-xs text-text-muted">{{ $booking->customer->email }}</p>
            </div>
            <div class="rounded-md border border-border bg-surface-inset p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Status</p>
                <p class="mt-1"><x-ui.badge :tone="$statusTone">{{ $booking->status->value }}</x-ui.badge></p>
            </div>
            <div class="rounded-md border border-border bg-surface-inset p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Payout (read-only)</p>
                <p class="mt-1 text-lg font-semibold text-text">₱{{ number_format((float) $booking->payout, 2) }}</p>
            </div>
            <div class="rounded-md border border-border bg-surface-inset p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Driver</p>
                <p class="mt-1 font-medium text-text">{{ $booking->driver?->name ?? 'Unassigned' }}</p>
            </div>
            <div class="rounded-md border border-border bg-surface-inset p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Gatepass</p>
                <p class="mt-1">
                    @if ($booking->hasGatepass())
                        <a href="{{ route('documents.bookings.gatepass', $booking) }}" class="font-medium text-primary hover:text-primary-shade-1">Download current</a>
                    @else
                        <span class="text-text-muted">Not uploaded</span>
                    @endif
                </p>
            </div>
            <div class="rounded-md border border-border bg-surface-inset p-3 sm:col-span-2">
                <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">Preferred pickup</p>
                <p class="mt-1 font-medium text-text">
                    {{ $booking->booking_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}
                </p>
                <p class="mt-1 text-xs text-text-muted">Vehicle type: {{ $booking->vehicle_type }}</p>
            </div>
        </div>
    @endif
</div>
