<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'company_name' => 'GK Trucking Services',
            'support_email' => 'support@gk.test',
            'booking_seq' => '7',
            'map_center_lat' => '14.5995',
            'map_center_lng' => '120.9842',
            'map_zoom' => '11',
            'dropoff_change_fee' => '0',
            'wrong_truck_fee_percent' => '10',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->withArchived()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value],
            );
        }
    }
}
