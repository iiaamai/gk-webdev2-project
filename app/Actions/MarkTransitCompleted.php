<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkTransitCompleted
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

        if (! BookingMultiTrip::canMarkTransitCompleted($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'Transit completed is only available during the haul when round-trip requests are resolved.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking): Booking {
            $booking->update(['status' => BookingStatus::TransitCompleted]);

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.transit_completed',
            subject: $booking,
            description: "Driver marked transit completed for {$booking->booking_number}.",
            user: $driver,
        );

        return $booking;
    }
}
