<?php

namespace App\Support;

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
}
