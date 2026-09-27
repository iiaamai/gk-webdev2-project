<?php

namespace App\Policies;

use App\Actions\AcceptDriverBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer()
            || $user->isStaff()
            || $user->isSystemAdmin()
            || $user->isDriver();
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isCustomer()) {
            return $booking->customer_id === $user->id;
        }

        if ($user->isDriver()) {
            return $booking->driver_id === $user->id
                || app(AcceptDriverBooking::class)->isAvailableForDriver($user, $booking);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isSystemAdmin();
    }

    public function update(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        return $user->isStaff() && ! $booking->hasGatepass();
    }

    public function uploadGatepass(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        return $user->isStaff() && ! $booking->hasGatepass();
    }

    public function viewGatepass(User $user, Booking $booking): bool
    {
        if ($user->isCustomer()) {
            return false;
        }

        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isDriver()) {
            return $booking->driver_id === $user->id
                || app(AcceptDriverBooking::class)->isAvailableForDriver($user, $booking);
        }

        return false;
    }

    public function accept(User $user, Booking $booking): bool
    {
        if (! $user->isDriver()) {
            return false;
        }

        if (! app(AcceptDriverBooking::class)->isAvailableForDriver($user, $booking)) {
            return false;
        }

        return ! Booking::query()
            ->where('driver_id', $user->id)
            ->whereIn('status', [BookingStatus::Accepted, BookingStatus::InTransit])
            ->exists();
    }

    public function updateDeliveryStatus(User $user, Booking $booking): bool
    {
        return $user->isDriver()
            && $booking->driver_id === $user->id
            && in_array($booking->status, [BookingStatus::Accepted, BookingStatus::InTransit], true);
    }

    public function viewEir(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isCustomer()) {
            return $booking->customer_id === $user->id
                && in_array($booking->status, [BookingStatus::InTransit, BookingStatus::Completed], true);
        }

        if ($user->isDriver()) {
            return $booking->driver_id === $user->id;
        }

        return false;
    }

    public function viewPod(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isCustomer()) {
            return $booking->customer_id === $user->id
                && $booking->status === BookingStatus::Completed;
        }

        if ($user->isDriver()) {
            return $booking->driver_id === $user->id;
        }

        return false;
    }

    public function uploadEir(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin()) {
            return $booking->status !== BookingStatus::Cancelled;
        }

        return $user->isDriver()
            && $booking->driver_id === $user->id
            && in_array($booking->status, [BookingStatus::Accepted, BookingStatus::InTransit], true);
    }

    public function uploadPod(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin()) {
            return $booking->status !== BookingStatus::Cancelled;
        }

        return $user->isDriver()
            && $booking->driver_id === $user->id
            && in_array($booking->status, [BookingStatus::Accepted, BookingStatus::InTransit], true);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isSystemAdmin()) {
            return $booking->status !== BookingStatus::Completed;
        }

        return $user->isStaff()
            && ! $booking->hasGatepass()
            && $booking->status === BookingStatus::Pending;
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->isSystemAdmin();
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->isSystemAdmin();
    }
}
