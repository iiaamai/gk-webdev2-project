@php
    use App\Enums\BookingStatus;
    use App\Support\BookingMultiTrip;

    $horizontalScroll = $horizontalScroll ?? false;

    $status = $booking->status;
    $hasGatepass = $booking->hasGatepass();

    $steps = [
        ['key' => 'pending', 'label' => 'Pending'],
        ['key' => 'gatepass', 'label' => 'Gatepass'],
        ['key' => 'accepted', 'label' => 'Accepted'],
        ['key' => 'transit_to_pickup', 'label' => 'To pickup'],
        ['key' => 'arrived_at_pickup', 'label' => 'At pickup'],
        ['key' => 'in_transit', 'label' => 'Haul'],
        ['key' => 'transit_completed', 'label' => 'Transit done'],
        ['key' => 'completed', 'label' => 'Completed'],
    ];

    $currentIndex = match (true) {
        $status === BookingStatus::Cancelled => -1,
        $status === BookingStatus::Completed => 7,
        $status === BookingStatus::TransitCompleted => 6,
        $status === BookingStatus::InTransit => 5,
        $status === BookingStatus::ArrivedAtPickup => 4,
        $status === BookingStatus::TransitToPickup => 3,
        $status === BookingStatus::Accepted => 2,
        $status === BookingStatus::Pending && $hasGatepass => 1,
        default => 0,
    };
@endphp

@if ($status === BookingStatus::Cancelled)
    <div class="mb-6 rounded-md border border-danger/20 bg-danger-subtle px-4 py-3 text-sm text-danger" role="status">
        This booking was cancelled.
    </div>
@else
    <nav class="mb-6" aria-label="Trip progress">
        <div @class(['relative' => ! $horizontalScroll])>
            <ol @class([
                '-mx-1 flex gap-2 overflow-x-auto pb-2 scroll-smooth',
                'snap-x snap-mandatory' => $horizontalScroll,
                'sm:mx-0 sm:flex-wrap sm:overflow-visible sm:pb-0 sm:snap-none' => ! $horizontalScroll,
            ])>
            @foreach ($steps as $index => $step)
                @php
                    $done = $index < $currentIndex;
                    $current = $index === $currentIndex;
                @endphp
                <li @class([
                    'flex shrink-0 items-center gap-2',
                    'snap-start' => $horizontalScroll,
                    'sm:min-w-0 sm:flex-1' => ! $horizontalScroll,
                ])>
                    <div
                        @class([
                            'flex items-center gap-2 rounded-md px-2 py-1.5 text-xs font-medium sm:text-sm',
                            'shrink-0 whitespace-nowrap' => $horizontalScroll,
                            'min-w-[5.5rem] sm:min-w-0' => ! $horizontalScroll,
                            'bg-success-subtle text-success' => $done,
                            'bg-primary-tone-1 text-primary' => $current,
                            'bg-surface-inset text-text-muted' => ! $done && ! $current,
                        ])
                        @if ($current) aria-current="step" @endif
                    >
                        <span
                            @class([
                                'inline-flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold',
                                'bg-success text-white' => $done,
                                'bg-primary text-text-on-primary' => $current,
                                'bg-border text-text-muted' => ! $done && ! $current,
                            ])
                        >
                            @if ($done)
                                ✓
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span @class(['truncate' => ! $horizontalScroll])>{{ $step['label'] }}</span>
                    </div>
                    @if (! $loop->last)
                        <span @class([
                            'shrink-0 text-border',
                            'hidden sm:inline' => ! $horizontalScroll,
                        ]) aria-hidden="true">—</span>
                    @endif
                </li>
            @endforeach
            </ol>
            @if (! $horizontalScroll)
                <div class="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-surface to-transparent sm:hidden" aria-hidden="true"></div>
            @endif
        </div>
    </nav>

    @if (in_array($status, [BookingStatus::ArrivedAtPickup, BookingStatus::InTransit], true) && BookingMultiTrip::multiTripBlocksCompletion($booking))
        <p class="mb-4 text-sm text-text-muted">Round-trip request in progress — finish office and driver steps before transit completed.</p>
    @endif
@endif
