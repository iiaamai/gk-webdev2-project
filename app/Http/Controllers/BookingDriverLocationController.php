<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class BookingDriverLocationController extends Controller
{
    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('viewDriverLocation', $booking);

        $driver = $booking->driver;

        return response()->json([
            'available' => $driver?->location_lat !== null && $driver?->location_lng !== null,
            'name' => $driver?->name,
            'lat' => $driver?->location_lat !== null ? (float) $driver->location_lat : null,
            'lng' => $driver?->location_lng !== null ? (float) $driver->location_lng : null,
            'updated_at' => $driver?->location_updated_at?->toIso8601String(),
        ]);
    }
}
