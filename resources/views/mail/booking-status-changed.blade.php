<x-mail::message>
# Booking {{ $status->value }}

Hello {{ $audience === 'driver' ? 'Driver' : ($booking->customer?->name ?? 'Customer') }},

Booking **{{ $booking->booking_number }}** is now **{{ $status->value }}**.

- **Pickup:** {{ $booking->pickup_address }}
- **Dropoff:** {{ $booking->dropoff_address }}

@if ($status->value === 'cancelled')
If you have questions, contact support through the app.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
