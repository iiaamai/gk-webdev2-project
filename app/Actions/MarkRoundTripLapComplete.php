<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkRoundTripLapComplete
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $driver, Booking $booking): Booking
    {
        if ($booking->driver_id !== $driver->id) {
            throw ValidationException::withMessages([
                'booking' => 'You are not assigned to this delivery.',
            ]);
        }

        if (! BookingMultiTrip::canMarkRoundTripLap($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'Round-trip laps can only be marked during the haul when laps remain.',
            ]);
        }

        $total = BookingMultiTrip::effectiveRoundTrips($booking);

        $booking = DB::transaction(function () use ($booking, $total): Booking {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! BookingMultiTrip::canMarkRoundTripLap($booking)) {
                throw ValidationException::withMessages([
                    'booking' => 'Round-trip laps can only be marked during the haul when laps remain.',
                ]);
            }

            $next = min($total, (int) $booking->multi_trip_laps_completed + 1);
            $booking->update(['multi_trip_laps_completed' => $next]);

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.round_trip_lap_completed',
            subject: $booking,
            description: "Driver marked round trip {$booking->multi_trip_laps_completed} of {$total} for {$booking->booking_number}.",
            user: $driver,
            properties: [
                'laps_completed' => $booking->multi_trip_laps_completed,
                'round_trips_total' => $total,
            ],
        );

        return $booking;
    }
}
