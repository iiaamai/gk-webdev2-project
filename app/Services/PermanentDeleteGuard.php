<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Eir;
use App\Models\Invoice;
use App\Models\Pod;
use App\Models\Pricing;
use App\Models\Rating;
use App\Models\User;
use App\Models\Vehicle;

class PermanentDeleteGuard
{
    public function booking(Booking $booking): ?string
    {
        $related = [];

        if (Invoice::withArchived()->where('booking_id', $booking->id)->exists()) {
            $related[] = 'an invoice';
        }

        if (Eir::withArchived()->where('booking_id', $booking->id)->exists()) {
            $related[] = 'an EIR';
        }

        if (Pod::withArchived()->where('booking_id', $booking->id)->exists()) {
            $related[] = 'a POD';
        }

        if (Rating::withArchived()->where('booking_id', $booking->id)->exists()) {
            $related[] = 'a rating';
        }

        return $this->message('booking', $related);
    }

    public function user(User $user): ?string
    {
        $related = [];

        if (Booking::withArchived()->where('customer_id', $user->id)->exists()) {
            $related[] = 'customer bookings';
        }

        if (Booking::withArchived()->where('driver_id', $user->id)->exists()) {
            $related[] = 'driver bookings';
        }

        if (Vehicle::withArchived()->where('driver_id', $user->id)->exists()) {
            $related[] = 'a fleet assignment';
        }

        if (ActivityLog::query()->where('user_id', $user->id)->exists()) {
            $related[] = 'activity logs';
        }

        return $this->message('user', $related);
    }

    public function vehicle(Vehicle $vehicle): ?string
    {
        $related = [];

        if (Booking::withArchived()->where('vehicle_id', $vehicle->id)->exists()) {
            $related[] = 'bookings';
        }

        return $this->message('vehicle', $related);
    }

    public function pricing(Pricing $pricing): ?string
    {
        $related = [];

        if (Booking::withArchived()->where('pricing_id', $pricing->id)->exists()) {
            $related[] = 'bookings';
        }

        if (Vehicle::withArchived()->where('pricing_id', $pricing->id)->exists()) {
            $related[] = 'fleet vehicles';
        }

        return $this->message('pricing row', $related);
    }

    /**
     * @param  list<string>  $related
     */
    private function message(string $label, array $related): ?string
    {
        if ($related === []) {
            return null;
        }

        $last = array_pop($related);
        $list = $related === [] ? $last : implode(', ', $related).' and '.$last;

        return 'This '.$label.' cannot be permanently deleted while it still has '.$list.'.';
    }
}
