<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CreateBookingInvoice
{
    public function execute(Booking $booking): Invoice
    {
        return DB::transaction(function () use ($booking): Invoice {
            return Invoice::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->payout,
                    'status' => InvoiceStatus::Unpaid,
                    'issued_at' => now('Asia/Manila'),
                    'paid_at' => null,
                ],
            );
        });
    }
}
