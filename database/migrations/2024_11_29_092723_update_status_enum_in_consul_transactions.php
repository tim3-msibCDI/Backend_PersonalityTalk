<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
   /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'pending_confirmation',
                'completed',
                'failed',
            ])->default('pending')->change()->after('payment_proof');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'canceled',
            ])->default('pending')->change()->after('payment_proof');
        });
    }


};
