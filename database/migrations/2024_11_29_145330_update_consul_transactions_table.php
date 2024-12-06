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
            $table->string('sender_name')->nullable()->after('payment_proof'); // Nama pengirim
            $table->string('sender_bank')->nullable()->after('sender_name');  // Nama bank pengirim
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->dropColumn(['sender_name', 'sender_bank']); // Hapus kedua kolom
        });
    }
};
