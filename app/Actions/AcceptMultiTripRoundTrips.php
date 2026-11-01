<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptMultiTripRoundTrips
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

        if (! BookingMultiTrip::needsDriverAccept($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'There is no approved round-trip count waiting for your acceptance.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking): Booking {
            $booking->update(['multi_trip_driver_accepted_at' => now('Asia/Manila')]);

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.multi_trip_driver_accepted',
            subject: $booking,
            description: "Driver accepted {$booking->multi_trip_office_rounds} round trip(s) for {$booking->booking_number}.",
            user: $driver,
        );

        return $booking;
    }
}
