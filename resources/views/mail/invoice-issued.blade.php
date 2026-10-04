<x-mail::message>
# Invoice issued

Hello {{ $invoice->booking?->customer?->name ?? 'Customer' }},

An unpaid invoice was issued for booking **{{ $invoice->booking?->booking_number }}**.

- **Amount:** ₱{{ number_format((float) $invoice->amount, 2) }}
- **Status:** unpaid

Staff will mark it paid when settlement is received.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
