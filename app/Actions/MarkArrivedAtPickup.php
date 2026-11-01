<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkArrivedAtPickup
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

        if (! BookingMultiTrip::canMarkArrivedAtPickup($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'Arrived at pickup is only available while the delivery is in transit.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking): Booking {
            $booking->update(['arrived_at_pickup_at' => now('Asia/Manila')]);

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.arrived_at_pickup',
            subject: $booking,
            description: "Driver marked arrived at pickup for {$booking->booking_number}.",
            user: $driver,
        );

        return $booking;
    }
}
