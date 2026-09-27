<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pricings', function (Blueprint $table) {
            $table->unsignedInteger('capacity_kg')->nullable()->after('amount');
        });

        $defaults = [
            '6-wheeler truck' => 12000,
            '4-wheeler truck' => 3000,
            'L300 van' => 1000,
            'Reefer / specialized' => 5000,
        ];

        foreach (DB::table('pricings')->select('id', 'vehicle_type')->get() as $pricing) {
            $fromVehicles = DB::table('vehicles')
                ->where('pricing_id', $pricing->id)
                ->avg('capacity_kg');

            $capacity = $fromVehicles !== null
                ? (int) round((float) $fromVehicles)
                : ($defaults[$pricing->vehicle_type] ?? 3000);

            DB::table('pricings')->where('id', $pricing->id)->update([
                'capacity_kg' => max(1, $capacity),
            ]);
        }

        DB::table('pricings')->whereNull('capacity_kg')->update(['capacity_kg' => 3000]);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('capacity_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('capacity_kg')->nullable()->after('pricing_id');
        });

        foreach (DB::table('vehicles')->select('id', 'pricing_id')->get() as $vehicle) {
            $capacity = DB::table('pricings')->where('id', $vehicle->pricing_id)->value('capacity_kg') ?? 3000;
            DB::table('vehicles')->where('id', $vehicle->id)->update(['capacity_kg' => $capacity]);
        }

        Schema::table('pricings', function (Blueprint $table) {
            $table->dropColumn('capacity_kg');
        });
    }
};
