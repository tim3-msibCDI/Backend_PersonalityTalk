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
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->enum('commission_transfer_status', ['Menunggu Konfirmasi', 'Diterima'])->default('Menunggu Konfirmasi')->nullable()->after('failure_reason');
            $table->string('commission_transfer_proof')->nullable()->after('commission_transfer_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->dropColumn(['commission_transfer_status', 'commission_transfer_proof']);
        });
    }
};
