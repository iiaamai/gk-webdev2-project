<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CustomerOverviewQuery
{
    private const string TIMEZONE = 'Asia/Manila';

    /**
     * @return array{
     *     pending_count: int,
     *     active_count: int,
     *     completed_count: int,
     *     active_bookings: Collection<int, Booking>,
     *     unrated_completed: Collection<int, Booking>,
     *     latest_booking: ?Booking
     * }
     */
    public function gather(User $customer): array
    {
        $base = Booking::query()->where('customer_id', $customer->id);

        $pendingCount = (clone $base)->where('status', BookingStatus::Pending)->count();
        $activeCount = (clone $base)
            ->whereIn('status', [BookingStatus::Accepted, BookingStatus::InTransit])
            ->count();
        $completedCount = (clone $base)->where('status', BookingStatus::Completed)->count();

        $activeBookings = Booking::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [
                BookingStatus::Pending,
                BookingStatus::Accepted,
                BookingStatus::InTransit,
            ])
            ->with(['pricing', 'driver', 'invoice'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $unratedCompleted = Booking::query()
            ->where('customer_id', $customer->id)
            ->where('status', BookingStatus::Completed)
            ->whereDoesntHave('rating')
            ->with(['pricing', 'invoice'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $latestBooking = Booking::query()
            ->where('customer_id', $customer->id)
            ->with(['pricing', 'invoice'])
            ->orderByDesc('created_at')
            ->first();

        return [
            'pending_count' => $pendingCount,
            'active_count' => $activeCount,
            'completed_count' => $completedCount,
            'active_bookings' => $activeBookings,
            'unrated_completed' => $unratedCompleted,
            'latest_booking' => $latestBooking,
        ];
    }

    public function nowInManila(): Carbon
    {
        return now(self::TIMEZONE);
    }
}
