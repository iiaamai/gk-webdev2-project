<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\MultiTripOfficeStatus;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\Setting;

class BookingMultiTrip
{
    public static function baseInvoiceAmount(Pricing $pricing): float
    {
        return round((float) $pricing->amount, 2);
    }

    public static function invoiceAmountForRoundTrips(Pricing $pricing, int $roundTrips): float
    {
        $roundTrips = max(1, $roundTrips);

        return round($roundTrips * (float) $pricing->amount, 2);
    }

    public static function wrongTruckFeeAmount(Pricing $pricing): float
    {
        $percent = (float) Setting::getValue('wrong_truck_fee_percent', '10');
        if ($percent <= 0) {
            return 0.0;
        }

        return round(self::baseInvoiceAmount($pricing) * ($percent / 100), 2);
    }

    public static function hasArrivedAtPickup(Booking $booking): bool
    {
        return $booking->arrived_at_pickup_at !== null;
    }

    public static function hasSubmittedMultiTripRequest(Booking $booking): bool
    {
        return $booking->multi_trip_requested_rounds !== null;
    }

    public static function isMultiTripPendingOffice(Booking $booking): bool
    {
        return $booking->multi_trip_office_status === MultiTripOfficeStatus::Pending;
    }

    public static function isMultiTripApproved(Booking $booking): bool
    {
        return $booking->multi_trip_office_status === MultiTripOfficeStatus::Approved;
    }

    public static function needsCustomerConfirm(Booking $booking): bool
    {
        if (! self::isMultiTripApproved($booking)) {
            return false;
        }

        if ($booking->multi_trip_customer_confirmed_at !== null) {
            return false;
        }

        return (int) $booking->multi_trip_office_rounds !== (int) $booking->multi_trip_requested_rounds;
    }

    public static function needsDriverAccept(Booking $booking): bool
    {
        if (! self::isMultiTripApproved($booking)) {
            return false;
        }

        if (self::needsCustomerConfirm($booking)) {
            return false;
        }

        return $booking->multi_trip_driver_accepted_at === null;
    }

    public static function multiTripBlocksCompletion(Booking $booking): bool
    {
        if (! self::hasSubmittedMultiTripRequest($booking)) {
            return false;
        }

        if (self::isMultiTripPendingOffice($booking)) {
            return true;
        }

        if (self::needsCustomerConfirm($booking)) {
            return true;
        }

        if (self::needsDriverAccept($booking)) {
            return true;
        }

        return false;
    }

    public static function canMarkArrivedAtPickup(Booking $booking): bool
    {
        return $booking->status === BookingStatus::TransitToPickup
            && ! self::hasArrivedAtPickup($booking);
    }

    public static function canStartHaulInTransit(Booking $booking): bool
    {
        return $booking->status === BookingStatus::ArrivedAtPickup
            && self::hasArrivedAtPickup($booking);
    }

    public static function canRequestMultiTrip(Booking $booking): bool
    {
        return self::hasArrivedAtPickup($booking)
            && ! self::hasSubmittedMultiTripRequest($booking)
            && $booking->status === BookingStatus::ArrivedAtPickup;
    }

    public static function canChangeVehicleType(Booking $booking): bool
    {
        return self::hasArrivedAtPickup($booking)
            && $booking->vehicle_type_changed_at === null
            && $booking->status === BookingStatus::ArrivedAtPickup;
    }

    public static function canMarkTransitCompleted(Booking $booking): bool
    {
        return $booking->status === BookingStatus::InTransit
            && ! self::multiTripBlocksCompletion($booking)
            && self::allRoundTripLapsCompleted($booking);
    }

    public static function requiresLapTracking(Booking $booking): bool
    {
        return self::effectiveRoundTrips($booking) > 1;
    }

    public static function showLapProgress(Booking $booking): bool
    {
        return $booking->status === BookingStatus::InTransit
            && self::requiresLapTracking($booking)
            && ! self::multiTripBlocksCompletion($booking);
    }

    public static function lapsCompleted(Booking $booking): int
    {
        return max(0, (int) $booking->multi_trip_laps_completed);
    }

    public static function effectiveRoundTripsTotal(Booking $booking): int
    {
        return max(1, self::effectiveRoundTrips($booking));
    }

    public static function allRoundTripLapsCompleted(Booking $booking): bool
    {
        if (! self::requiresLapTracking($booking)) {
            return true;
        }

        return self::lapsCompleted($booking) >= self::effectiveRoundTripsTotal($booking);
    }

    public static function canMarkRoundTripLap(Booking $booking): bool
    {
        if (! self::showLapProgress($booking)) {
            return false;
        }

        return self::lapsCompleted($booking) < self::effectiveRoundTripsTotal($booking);
    }

    public static function nextLapNumber(Booking $booking): int
    {
        return min(
            self::effectiveRoundTripsTotal($booking),
            self::lapsCompleted($booking) + 1
        );
    }

    public static function invoiceAmountAfterMultiTripApprove(Booking $booking, Pricing $pricing, int $officeRoundTrips): float
    {
        $tripsAmount = self::invoiceAmountForRoundTrips($pricing, $officeRoundTrips);
        $fee = 0.0;

        if ($booking->vehicle_type_changed_at !== null && $booking->invoice !== null) {
            $singleTrip = self::invoiceAmountForRoundTrips($pricing, 1);
            $fee = max(0, round((float) $booking->invoice->amount - $singleTrip, 2));
        }

        return round($tripsAmount + $fee, 2);
    }

    public static function effectiveRoundTrips(Booking $booking): int
    {
        if (self::isMultiTripApproved($booking) && $booking->multi_trip_office_rounds !== null) {
            return max(1, (int) $booking->multi_trip_office_rounds);
        }

        return 1;
    }
}
