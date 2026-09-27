<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncVehicleDriverAssignment
{
    public function forVehicle(Vehicle $vehicle, ?int $driverId): void
    {
        DB::transaction(function () use ($vehicle, $driverId): void {
            $vehicle = Vehicle::query()->whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            $nextDriverId = $driverId;

            if ($nextDriverId !== null) {
                $driver = User::query()->whereKey($nextDriverId)->lockForUpdate()->first();

                if ($driver === null || $driver->role !== UserRole::Driver) {
                    throw ValidationException::withMessages([
                        'driver_id' => 'The selected driver is invalid.',
                    ]);
                }

                $this->detachDriverFromOtherVehicles($nextDriverId, $vehicle->id);
            }

            $vehicle->update(['driver_id' => $nextDriverId]);
        });
    }

    public function forDriver(User $driver, ?int $vehicleId): void
    {
        DB::transaction(function () use ($driver, $vehicleId): void {
            $driver = User::query()->whereKey($driver->id)->lockForUpdate()->firstOrFail();

            if ($driver->role !== UserRole::Driver) {
                $this->detachDriverFromOtherVehicles($driver->id, null);

                return;
            }

            if ($vehicleId === null) {
                $this->detachDriverFromOtherVehicles($driver->id, null);

                return;
            }

            $vehicle = Vehicle::query()->whereKey($vehicleId)->lockForUpdate()->first();

            if ($vehicle === null) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'The selected fleet vehicle is invalid.',
                ]);
            }

            $this->detachDriverFromOtherVehicles($driver->id, $vehicle->id);

            $vehicle->update(['driver_id' => $driver->id]);
        });
    }

    private function detachDriverFromOtherVehicles(int $driverId, ?int $exceptVehicleId): void
    {
        $query = Vehicle::query()->where('driver_id', $driverId);

        if ($exceptVehicleId !== null) {
            $query->whereKeyNot($exceptVehicleId);
        }

        $query->update(['driver_id' => null]);
    }
}
