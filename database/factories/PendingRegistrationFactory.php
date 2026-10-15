<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\PendingRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<PendingRegistration>
 */
class PendingRegistrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->numerify('09#########'),
            'password' => Hash::make('password'),
            'role' => UserRole::Customer,
            'expires_at' => now()->addDay(),
        ];
    }
}
