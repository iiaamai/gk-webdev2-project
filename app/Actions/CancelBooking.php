<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingEmailNotifier;
use App\Services\BookingVehicleRelease;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    public function __construct(
        private readonly BookingVehicleRelease $bookingVehicleRelease,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
    ) {}

    public function execute(Booking $booking): Booking
    {
        $booking->loadMissing('driver');
        $assignedDriver = $booking->driver;

        $booking = DB::transaction(function () use ($booking): Booking {
            $this->bookingVehicleRelease->releaseForBooking($booking);

            $booking->update(['status' => BookingStatus::Cancelled]);

            return $booking->fresh();
        });

        $this->bookingEmailNotifier->statusChanged($booking, BookingStatus::Cancelled, $assignedDriver);

        return $booking;
    }
}
