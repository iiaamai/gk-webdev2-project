@props([
    'booking',
])

@php
    use App\Support\BookingStatusCallout;
    use App\Support\PaymentDue;

    $statusTone = $booking->status->badgeTone();
    $callout = BookingStatusCallout::forCustomer($booking);
    $paymentChip = PaymentDue::paymentChip($booking);
    $invoice = $booking->relationLoaded('invoice') ? $booking->invoice : $booking->loadMissing('invoice')->invoice;
@endphp

<x-ui.card {{ $attributes->class(['flex h-full flex-col']) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <p class="text-base font-semibold text-text">{{ $booking->booking_number }}</p>
        <div class="flex flex-wrap justify-end gap-1.5">
            @if ($paymentChip !== null && $invoice !== null)
                <x-ui.badge :tone="$invoice->status->badgeTone()">{{ $invoice->status->label() }}</x-ui.badge>
            @endif
            <x-ui.badge :tone="$statusTone">{{ $booking->status->label() }}</x-ui.badge>
        </div>
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
        @if (PaymentDue::isUnpaid($booking))
            <div>
                <dt class="text-text-muted">Payment</dt>
                <dd class="mt-0.5 @if (PaymentDue::isOverdue($booking)) font-medium text-danger @else text-text @endif">
                    @if (PaymentDue::isOverdue($booking))
                        Overdue — pay via invoice on booking details
                    @else
                        Due by {{ PaymentDue::dueDateLabel($booking) }}
                    @endif
                </dd>
            </div>
        @endif
    </dl>

    @if ($callout)
        <p class="mt-3 text-sm text-text-muted">{{ Str::limit($callout['message'], 120) }}</p>
    @endif

    <div class="mt-4">
        <x-ui.button href="{{ route('customer.bookings.show', $booking) }}" variant="secondary" class="!py-1.5 !text-xs">
            View details
        </x-ui.button>
    </div>
</x-ui.card>
