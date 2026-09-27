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

        if (blank($driver->vehicle_type)) {
            throw ValidationException::withMessages([
                'vehicle_type' => 'Your driver profile must have a vehicle type before accepting jobs.',
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
        return $booking->status === BookingStatus::Pending
            && $booking->hasGatepass()
            && ! $booking->is_locked
            && $booking->driver_id === null
            && $booking->vehicle_type === $driver->vehicle_type;
    }

    private function resolveVehicle(User $driver, Booking $booking): ?Vehicle
    {
        $query = Vehicle::query()
            ->where('type', $booking->vehicle_type)
            ->where('status', VehicleStatus::Available);

        if (filled($driver->plate)) {
            $matched = (clone $query)->where('plate_number', $driver->plate)->first();
            if ($matched !== null) {
                return $matched;
            }
        }

        return $query->orderBy('plate_number')->first();
    }
}
