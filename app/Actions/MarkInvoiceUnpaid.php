<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkInvoiceUnpaid
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Invoice $invoice, array $data = []): Invoice
    {
        if ($invoice->status === InvoiceStatus::Unpaid) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is already marked as unpaid.',
            ]);
        }

        $invoice = DB::transaction(function () use ($invoice, $data): Invoice {
            $payload = [
                'status' => InvoiceStatus::Unpaid,
                'paid_at' => null,
            ];

            if (array_key_exists('notes', $data)) {
                $payload['notes'] = $data['notes'];
            }

            $invoice->update($payload);

            return $invoice->fresh();
        });

        $invoice->loadMissing('booking');
        $this->activityLogger->log(
            action: 'invoice.marked_unpaid',
            subject: $invoice->booking,
            description: 'Invoice reverted to unpaid for booking '.($invoice->booking?->booking_number ?? $invoice->booking_id).'.',
            properties: [
                'invoice_id' => $invoice->id,
                'amount' => (string) $invoice->amount,
            ],
        );

        return $invoice;
    }
}
