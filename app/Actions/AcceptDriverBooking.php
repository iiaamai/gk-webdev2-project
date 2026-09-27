<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\BookingEmailNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptDriverBooking
{
    public function __construct(
        private readonly BookingEmailNotifier $bookingEmailNotifier,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function execute(User $driver, Booking $booking): Booking
    {
        if (! $driver->isDriver()) {
            throw ValidationException::withMessages([
                'driver' => 'Only drivers can accept deliveries.',
            ]);
        }

        $driver->loadMissing('assignedVehicle');

        if ($driver->assignedVehicle === null || $driver->assignedVehicle->pricing_id === null) {
            throw ValidationException::withMessages([
                'vehicle' => 'You must be assigned a fleet vehicle before accepting jobs.',
            ]);
        }

        $booking = DB::transaction(function () use ($driver, $booking): Booking {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $this->isAvailableForDriver($driver, $booking)) {
                throw ValidationException::withMessages([
                    'booking' => 'This job is no longer available to accept.',
                ]);
            }

            $hasActiveDelivery = Booking::query()
                ->where('driver_id', $driver->id)
                ->whereIn('status', [BookingStatus::Accepted, BookingStatus::InTransit])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveDelivery) {
                throw ValidationException::withMessages([
                    'driver' => 'You already have an active delivery. Finish or complete it before accepting another job.',
                ]);
            }

            $vehicle = $this->resolveVehicle($driver, $booking);

            if ($vehicle === null) {
                throw ValidationException::withMessages([
                    'vehicle' => 'No available fleet unit for this vehicle type right now.',
                ]);
            }

            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->id)
                ->lockForUpdate()
                ->first();

            if ($lockedVehicle === null || $lockedVehicle->status !== VehicleStatus::Available) {
                throw ValidationException::withMessages([
                    'vehicle' => 'The selected vehicle is no longer available.',
                ]);
            }

            $lockedVehicle->update(['status' => VehicleStatus::InUse]);

            $booking->update([
                'driver_id' => $driver->id,
                'vehicle_id' => $lockedVehicle->id,
                'status' => BookingStatus::Accepted,
                'is_locked' => true,
                'accepted_at' => now('Asia/Manila'),
            ]);

            return $booking->fresh();
        });

        $this->bookingEmailNotifier->statusChanged($booking, BookingStatus::Accepted);

        $this->activityLogger->log(
            action: 'booking.status_changed',
            subject: $booking,
            description: "Booking {$booking->booking_number} accepted by driver.",
            properties: [
                'status' => BookingStatus::Accepted->value,
                'driver_id' => $driver->id,
                'vehicle_id' => $booking->vehicle_id,
            ],
            user: $driver,
        );

        return $booking;
    }

    public function isAvailableForDriver(User $driver, Booking $booking): bool
    {
        $driver->loadMissing('assignedVehicle');

        return $booking->status === BookingStatus::Pending
            && $booking->hasGatepass()
            && ! $booking->is_locked
            && $booking->driver_id === null
            && $driver->assignedVehicle !== null
            && (int) $booking->pricing_id === (int) $driver->assignedVehicle->pricing_id;
    }

    private function resolveVehicle(User $driver, Booking $booking): ?Vehicle
    {
        $driver->loadMissing('assignedVehicle');

        $assigned = $driver->assignedVehicle;

        if (
            $assigned !== null
            && (int) $assigned->pricing_id === (int) $booking->pricing_id
            && $assigned->status === VehicleStatus::Available
        ) {
            return $assigned;
        }

        return Vehicle::query()
            ->where('pricing_id', $booking->pricing_id)
            ->where('status', VehicleStatus::Available)
            ->orderBy('plate_number')
            ->first();
    }
}
