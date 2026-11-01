<?php

namespace App\Support;

use App\Models\Pricing;

class BookingTripPricing
{
    public static function tripCount(int $cargoKg, int $capacityKg): int
    {
        if ($capacityKg < 1) {
            return 1;
        }

        return max(1, (int) ceil($cargoKg / $capacityKg));
    }

    public static function invoiceAmount(Pricing $pricing, int $cargoKg): float
    {
        $trips = self::tripCount($cargoKg, (int) $pricing->capacity_kg);

        return round($trips * (float) $pricing->amount, 2);
    }
}
