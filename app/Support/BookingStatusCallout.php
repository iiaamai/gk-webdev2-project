<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;

class BookingStatusCallout
{
    /**
     * @return array{message: string, tone: string}|null
     */
    public static function forCustomer(Booking $booking): ?array
    {
        if ($booking->status === BookingStatus::Cancelled) {
            return null;
        }

        if ($booking->status === BookingStatus::Pending) {
            if (! $booking->hasGatepass()) {
                return [
                    'message' => 'Your booking is pending. GK staff will upload a gatepass before a driver can accept.',
                    'tone' => 'info',
                ];
            }

            if ($booking->driver_id === null) {
                return [
                    'message' => 'Gatepass is ready. Waiting for a driver to accept your trip.',
                    'tone' => 'info',
                ];
            }
        }

        if (BookingMultiTrip::multiTripBlocksCompletion($booking) && auth()->user()?->can('confirmMultiTrip', $booking)) {
            return [
                'message' => 'Office revised your round-trip count — confirm it in the capacity section below.',
                'tone' => 'warning',
            ];
        }

        if (auth()->user()?->can('requestMultiTrip', $booking)) {
            return [
                'message' => 'You are at pickup. You can request round trips or change vehicle type in the section below.',
                'tone' => 'info',
            ];
        }

        return match ($booking->status) {
            BookingStatus::Accepted => [
                'message' => 'A driver accepted your booking. They will start heading to pickup soon.',
                'tone' => 'info',
            ],
            BookingStatus::TransitToPickup => [
                'message' => 'Your driver is on the way to the pickup location.',
                'tone' => 'info',
            ],
            BookingStatus::ArrivedAtPickup => [
                'message' => 'Your driver arrived at pickup. The haul will start when they leave with cargo.',
                'tone' => 'info',
            ],
            BookingStatus::InTransit => [
                'message' => 'Your delivery is in progress (haul to dropoff).',
                'tone' => 'info',
            ],
            BookingStatus::TransitCompleted => [
                'message' => 'The haul is done. Your driver will upload proof-of-delivery documents next.',
                'tone' => 'info',
            ],
            BookingStatus::Completed => [
                'message' => 'This trip is complete. Pay any unpaid invoice from the invoice section.',
                'tone' => 'success',
            ],
            default => null,
        };
    }
}
