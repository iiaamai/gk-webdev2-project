<?php

namespace App\Actions;

use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletePendingRegistration
{
    public function execute(PendingRegistration $pending): User
    {
        if ($pending->isExpired()) {
            $pending->delete();

            throw ValidationException::withMessages([
                'email' => 'This confirmation link has expired. Please register again.',
            ]);
        }

        if (User::query()->where('email', $pending->email)->exists()) {
            $pending->delete();

            throw ValidationException::withMessages([
                'email' => 'An account already exists for this email. Please log in.',
            ]);
        }

        return DB::transaction(function () use ($pending): User {
            $user = User::query()->create([
                'name' => $pending->name,
                'email' => $pending->email,
                'mobile' => $pending->mobile,
                'password' => $pending->password,
                'role' => $pending->role,
                'email_verified_at' => now(),
            ]);

            $pending->delete();

            return $user;
        });
    }
}
