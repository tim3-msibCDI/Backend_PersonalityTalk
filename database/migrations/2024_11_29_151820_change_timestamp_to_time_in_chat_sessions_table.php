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
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->time('start_time')->change(); // Ubah ke tipe time
            $table->time('end_time')->change();   // Ubah ke tipe time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->timestamp('start_time')->change(); // Kembalikan ke tipe timestamp
            $table->timestamp('end_time')->change();   // Kembalikan ke tipe timestamp
        });
    }
};
