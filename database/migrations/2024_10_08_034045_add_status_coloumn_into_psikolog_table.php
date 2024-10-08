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
        Schema::table('psikolog', function (Blueprint $table) {
            // Kolom status untuk persetujuan admin
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('practice_start_date');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psikolog', function (Blueprint $table) {
            $table->dropColumn('status');
        });

    }
};
