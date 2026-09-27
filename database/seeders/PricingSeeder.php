<?php

namespace Database\Seeders;

use App\Models\Pricing;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['vehicle_type' => '6-wheeler truck', 'amount' => 14500.00, 'capacity_kg' => 12000],
            ['vehicle_type' => '4-wheeler truck', 'amount' => 9200.00, 'capacity_kg' => 3000],
            ['vehicle_type' => 'L300 van', 'amount' => 4500.00, 'capacity_kg' => 1000],
            ['vehicle_type' => 'Reefer / specialized', 'amount' => 18500.00, 'capacity_kg' => 5000],
        ];

        foreach ($rows as $row) {
            Pricing::query()->updateOrCreate(
                ['vehicle_type' => $row['vehicle_type']],
                [
                    'amount' => $row['amount'],
                    'capacity_kg' => $row['capacity_kg'],
                ],
            );
        }
    }
}
