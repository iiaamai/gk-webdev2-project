<?php

namespace App\Actions;

use App\Enums\MultiTripOfficeStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestMultiTripRoundTrips
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $customer, Booking $booking, int $roundTrips): Booking
    {
        if ($booking->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'booking' => 'This booking does not belong to you.',
            ]);
        }

        if (! BookingMultiTrip::canRequestMultiTrip($booking)) {
            throw ValidationException::withMessages([
                'round_trips' => 'You can submit one round-trip request after the driver arrives at pickup.',
            ]);
        }

        if ($roundTrips < 1 || $roundTrips > 99) {
            throw ValidationException::withMessages([
                'round_trips' => 'Enter between 1 and 99 round trips.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking, $roundTrips): Booking {
            $booking->update([
                'multi_trip_requested_rounds' => $roundTrips,
                'multi_trip_office_status' => MultiTripOfficeStatus::Pending,
                'multi_trip_office_rounds' => null,
                'multi_trip_customer_confirmed_at' => null,
                'multi_trip_driver_accepted_at' => null,
            ]);

            return $booking->fresh();
        });

        $this->activityLogger->log(
            action: 'booking.multi_trip_requested',
            subject: $booking,
            description: "Customer requested {$roundTrips} round trip(s) for {$booking->booking_number}.",
            properties: ['round_trips' => $roundTrips],
            user: $customer,
        );

        return $booking;
    }
}
