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
            $table->date('practice_start_date')->nullable()->after('sipp'); // Menambahkan kolom practice_start_date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psikolog', function (Blueprint $table) {
            $table->dropColumn('practice_start_date');
        });
    }
};
