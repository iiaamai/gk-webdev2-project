<?php

namespace App\Support;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class PaymentDue
{
    private const string TIMEZONE = 'Asia/Manila';

    private const int DUE_DAYS = 5;

    /**
     * Last moment payment is due (end of the 5th calendar day from creation, Manila).
     */
    public static function dueAt(Booking $booking): ?Carbon
    {
        if ($booking->created_at === null) {
            return null;
        }

        return $booking->created_at
            ->timezone(self::TIMEZONE)
            ->startOfDay()
            ->addDays(self::DUE_DAYS - 1)
            ->endOfDay();
    }

    public static function dueDateLabel(Booking $booking): ?string
    {
        $dueAt = self::dueAt($booking);

        return $dueAt?->format('M j, Y');
    }

    public static function isUnpaid(Booking $booking): bool
    {
        $booking->loadMissing('invoice');

        return $booking->invoice !== null
            && $booking->invoice->status === InvoiceStatus::Unpaid;
    }

    public static function isOverdue(Booking $booking): bool
    {
        if (! self::isUnpaid($booking)) {
            return false;
        }

        $dueAt = self::dueAt($booking);

        if ($dueAt === null) {
            return false;
        }

        return now(self::TIMEZONE)->greaterThan($dueAt);
    }

    /**
     * @return 'paid'|'unpaid'|null null when no invoice
     */
    public static function paymentChip(Booking $booking): ?string
    {
        $booking->loadMissing('invoice');

        if ($booking->invoice === null) {
            return null;
        }

        return $booking->invoice->status === InvoiceStatus::Paid ? 'paid' : 'unpaid';
    }
}
