<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\BookingEmailNotifier;
use App\Support\BookingTripPricing;
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

            $cargoKg = (int) ($booking->cargo_kg ?? 0);
            $amount = $booking->pricing !== null && $cargoKg > 0
                ? BookingTripPricing::invoiceAmount($booking->pricing, $cargoKg)
                : (float) ($booking->pricing?->amount ?? 0);

            return Invoice::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $amount,
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
