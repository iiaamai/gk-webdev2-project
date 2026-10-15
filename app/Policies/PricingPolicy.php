<?php

namespace App\Policies;

use App\Models\Pricing;
use App\Models\User;

class PricingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isSystemAdmin()) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Pricing $pricing): bool
    {
        return true;
    }

    public function delete(User $user, Pricing $pricing): bool
    {
        return true;
    }

    public function restore(User $user, Pricing $pricing): bool
    {
        return true;
    }

    public function forceDelete(User $user, Pricing $pricing): bool
    {
        return true;
    }
}
