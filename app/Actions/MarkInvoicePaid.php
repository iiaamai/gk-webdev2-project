<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\BookingEmailNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkInvoicePaid
{
    public function __construct(
        private readonly BookingEmailNotifier $bookingEmailNotifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Invoice $invoice, array $data = []): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is already marked as paid.',
            ]);
        }

        $invoice = DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now('Asia/Manila'),
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            return $invoice->fresh();
        });

        $this->bookingEmailNotifier->invoicePaid($invoice);

        return $invoice;
    }
}
