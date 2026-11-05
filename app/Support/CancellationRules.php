<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class CancellationRules
{
    public const FEE_RATE = 0.10;

    /**
     * @return list<BookingStatus>
     */
    public static function customerCancellableStatuses(): array
    {
        return [
            BookingStatus::Pending,
            BookingStatus::Accepted,
            BookingStatus::TransitToPickup,
            BookingStatus::ArrivedAtPickup,
            BookingStatus::InTransit,
        ];
    }

    public static function customerMayCancel(User $user, Booking $booking): bool
    {
        if (! $user->isCustomer() || $booking->customer_id !== $user->id) {
            return false;
        }

        if ($booking->status === BookingStatus::Cancelled
            || $booking->status === BookingStatus::Completed
            || $booking->status === BookingStatus::TransitCompleted) {
            return false;
        }

        return in_array($booking->status, self::customerCancellableStatuses(), true);
    }

    public static function isFreeCustomerCancellation(Booking $booking): bool
    {
        if ($booking->status !== BookingStatus::Pending || $booking->hasGatepass()) {
            return false;
        }

        $createdDay = $booking->created_at?->timezone('Asia/Manila')->toDateString();
        $today = now('Asia/Manila')->toDateString();

        return $createdDay !== null && $createdDay === $today;
    }

    public static function shouldApplyCancellationFee(Booking $booking, ?User $cancelledBy): bool
    {
        if ($cancelledBy === null) {
            return false;
        }

        if ($cancelledBy->isCustomer() && $booking->customer_id === $cancelledBy->id) {
            return ! self::isFreeCustomerCancellation($booking);
        }

        if ($cancelledBy->isSystemAdmin()) {
            return $booking->accepted_at !== null;
        }

        return false;
    }

    public static function feeAmount(Booking $booking): float
    {
        $booking->loadMissing('invoice', 'pricing');
        $base = (float) ($booking->invoice?->amount ?? $booking->pricing?->amount ?? 0);

        return round($base * self::FEE_RATE, 2);
    }

    public static function feeNote(): string
    {
        return 'Cancellation fee (10%).';
    }
}
