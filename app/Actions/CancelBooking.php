<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use App\Services\BookingVehicleRelease;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    public function __construct(
        private readonly BookingVehicleRelease $bookingVehicleRelease,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(Booking $booking): Booking
    {
        $booking->loadMissing('driver');
        $assignedDriver = $booking->driver;
        $previousStatus = $booking->status;

        $booking = DB::transaction(function () use ($booking): Booking {
            $this->bookingVehicleRelease->releaseForBooking($booking);

            $booking->update(['status' => BookingStatus::Cancelled]);

            return $booking->fresh();
        });

        $this->bookingEmailNotifier->statusChanged($booking, BookingStatus::Cancelled, $assignedDriver);

        $this->activityLogger->log(
            action: 'booking.status_changed',
            subject: $booking,
            description: "Booking {$booking->booking_number} cancelled.",
            properties: [
                'status' => BookingStatus::Cancelled->value,
                'previous_status' => $previousStatus->value,
            ],
        );

        return $booking;
    }
}
