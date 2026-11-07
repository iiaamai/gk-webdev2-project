<?php

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Vehicle;

class BookingVehicleRelease
{
    public function releaseForBooking(Booking $booking): void
    {
        if ($booking->vehicle_id !== null) {
            Vehicle::query()
                ->whereKey($booking->vehicle_id)
                ->update(['status' => VehicleStatus::Available]);
        }

        $booking->forceFill([
            'vehicle_id' => null,
            'accepted_at' => null,
            'is_locked' => false,
        ])->save();
    }

    public function releaseVehicleAfterCompletion(Booking $booking): void
    {
        if ($booking->vehicle_id !== null) {
            Vehicle::query()
                ->whereKey($booking->vehicle_id)
                ->update(['status' => VehicleStatus::Available]);
        }

        $booking->forceFill([
            'vehicle_id' => null,
            'is_locked' => false,
        ])->save();
    }
}
