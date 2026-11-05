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
        $sixWheelerId = $pricingByType['6-wheeler'];
        $tenWheelerId = $pricingByType['10-wheeler'];

        $lines = [
            ['prefix' => 'ISU-4X2', 'brand' => 'Isuzu Giga / S&E (4×2)', 'pricing_id' => $sixWheelerId, 'count' => 5],
            ['prefix' => 'ISU-6X4', 'brand' => 'Isuzu Giga / S&E (6×4)', 'pricing_id' => $tenWheelerId, 'count' => 5],
            ['prefix' => 'HNO-4X2', 'brand' => 'Hino 700 (4×2)', 'pricing_id' => $sixWheelerId, 'count' => 5],
            ['prefix' => 'HNO-6X4', 'brand' => 'Hino 700 (6×4)', 'pricing_id' => $tenWheelerId, 'count' => 5],
            ['prefix' => 'HOW-6X4', 'brand' => 'Sinotruk HOWO 10W (6×4)', 'pricing_id' => $tenWheelerId, 'count' => 5],
            ['prefix' => 'FUS-6X4', 'brand' => 'Fuso Super Great (6×4)', 'pricing_id' => $tenWheelerId, 'count' => 5],
        ];

        foreach ($lines as $line) {
            for ($unit = 1; $unit <= $line['count']; $unit++) {
                $plate = sprintf('%s-%03d', $line['prefix'], $unit);

                Vehicle::query()->withArchived()->updateOrCreate(
                    ['plate_number' => $plate],
                    [
                        'brand' => $line['brand'],
                        'color' => 'White',
                        'pricing_id' => $line['pricing_id'],
                        'status' => VehicleStatus::Available,
                    ],
                );
            }
        }

        $maintenance = Vehicle::query()->withArchived()->updateOrCreate(
            ['plate_number' => 'MNT-0001'],
            [
                'brand' => 'Isuzu Giga / S&E (4×2)',
                'color' => 'Gray',
                'pricing_id' => $sixWheelerId,
                'status' => VehicleStatus::Maintenance,
            ],
        );

        unset($maintenance);

        $assignments = [
            'ISU-4X2-001' => 'driver@gk.test',
            'ISU-6X4-001' => 'driver2@gk.test',
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
