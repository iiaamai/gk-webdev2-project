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
            ['vehicle_type' => '6-wheeler', 'amount' => 15000.00, 'capacity_kg' => 42000],
            ['vehicle_type' => '10-wheeler', 'amount' => 25000.00, 'capacity_kg' => 72000],
        ];

        foreach ($rows as $row) {
            Pricing::query()->withArchived()->updateOrCreate(
                ['vehicle_type' => $row['vehicle_type']],
                $row,
            );
        }
    }
}
