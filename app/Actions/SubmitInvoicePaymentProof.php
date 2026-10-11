<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProofStatus;
use App\Models\Invoice;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitInvoicePaymentProof
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Invoice $invoice, UploadedFile $file): Invoice
    {
        $invoice->loadMissing('booking');

        if ($invoice->status !== InvoiceStatus::Unpaid) {
            throw ValidationException::withMessages([
                'proof' => 'This invoice is already paid.',
            ]);
        }

        if ($invoice->payment_proof_status === PaymentProofStatus::Pending) {
            throw ValidationException::withMessages([
                'proof' => 'Your payment screenshot is already awaiting review.',
            ]);
        }

        $booking = $invoice->booking;

        if ($booking === null) {
            throw ValidationException::withMessages([
                'proof' => 'This invoice is missing a booking.',
            ]);
        }

        $invoice = DB::transaction(function () use ($invoice, $booking, $file): Invoice {
            $directory = 'bookings/'.$booking->booking_number;
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = 'payment-proof.'.$extension;
            $path = $directory.'/'.$filename;

            if (filled($invoice->payment_proof_path)) {
                Storage::disk('local')->delete($invoice->payment_proof_path);
            }

            Storage::disk('local')->putFileAs($directory, $file, $filename);

            $invoice->update([
                'payment_proof_path' => $path,
                'payment_proof_status' => PaymentProofStatus::Pending,
                'payment_proof_submitted_at' => now('Asia/Manila'),
            ]);

            return $invoice->fresh();
        });

        $this->activityLogger->log(
            action: 'invoice.payment_proof_submitted',
            subject: $booking,
            description: 'Payment screenshot submitted for booking '.$booking->booking_number.'.',
            properties: [
                'invoice_id' => $invoice->id,
            ],
        );

        return $invoice;
    }
}
