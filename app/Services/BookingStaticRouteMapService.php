<?php

namespace App\Services;

use App\Data\StaticRouteMapResult;
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
                message: 'Pickup and dropoff coordinates are required to show this route.',
            );
        }

        if (! MapboxIntegration::isConfigured()) {
            return new StaticRouteMapResult(
                configured: false,
                eligible: true,
            );
        }

        return $this->fetchRouteMap($booking);
    }

    public function isEligible(Booking $booking): bool
    {
        return $booking->pickup_lat !== null
            && $booking->pickup_lng !== null
            && $booking->dropoff_lat !== null
            && $booking->dropoff_lng !== null;
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
                'geometries' => 'geojson',
                'overview' => 'full',
                'access_token' => $token,
            ],
        );

        if (! $response->successful()) {
            return new StaticRouteMapResult(
                configured: true,
                eligible: true,
                message: 'Could not load the route preview.',
            );
        }

        $route = $response->json('routes.0');
        $geometry = is_array($route) ? ($route['geometry'] ?? null) : null;

        if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'LineString' || ($geometry['coordinates'] ?? []) === []) {
            return new StaticRouteMapResult(
                configured: true,
                eligible: true,
                message: 'No route could be calculated for these addresses.',
            );
        }

        $distanceKm = isset($route['distance']) ? round((float) $route['distance'] / 1000, 1) : null;
        $durationMinutes = isset($route['duration']) ? (int) round((float) $route['duration'] / 60) : null;

        return new StaticRouteMapResult(
            configured: true,
            eligible: true,
            geometry: $geometry,
            distanceKm: $distanceKm,
            durationMinutes: $durationMinutes,
            pickupLng: (float) $booking->pickup_lng,
            pickupLat: (float) $booking->pickup_lat,
            dropoffLng: (float) $booking->dropoff_lng,
            dropoffLat: (float) $booking->dropoff_lat,
        );
    }
}
