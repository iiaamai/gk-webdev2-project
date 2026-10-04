<?php

namespace App\Support;

use App\Models\Setting;

/**
 * External Mapbox integration gate (see docs/development/development_notes.txt).
 * While placeholder: route helpers return not-configured; no HTTP to Mapbox.
 */
class MapboxIntegration
{
    public static function isEnabled(): bool
    {
        return (bool) config('gk.mapbox_enabled', false);
    }

    public static function accessToken(): ?string
    {
        $token = config('gk.mapbox_token');

        return filled($token) ? (string) $token : null;
    }

    public static function isConfigured(): bool
    {
        return self::isEnabled() && self::accessToken() !== null;
    }

    /**
     * Default camera center as [lng, lat] for Mapbox GL (Settings, then config).
     *
     * @return array{0: float, 1: float}
     */
    public static function defaultCenter(): array
    {
        $lat = (float) Setting::getValue(
            'map_center_lat',
            (string) config('gk.mapbox_center_lat', 14.5995),
        );
        $lng = (float) Setting::getValue(
            'map_center_lng',
            (string) config('gk.mapbox_center_lng', 120.9842),
        );

        return [$lng, $lat];
    }

    public static function initialZoom(): float
    {
        return (float) config('gk.mapbox_initial_zoom', 11);
    }
}
