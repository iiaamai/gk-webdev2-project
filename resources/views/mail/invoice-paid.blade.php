<x-mail::message>
# Invoice paid

Hello {{ $invoice->booking?->customer?->name ?? 'Customer' }},

Your invoice for booking **{{ $invoice->booking?->booking_number }}** has been marked **paid**.

- **Amount:** ₱{{ number_format((float) $invoice->amount, 2) }}
@if ($invoice->paid_at)
- **Paid at:** {{ $invoice->paid_at->timezone('Asia/Manila')->format('Y-m-d H:i') }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
