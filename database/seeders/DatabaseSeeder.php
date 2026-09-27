<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PricingSeeder::class,
            UserSeeder::class,
            VehicleSeeder::class,
            SettingSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
