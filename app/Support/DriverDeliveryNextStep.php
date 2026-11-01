<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;

class DriverDeliveryNextStep
{
    public static function hint(Booking $booking): ?string
    {
        if (BookingMultiTrip::multiTripBlocksCompletion($booking)
            && in_array($booking->status, [BookingStatus::InTransit, BookingStatus::ArrivedAtPickup], true)) {
            return 'Resolve the round-trip request before marking transit completed.';
        }

        return match ($booking->status) {
            BookingStatus::Accepted => 'Head to the pickup location, then mark transit to pickup.',
            BookingStatus::TransitToPickup => 'Confirm when you arrive at the pickup location.',
            BookingStatus::ArrivedAtPickup => 'Leave pickup with cargo when ready, then start haul in progress.',
            BookingStatus::InTransit => BookingMultiTrip::requiresLapTracking($booking) && ! BookingMultiTrip::allRoundTripLapsCompleted($booking)
                ? 'Mark each completed round trip, then mark transit completed.'
                : 'Finish the haul leg, then mark transit completed.',
            BookingStatus::TransitCompleted => 'Upload EIR and POD below, then mark completed.',
            default => null,
        };
    }
}
