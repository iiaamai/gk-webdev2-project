<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_attendance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('timed_in_at');
            $table->foreignId('timed_in_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('timed_out_at')->nullable();
            $table->foreignId('timed_out_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['driver_id', 'timed_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_attendance_logs');
    }
};
