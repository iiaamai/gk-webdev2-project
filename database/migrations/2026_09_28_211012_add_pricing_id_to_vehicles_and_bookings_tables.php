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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('pricing_id')
                ->nullable()
                ->after('type')
                ->constrained('pricings');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('pricing_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('pricings');
        });

        $pricings = DB::table('pricings')->pluck('id', 'vehicle_type');

        foreach (DB::table('vehicles')->select('id', 'type')->get() as $vehicle) {
            $pricingId = $pricings[$vehicle->type] ?? null;
            if ($pricingId !== null) {
                DB::table('vehicles')->where('id', $vehicle->id)->update(['pricing_id' => $pricingId]);
            }
        }

        foreach (DB::table('bookings')->select('id', 'vehicle_type')->get() as $booking) {
            $pricingId = $pricings[$booking->vehicle_type] ?? null;
            if ($pricingId !== null) {
                DB::table('bookings')->where('id', $booking->id)->update(['pricing_id' => $pricingId]);
            }
        }

        $fallbackPricingId = DB::table('pricings')->orderBy('id')->value('id');

        if ($fallbackPricingId !== null) {
            DB::table('vehicles')->whereNull('pricing_id')->update(['pricing_id' => $fallbackPricingId]);
            DB::table('bookings')->whereNull('pricing_id')->update(['pricing_id' => $fallbackPricingId]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['vehicle_type']);
            $table->dropColumn('vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('vehicle_id');
            $table->index('vehicle_type');
        });

        $pricings = DB::table('pricings')->pluck('vehicle_type', 'id');

        foreach (DB::table('bookings')->select('id', 'pricing_id')->get() as $booking) {
            DB::table('bookings')->where('id', $booking->id)->update([
                'vehicle_type' => $pricings[$booking->pricing_id] ?? 'unknown',
            ]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_id');
        });
    }
};
