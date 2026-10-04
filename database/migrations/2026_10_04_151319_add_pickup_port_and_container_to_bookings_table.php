<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('pickup_port_number')->nullable()->after('pickup_lng');
            $table->string('pickup_container_number')->nullable()->after('pickup_port_number');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['pickup_port_number', 'pickup_container_number']);
        });
    }
};
