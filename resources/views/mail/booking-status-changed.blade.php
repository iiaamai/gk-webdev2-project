<x-mail::message>
# Booking {{ $status->label() }}

Hello {{ $audience === 'driver' ? 'Driver' : ($booking->customer?->name ?? 'Customer') }},

Booking **{{ $booking->booking_number }}** is now **{{ $status->label() }}**.

- **Pickup:** {{ $booking->pickup_address }}
- **Dropoff:** {{ $booking->dropoff_address }}

@if ($status->value === 'cancelled')
If you have questions, contact support through the app.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
