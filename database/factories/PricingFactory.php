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
            'vehicle_type' => fake()->unique()->randomElement([
                '6-wheeler truck',
                '4-wheeler truck',
                'L300 van',
                'Reefer / specialized',
            ]),
            'amount' => fake()->randomElement([14500, 9200, 4500, 18500]),
            'capacity_kg' => fake()->randomElement([1000, 3000, 5000, 12000]),
        ];
    }
}
