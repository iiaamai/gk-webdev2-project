<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    public function view(User $user, Rating $rating): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        return $user->isCustomer() && $rating->customer_id === $user->id;
    }

    public function create(User $user, Booking $booking): bool
    {
        if (! $user->isCustomer() || $booking->customer_id !== $user->id) {
            return false;
        }

        if ($booking->status !== BookingStatus::Completed) {
            return false;
        }

        return ! $booking->rating()->exists();
    }
}
