<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DriverTripCancelledNotice
{
    public static function remember(?User $driver, Booking $booking): void
    {
        if ($driver === null || ! $driver->isDriver()) {
            return;
        }

        Cache::put(self::key($driver->id), $booking->booking_number, now()->addDay());
    }

    public static function peek(User $driver): ?string
    {
        $value = Cache::get(self::key($driver->id));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function bookingFor(User $driver, ?string $bookingNumber): ?Booking
    {
        if ($bookingNumber === null) {
            return null;
        }

        return Booking::query()
            ->where('booking_number', $bookingNumber)
            ->where('driver_id', $driver->id)
            ->where('status', BookingStatus::Cancelled)
            ->with(['pricing', 'vehicle.pricing'])
            ->first();
    }

    public static function pull(User $driver): ?string
    {
        $value = Cache::pull(self::key($driver->id));

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function key(int $driverId): string
    {
        return 'driver.'.$driverId.'.trip_cancelled';
    }
}
