@php
    use App\Support\BookingStatusCallout;

    $callout = BookingStatusCallout::forCustomer($booking);
@endphp

@if ($callout)
    @php
        $toneClasses = match ($callout['tone']) {
            'warning' => 'border-warning/30 bg-warning-subtle text-warning',
            'success' => 'border-success/30 bg-success-subtle text-success',
            default => 'border-primary/20 bg-primary-tone-1 text-text',
        };
    @endphp
    <div class="mb-6 rounded-md border px-4 py-3 text-sm {{ $toneClasses }}" role="status">
        {{ $callout['message'] }}
    </div>
@endif
