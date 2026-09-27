<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type', 'plate', 'capacity_kg']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
            $table->dropColumn('type');
            $table->index('status');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('role');
            $table->string('plate')->nullable()->after('vehicle_type');
            $table->unsignedInteger('capacity_kg')->nullable()->after('plate');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->string('type')->nullable()->after('label');
            $table->index(['type', 'status']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('payout', 12, 2)->nullable()->after('accepted_at');
        });
    }
};
