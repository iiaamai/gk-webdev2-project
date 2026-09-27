@if ($booking->invoice)
    @can('view', $booking->invoice)
        <h2>Invoice (Pay Later)</h2>
        <dl>
            <dt>Amount</dt><dd>₱{{ number_format((float) $booking->invoice->amount, 2) }}</dd>
            <dt>Status</dt><dd>{{ $booking->invoice->status->value }}</dd>
            @if ($booking->invoice->issued_at)
                <dt>Issued</dt><dd>{{ $booking->invoice->issued_at->timezone('Asia/Manila')->format('Y-m-d H:i') }}</dd>
            @endif
            @if ($booking->invoice->paid_at)
                <dt>Paid</dt><dd>{{ $booking->invoice->paid_at->timezone('Asia/Manila')->format('Y-m-d H:i') }}</dd>
            @endif
            @if ($booking->invoice->notes)
                <dt>Notes</dt><dd>{{ $booking->invoice->notes }}</dd>
            @endif
        </dl>
        <p><em>Online payment (PayMongo) is not enabled in MVP.</em></p>
    @endcan

    @can('markAsPaid', $booking->invoice)
        @if (! empty($markPaidAction ?? null))
        <h3>Mark invoice paid</h3>
        <form method="post" action="{{ $markPaidAction }}">
            @csrf
            <label for="invoice_notes">Notes (optional)</label>
            <textarea id="invoice_notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            <button type="submit" onclick="return confirm('Mark this invoice as paid?');">Mark as paid</button>
        </form>
        @endif
    @endcan
@else
    <p><em>No invoice on file for this booking.</em></p>
@endif
