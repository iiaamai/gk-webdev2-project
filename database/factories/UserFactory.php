<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\DriverAttendanceLog;
use App\Models\User;
use App\Support\DriverAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'mobile' => fake()->numerify('09#########'),
            'role' => UserRole::Customer,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Customer,
        ]);
    }

    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Driver,
        ]);
    }

    public function timedIn(?User $recordedBy = null): static
    {
        return $this->afterCreating(function (User $user) use ($recordedBy): void {
            if (! $user->isDriver()) {
                return;
            }

            if (DriverAttendance::isTimedIn($user)) {
                return;
            }

            $recorder = $recordedBy ?? User::query()->where('role', UserRole::Staff)->first()
                ?? User::factory()->staff()->create();

            DriverAttendanceLog::query()->create([
                'driver_id' => $user->id,
                'timed_in_at' => now('Asia/Manila'),
                'timed_in_by_id' => $recorder->id,
            ]);
        });
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Staff,
        ]);
    }

    public function systemAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SystemAdmin,
        ]);
    }
}
