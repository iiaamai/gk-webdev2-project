<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use App\Services\BookingVehicleRelease;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDriverDeliveryStatus
{
    public function __construct(
        private readonly BookingVehicleRelease $bookingVehicleRelease,
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $driver, Booking $booking, BookingStatus $status): Booking
    {
        if (! $driver->isDriver()) {
            throw ValidationException::withMessages([
                'driver' => 'Only drivers can update delivery status.',
            ]);
        }

        if ($booking->driver_id !== $driver->id) {
            throw ValidationException::withMessages([
                'booking' => 'You are not assigned to this delivery.',
            ]);
        }

        $booking = DB::transaction(function () use ($booking, $status): Booking {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($status === BookingStatus::InTransit) {
                if ($booking->status !== BookingStatus::Accepted) {
                    throw ValidationException::withMessages([
                        'status' => 'Only accepted deliveries can be marked in transit.',
                    ]);
                }

                $booking->update(['status' => BookingStatus::InTransit]);

                return $booking->fresh();
            }

            if ($status === BookingStatus::Completed) {
                if ($booking->status !== BookingStatus::InTransit) {
                    throw ValidationException::withMessages([
                        'status' => 'Only in-transit deliveries can be completed.',
                    ]);
                }

                if (! $booking->eir()->exists() || ! $booking->pod()->exists()) {
                    throw ValidationException::withMessages([
                        'status' => 'EIR and POD are required before completing this delivery.',
                    ]);
                }

                $this->bookingVehicleRelease->releaseVehicleAfterCompletion($booking);
                $booking->update(['status' => BookingStatus::Completed]);

                return $booking->fresh();
            }

            throw ValidationException::withMessages([
                'status' => 'Invalid status transition for driver delivery.',
            ]);
        });

        $this->bookingEmailNotifier->statusChanged($booking, $status);

        $this->activityLogger->log(
            action: 'booking.status_changed',
            subject: $booking,
            description: "Booking {$booking->booking_number} marked {$status->value}.",
            properties: ['status' => $status->value],
            user: $driver,
        );

        return $booking;
    }
}
