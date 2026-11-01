<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmMultiTripRoundTrips
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $customer, Booking $booking): Booking
    {
        if ($booking->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'booking' => 'This booking does not belong to you.',
            ]);
        }

        if (! BookingMultiTrip::needsCustomerConfirm($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'No revised round-trip count is waiting for your confirmation.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking): Booking {
            $booking->update(['multi_trip_customer_confirmed_at' => now('Asia/Manila')]);

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.multi_trip_customer_confirmed',
            subject: $booking,
            description: "Customer confirmed {$booking->multi_trip_office_rounds} round trip(s) for {$booking->booking_number}.",
            user: $customer,
        );

        return $booking;
    }
}
