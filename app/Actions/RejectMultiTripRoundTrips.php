<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BookingMultiTrip;
use Illuminate\Validation\ValidationException;

class RejectMultiTripRoundTrips
{
    public function __construct(
        private readonly CancelBooking $cancelBooking,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $officeUser, Booking $booking): Booking
    {
        if (! $officeUser->isStaff() && ! $officeUser->isSystemAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Only office staff can reject multi-trip requests.',
            ]);
        }

        if (! BookingMultiTrip::isMultiTripPendingOffice($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'There is no pending multi-trip request for this booking.',
            ]);
        }

        $this->activityLogger->log(
            action: 'booking.multi_trip_rejected',
            subject: $booking,
            description: "Office rejected multi-trip request for {$booking->booking_number}; booking cancelled.",
            user: $officeUser,
        );

        return $this->cancelBooking->execute($booking, $officeUser, applyLateCancelFee: false);
    }
}
