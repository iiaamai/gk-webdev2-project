<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProofStatus;
use App\Models\Invoice;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectInvoicePaymentProof
{
    public function __construct(
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Invoice $invoice, array $data = []): Invoice
    {
        $invoice->loadMissing('booking');

        if ($invoice->status !== InvoiceStatus::Unpaid) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is already paid.',
            ]);
        }

        if ($invoice->payment_proof_status !== PaymentProofStatus::Pending) {
            throw ValidationException::withMessages([
                'invoice' => 'There is no payment screenshot waiting for review.',
            ]);
        }

        $invoice = DB::transaction(function () use ($invoice, $data): Invoice {
            $payload = [
                'payment_proof_status' => PaymentProofStatus::Rejected,
            ];

            if (array_key_exists('notes', $data)) {
                $payload['notes'] = $data['notes'];
            }

            $invoice->update($payload);

            return $invoice->fresh();
        });

        $this->bookingEmailNotifier->paymentProofRejected($invoice);

        $invoice->loadMissing('booking');
        $this->activityLogger->log(
            action: 'invoice.payment_proof_rejected',
            subject: $invoice->booking,
            description: 'Payment screenshot rejected for booking '.($invoice->booking?->booking_number ?? $invoice->booking_id).'.',
            properties: [
                'invoice_id' => $invoice->id,
            ],
        );

        return $invoice;
    }
}
