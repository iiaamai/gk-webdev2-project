<?php

namespace App\Policies;

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isSystemAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->isStaff() || $user->isSystemAdmin();
    }

    public function updateStatus(User $user, Vehicle $vehicle): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        if (! $user->isStaff()) {
            return false;
        }

        return $vehicle->status !== VehicleStatus::InUse;
    }

    public function updateDriver(User $user, Vehicle $vehicle): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        if (! $user->isStaff()) {
            return false;
        }

        return in_array($vehicle->status, [
            VehicleStatus::Available,
            VehicleStatus::Maintenance,
        ], true);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->isSystemAdmin();
    }
}
