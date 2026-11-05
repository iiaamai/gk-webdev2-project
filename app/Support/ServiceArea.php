<?php

namespace App\Support;

/**
 * Metro Manila (NCR) service area — pickup and dropoff must fall inside this box.
 */
class ServiceArea
{
    public const MIN_LAT = 14.45;

    public const MAX_LAT = 14.73;

    public const MIN_LNG = 120.92;

    public const MAX_LNG = 121.12;

    public static function contains(float $lat, float $lng): bool
    {
        return $lat >= self::MIN_LAT
            && $lat <= self::MAX_LAT
            && $lng >= self::MIN_LNG
            && $lng <= self::MAX_LNG;
    }

    /**
     * Mapbox maxBounds: southwest corner, northeast corner.
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}
     */
    public static function maxBounds(): array
    {
        return [
            [self::MIN_LNG, self::MIN_LAT],
            [self::MAX_LNG, self::MAX_LAT],
        ];
    }

    public static function label(): string
    {
        return 'Metro Manila (NCR)';
    }

    public static function outOfAreaMessage(): string
    {
        return 'Location must be within '.self::label().'.';
    }
}
