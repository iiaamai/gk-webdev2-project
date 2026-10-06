@php
    use App\Enums\BookingStatus;

    $status = $booking->status;
    $hasGatepass = $booking->hasGatepass();

    $steps = [
        ['key' => 'pending', 'label' => 'Pending'],
        ['key' => 'gatepass', 'label' => 'Gatepass'],
        ['key' => 'accepted', 'label' => 'Accepted'],
        ['key' => 'in_transit', 'label' => 'In transit'],
        ['key' => 'completed', 'label' => 'Completed'],
    ];

    $currentIndex = match (true) {
        $status === BookingStatus::Cancelled => -1,
        $status === BookingStatus::Completed => 4,
        $status === BookingStatus::InTransit => 3,
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
        <ol class="flex flex-wrap items-center gap-2 sm:gap-0">
            @foreach ($steps as $index => $step)
                @php
                    $done = $index < $currentIndex;
                    $current = $index === $currentIndex;
                @endphp
                <li class="flex items-center gap-2 sm:flex-1">
                    <div
                        @class([
                            'flex min-w-0 items-center gap-2 rounded-md px-2 py-1.5 text-xs font-medium sm:text-sm',
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
                        <span class="truncate">{{ $step['label'] }}</span>
                    </div>
                    @if (! $loop->last)
                        <span class="hidden text-border sm:inline" aria-hidden="true">—</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
