<?php

namespace Database\Seeders;

use App\Actions\SyncVehicleDriverAssignment;
use App\Enums\VehicleStatus;
use App\Models\Pricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingByType = Pricing::query()->pluck('id', 'vehicle_type');

        $vehicles = [
            [
                'plate_number' => 'ABC-1234',
                'brand' => 'Isuzu',
                'color' => 'White',
                'pricing_id' => $pricingByType['4-wheeler truck'],
                'status' => VehicleStatus::Available,
            ],
            [
                'plate_number' => 'DEF-5678',
                'brand' => 'Fuso',
                'color' => 'White',
                'pricing_id' => $pricingByType['6-wheeler truck'],
                'status' => VehicleStatus::Available,
            ],
            [
                'plate_number' => 'GHI-9012',
                'brand' => 'Toyota',
                'color' => 'Silver',
                'pricing_id' => $pricingByType['L300 van'],
                'status' => VehicleStatus::Available,
            ],
            [
                'plate_number' => 'JKL-3456',
                'brand' => 'Hino',
                'color' => 'White',
                'pricing_id' => $pricingByType['Reefer / specialized'],
                'status' => VehicleStatus::Available,
            ],
            [
                'plate_number' => 'MNO-7890',
                'brand' => 'Mitsubishi',
                'color' => 'Blue',
                'pricing_id' => $pricingByType['4-wheeler truck'],
                'status' => VehicleStatus::Maintenance,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::query()->updateOrCreate(
                ['plate_number' => $vehicle['plate_number']],
                $vehicle,
            );
        }

        $assignments = [
            'ABC-1234' => 'driver@gk.test',
            'DEF-5678' => 'driver2@gk.test',
        ];

        $sync = app(SyncVehicleDriverAssignment::class);

        foreach ($assignments as $plateNumber => $driverEmail) {
            $vehicle = Vehicle::query()->where('plate_number', $plateNumber)->first();
            $driver = User::query()->where('email', $driverEmail)->first();

            if ($vehicle === null || $driver === null) {
                continue;
            }

            $sync->forVehicle($vehicle, $driver->id);
        }
    }
}
