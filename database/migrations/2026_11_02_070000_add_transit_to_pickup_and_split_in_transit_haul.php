<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->where('status', 'in_transit')
            ->whereNull('arrived_at_pickup_at')
            ->update(['status' => 'transit_to_pickup']);
    }

    public function down(): void
    {
        DB::table('bookings')
            ->where('status', 'transit_to_pickup')
            ->update(['status' => 'in_transit']);
    }
};
