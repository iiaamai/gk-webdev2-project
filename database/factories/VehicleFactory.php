<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Pricing;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pricing = Pricing::query()->where('vehicle_type', '4-wheeler truck')->first()
            ?? Pricing::factory()->create([
                'vehicle_type' => '4-wheeler truck',
                'amount' => 9200.00,
                'capacity_kg' => 3000,
            ]);

        return [
            'plate_number' => strtoupper(fake()->unique()->bothify('???-####')),
            'brand' => fake()->randomElement(['Isuzu', 'Fuso', 'Toyota', 'Hino', 'Mitsubishi']),
            'color' => fake()->randomElement(['White', 'Silver', 'Blue', 'Red', 'Black']),
            'pricing_id' => $pricing->id,
            'status' => VehicleStatus::Available,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Available,
        ]);
    }

    public function inUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::InUse,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Maintenance,
        ]);
    }
}
