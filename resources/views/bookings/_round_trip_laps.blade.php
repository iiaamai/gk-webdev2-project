@php
    use App\Support\BookingMultiTrip;

    if (! BookingMultiTrip::showLapProgress($booking)) {
        return;
    }

    $completed = BookingMultiTrip::lapsCompleted($booking);
    $total = BookingMultiTrip::effectiveRoundTripsTotal($booking);
    $percent = $total > 0 ? min(100, (int) round(($completed / $total) * 100)) : 0;
    $allDone = BookingMultiTrip::allRoundTripLapsCompleted($booking);
@endphp

<x-ui.card class="mb-6 border-2 border-primary/40 bg-primary-subtle">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-primary-shade-1">Round trip progress</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-text sm:text-4xl">
                {{ $completed }}<span class="text-xl font-semibold text-text-muted sm:text-2xl"> / {{ $total }}</span>
            </p>
            <p class="mt-2 text-sm text-text-muted">
                @if ($allDone)
                    All round trips marked complete. Driver can mark transit completed next.
                @elseif (auth()->user()?->isDriver() && auth()->id() === $booking->driver_id)
                    Mark each finished round trip in <strong>Next step</strong> before transit completed.
                @else
                    Driver marks each completed round trip during the haul. Everyone sees the same count.
                @endif
            </p>
        </div>
        <div class="w-full sm:max-w-xs">
            <div class="h-3 overflow-hidden rounded-full bg-border">
                <div
                    class="h-full rounded-full bg-primary transition-all duration-300"
                    style="width: {{ $percent }}%"
                    role="progressbar"
                    aria-valuenow="{{ $completed }}"
                    aria-valuemin="0"
                    aria-valuemax="{{ $total }}"
                    aria-label="Round trips completed"
                ></div>
            </div>
            <p class="mt-2 text-center text-xs font-medium text-text-muted">{{ $percent }}% of approved round trips</p>
        </div>
    </div>
</x-ui.card>
