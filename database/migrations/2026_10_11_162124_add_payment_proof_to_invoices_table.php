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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable()->after('paymongo_reference');
            $table->string('payment_proof_status')->nullable()->after('payment_proof_path');
            $table->timestamp('payment_proof_submitted_at')->nullable()->after('payment_proof_status');

            $table->index('payment_proof_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_proof_status']);
            $table->dropColumn([
                'payment_proof_path',
                'payment_proof_status',
                'payment_proof_submitted_at',
            ]);
        });
    }
};
