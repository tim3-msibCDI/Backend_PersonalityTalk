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
            $table->renameColumn('total_amount', 'consul_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->renameColumn('consul_fee', 'total_amount');
        });
    }
};
