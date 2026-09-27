<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\BookingEmailNotifier;
use Illuminate\Support\Facades\DB;

class CreateBookingInvoice
{
    public function __construct(
        private readonly BookingEmailNotifier $bookingEmailNotifier,
    ) {}

    public function execute(Booking $booking): Invoice
    {
        $invoice = DB::transaction(function () use ($booking): Invoice {
            $booking->loadMissing('pricing');

            return Invoice::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->pricing?->amount ?? 0,
                    'status' => InvoiceStatus::Unpaid,
                    'issued_at' => now('Asia/Manila'),
                    'paid_at' => null,
                ],
            );
        });

        $this->bookingEmailNotifier->invoiceIssued($invoice);

        return $invoice;
    }
}
