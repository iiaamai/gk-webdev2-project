<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\User;

class DriverLocationQuery
{
    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     lat: float,
     *     lng: float,
     *     updated_at: string|null,
     *     vehicle: string|null,
     *     booking_number: string|null
     * }>
     */
    public function latest(): array
    {
        return User::query()
            ->where('role', UserRole::Driver)
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->with([
                'assignedVehicle',
                'driverBookings' => function ($query): void {
                    $query->whereIn('status', [BookingStatus::Accepted, BookingStatus::InTransit])
                        ->latest('accepted_at');
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $driver): array {
                $active = $driver->driverBookings->first();

                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'lat' => (float) $driver->location_lat,
                    'lng' => (float) $driver->location_lng,
                    'updated_at' => $driver->location_updated_at?->toIso8601String(),
                    'vehicle' => $driver->assignedVehicle?->plate_number,
                    'booking_number' => $active?->booking_number,
                ];
            })
            ->values()
            ->all();
    }
}
