<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;

class DriverOverviewQuery
{
    private const string TIMEZONE = 'Asia/Manila';

    /**
     * @return array{
     *     available_count: int,
     *     active_booking: ?Booking,
     *     vehicle_type: ?string,
     *     plate_number: ?string
     * }
     */
    public function gather(User $driver): array
    {
        $driver->loadMissing('assignedVehicle.pricing');

        $availableCount = Booking::query()
            ->availableForDriver($driver)
            ->count();

        $activeBooking = Booking::query()
            ->activeForDriver($driver)
            ->with(['pricing'])
            ->orderByDesc('accepted_at')
            ->first();

        return [
            'available_count' => $availableCount,
            'active_booking' => $activeBooking,
            'vehicle_type' => $driver->assignedVehicle?->pricing?->vehicle_type,
            'plate_number' => $driver->assignedVehicle?->plate_number,
        ];
    }

    public function nowInManila(): Carbon
    {
        return now(self::TIMEZONE);
    }
}
