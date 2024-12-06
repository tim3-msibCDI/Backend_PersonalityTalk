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
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID pengguna
            $table->unsignedBigInteger('psi_id'); // ID psikolog
            $table->unsignedBigInteger('consultation_id')->unique(); // ID konsultasi terkait
            $table->timestamp('start_time'); // Waktu mulai sesi chat
            $table->timestamp('end_time'); // Waktu selesai sesi chat
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('psi_id')->references('id')->on('psikolog')->onDelete('cascade');
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['psi_id']);
            $table->dropForeign(['consultation_id']);
        });

        Schema::dropIfExists('chat_sessions');
    }
};
