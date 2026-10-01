<?php

namespace App\Policies;

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

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->isSystemAdmin();
    }
}
