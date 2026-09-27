<x-mail::message>
# Booking created

Hello {{ $booking->customer?->name ?? 'Customer' }},

Your booking **{{ $booking->booking_number }}** has been created.

- **Vehicle type:** {{ $booking->vehicle_type }}
- **Pickup:** {{ $booking->pickup_address }}
- **Dropoff:** {{ $booking->dropoff_address }}
- **Payout:** ₱{{ number_format((float) $booking->payout, 2) }}

We'll notify you when a gatepass is issued and a driver accepts.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
