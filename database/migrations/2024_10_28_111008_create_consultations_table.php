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
        Schema::create('consultations', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // Pastikan tabel menggunakan InnoDB
            $table->charset = 'utf8'; // Charset untuk mendukung emoji
            $table->collation = 'utf8_unicode_ci'; // Collation untuk Unicode penuh
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('psi_id')->unsigned();
            $table->bigInteger('psch_id')->unsigned();
            $table->bigInteger('topic_id')->unsigned();
            $table->enum('consul_status', ['pending', 'scheduled', 'ongoing', 'completed', 'failed'])->default('pending');
            $table->text('patient_complaint');
            $table->text('psikolog_note');            
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('psi_id')->references('id')->on('psikolog')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('psch_id')->references('id')->on('psikolog_schedules')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('topic_id')->references('id')->on('topics')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['psi_id']);
            $table->dropForeign(['psch_id']);
            $table->dropForeign(['topic_id']);
        });
        Schema::dropIfExists('consultations');
    }
};
