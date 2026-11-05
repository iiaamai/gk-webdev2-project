<?php

namespace App\Support;

/**
 * Metric tons display and form conversion (1 ton = 1,000 kg). Storage remains kg.
 */
class MetricWeight
{
    public const KG_PER_TON = 1000;

    public static function kgToTons(int|float|null $kg): float
    {
        if ($kg === null) {
            return 0.0;
        }

        return round((float) $kg / self::KG_PER_TON, 3);
    }

    public static function tonsToKg(float $tons): int
    {
        return (int) max(1, round($tons * self::KG_PER_TON));
    }

    public static function formatTons(int|float|null $kg, int $decimals = 0): string
    {
        if ($kg === null) {
            return '—';
        }

        return number_format(self::kgToTons($kg), $decimals);
    }

    public static function capacityLabel(int $capacityKg): string
    {
        return self::formatTons($capacityKg).' tons capacity';
    }

    public static function weightLabel(int $cargoKg): string
    {
        return self::formatTons($cargoKg).' tons';
    }
}
