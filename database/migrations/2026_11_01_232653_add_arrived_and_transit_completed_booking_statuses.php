<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->where('status', 'in_transit')
            ->whereNotNull('arrived_at_pickup_at')
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('eirs')
                    ->whereColumn('eirs.booking_id', 'bookings.id');
            })
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('pods')
                    ->whereColumn('pods.booking_id', 'bookings.id');
            })
            ->update(['status' => 'transit_completed']);

        DB::table('bookings')
            ->where('status', 'in_transit')
            ->whereNotNull('arrived_at_pickup_at')
            ->update(['status' => 'arrived_at_pickup']);
    }

    public function down(): void
    {
        DB::table('bookings')
            ->whereIn('status', ['arrived_at_pickup', 'transit_completed'])
            ->update(['status' => 'in_transit']);
    }
};
