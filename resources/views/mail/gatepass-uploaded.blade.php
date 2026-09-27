<x-mail::message>
# New job available

Hello,

A gatepass was uploaded for booking **{{ $booking->booking_number }}** matching your vehicle type ({{ $booking->vehicle_type }}).

- **Pickup:** {{ $booking->pickup_address }}
- **Dropoff:** {{ $booking->dropoff_address }}
- **Payout:** ₱{{ number_format((float) $booking->payout, 2) }}

Sign in to the driver portal to view and accept available deliveries.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
