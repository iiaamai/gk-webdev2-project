<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['updateProfile', 'viewAvatar'], true)) {
            return null;
        }

        if (! $user->isSystemAdmin()) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }

    public function updateProfile(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function viewAvatar(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->isSystemAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isCustomer() && $model->isDriver()) {
            return Booking::query()
                ->where('customer_id', $user->id)
                ->where('driver_id', $model->id)
                ->whereIn('status', [
                    BookingStatus::Accepted,
                    BookingStatus::InTransit,
                    BookingStatus::Completed,
                ])
                ->exists();
        }

        return false;
    }
}
