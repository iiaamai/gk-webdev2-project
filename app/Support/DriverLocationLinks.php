<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;

class DriverLocationLinks
{
    public static function forBooking(Booking $booking, ?User $user): ?string
    {
        if ($user === null || $booking->driver_id === null || ! $user->can('viewDriverLocation', $booking)) {
            return null;
        }

        if ($user->isSystemAdmin()) {
            return route('admin.bookings.driver-location', $booking);
        }

        if ($user->isStaff()) {
            return route('staff.bookings.driver-location', $booking);
        }

        if ($user->isCustomer()) {
            return route('customer.bookings.driver-location', $booking);
        }

        return null;
    }
}
