<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingEmailNotifier;
use App\Services\BookingVehicleRelease;
use Illuminate\Support\Facades\DB;

class UpdateBookingStatus
{
    public function __construct(
        private readonly BookingVehicleRelease $bookingVehicleRelease,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
    ) {}

    public function execute(Booking $booking, BookingStatus $status): Booking
    {
        $booking->loadMissing('driver');
        $assignedDriver = $booking->driver;
        $previousStatus = $booking->status;

        $booking = DB::transaction(function () use ($booking, $status): Booking {
            if ($status === BookingStatus::Cancelled) {
                $this->bookingVehicleRelease->releaseForBooking($booking);
            }

            $booking->update(['status' => $status]);

            return $booking->fresh();
        });

        if ($previousStatus !== $status) {
            $this->bookingEmailNotifier->statusChanged(
                $booking,
                $status,
                $status === BookingStatus::Cancelled ? $assignedDriver : null,
            );
        }

        return $booking;
    }
}
