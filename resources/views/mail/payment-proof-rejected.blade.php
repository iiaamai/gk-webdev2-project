<x-mail::message>
# Payment did not reflect

Hello {{ $invoice->booking?->customer?->name ?? 'Customer' }},

We could not confirm that payment for booking **{{ $invoice->booking?->booking_number }}** arrived in our bank or e-wallet account.

- **Amount:** ₱{{ number_format((float) $invoice->amount, 2) }}

Please check the transfer in your GCash, Maya, or bank app, then upload a new screenshot on the unpaid invoice in your customer portal.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
