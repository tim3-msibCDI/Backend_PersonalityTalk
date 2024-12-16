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
        Schema::create('psikolog_schedules', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // Pastikan tabel menggunakan InnoDB
            $table->charset = 'utf8'; // Charset untuk mendukung emoji
            $table->collation = 'utf8_unicode_ci'; // Collation untuk Unicode penuh
            $table->id();
            $table->bigInteger('psikolog_id')->unsigned();
            $table->date('date'); 
            $table->boolean('is_available')->default(true); 
            $table->bigInteger('msch_id')->unsigned();
            $table->foreign('psikolog_id')->references('id')->on('psikolog')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('msch_id')->references('id')->on('main_schedules')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('psikolog_schedules', function (Blueprint $table) {
            $table->dropForeign(['psikolog_id']);
            $table->dropForeign(['msch_id']);
        });
        Schema::dropIfExists('psikolog_schedules');
    }
};
