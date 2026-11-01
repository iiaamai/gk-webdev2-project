<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('arrived_at_pickup_at')->nullable()->after('accepted_at');
            $table->unsignedSmallInteger('multi_trip_requested_rounds')->nullable()->after('arrived_at_pickup_at');
            $table->unsignedSmallInteger('multi_trip_office_rounds')->nullable()->after('multi_trip_requested_rounds');
            $table->string('multi_trip_office_status', 32)->nullable()->after('multi_trip_office_rounds');
            $table->timestamp('multi_trip_customer_confirmed_at')->nullable()->after('multi_trip_office_status');
            $table->timestamp('multi_trip_driver_accepted_at')->nullable()->after('multi_trip_customer_confirmed_at');
            $table->timestamp('vehicle_type_changed_at')->nullable()->after('multi_trip_driver_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'arrived_at_pickup_at',
                'multi_trip_requested_rounds',
                'multi_trip_office_rounds',
                'multi_trip_office_status',
                'multi_trip_customer_confirmed_at',
                'multi_trip_driver_accepted_at',
                'vehicle_type_changed_at',
            ]);
        });
    }
};
