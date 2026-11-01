<?php

namespace App\Support;

use App\Models\Pricing;

class BookingTripPricing
{
    /**
     * Invoice at booking create: always one list fare (multi-trip priced after pickup approval).
     */
    public static function invoiceAmount(Pricing $pricing, int $cargoKg): float
    {
        return BookingMultiTrip::baseInvoiceAmount($pricing);
    }

    public static function tripCount(int $cargoKg, int $capacityKg): int
    {
        if ($capacityKg < 1) {
            return 1;
        }

        return max(1, (int) ceil($cargoKg / $capacityKg));
    }
}
