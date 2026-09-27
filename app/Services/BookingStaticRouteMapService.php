<?php

namespace App\Services;

use App\Data\StaticRouteMapResult;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\MapboxIntegration;
use Illuminate\Support\Facades\Http;

class BookingStaticRouteMapService
{
    public function forBooking(Booking $booking): StaticRouteMapResult
    {
        if (! $this->isEligible($booking)) {
            return new StaticRouteMapResult(
                configured: MapboxIntegration::isConfigured(),
                eligible: false,
                message: 'Route map is available after a driver accepts this booking.',
            );
        }

        if (! MapboxIntegration::isConfigured()) {
            return new StaticRouteMapResult(
                configured: false,
                eligible: true,
                message: MapboxIntegration::isEnabled()
                    ? 'MAPBOX_TOKEN is not set.'
                    : 'Mapbox integration is disabled (GK_MAPBOX_ENABLED=false).',
            );
        }

        return $this->fetchRouteMap($booking);
    }

    public function isEligible(Booking $booking): bool
    {
        if ($booking->driver_id === null) {
            return false;
        }

        return in_array($booking->status, [
            BookingStatus::Accepted,
            BookingStatus::InTransit,
            BookingStatus::Completed,
        ], true);
    }

    private function fetchRouteMap(Booking $booking): StaticRouteMapResult
    {
        $token = MapboxIntegration::accessToken();
        $coordinates = sprintf(
            '%s,%s;%s,%s',
            $booking->pickup_lng,
            $booking->pickup_lat,
            $booking->dropoff_lng,
            $booking->dropoff_lat,
        );

        $response = Http::timeout(15)->get(
            'https://api.mapbox.com/directions/v5/mapbox/driving/'.$coordinates,
            [
                'geometries' => 'polyline',
                'overview' => 'full',
                'access_token' => $token,
            ],
        );

        if (! $response->successful()) {
            return new StaticRouteMapResult(
                configured: true,
                eligible: true,
                message: 'Unable to load route from Mapbox.',
            );
        }

        $route = $response->json('routes.0');

        if (! is_array($route) || ! isset($route['geometry'])) {
            return new StaticRouteMapResult(
                configured: true,
                eligible: true,
                message: 'Mapbox returned no route for these coordinates.',
            );
        }

        $polyline = (string) $route['geometry'];
        $style = (string) config('gk.mapbox_style', 'mapbox/streets-v12');
        $path = 'path-5+1e40af-0.75('.rawurlencode($polyline).')';
        $imageUrl = sprintf(
            'https://api.mapbox.com/styles/v1/%s/static/%s/auto/640x400@2x?padding=48&access_token=%s',
            $style,
            $path,
            $token,
        );

        $distanceKm = isset($route['distance']) ? round((float) $route['distance'] / 1000, 1) : null;
        $durationMinutes = isset($route['duration']) ? (int) round((float) $route['duration'] / 60) : null;

        return new StaticRouteMapResult(
            configured: true,
            eligible: true,
            imageUrl: $imageUrl,
            distanceKm: $distanceKm,
            durationMinutes: $durationMinutes,
        );
    }
}
