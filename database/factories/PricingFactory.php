<?php

namespace Database\Factories;

use App\Models\Pricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pricing>
 */
class PricingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_type' => fake()->randomElement([
                '6-wheeler',
                '10-wheeler',
            ]).'-'.fake()->unique()->numerify('####'),
            'amount' => fake()->randomElement([15000, 25000]),
            'capacity_kg' => fake()->randomElement([42000, 72000]),
        ];
    }
}
