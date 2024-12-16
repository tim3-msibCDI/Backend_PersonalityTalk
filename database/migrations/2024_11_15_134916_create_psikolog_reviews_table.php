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
        Schema::create('psikolog_reviews', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // Pastikan tabel menggunakan InnoDB
            $table->charset = 'utf8'; // Charset untuk mendukung emoji
            $table->collation = 'utf8_unicode_ci'; // Collation untuk Unicode penuh
            $table->id();
            $table->unsignedBigInteger('psi_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('rating');
            $table->text('review');
            $table->foreign('psi_id')->references('id')->on('psikolog')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psikolog_reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['psi_id']);
        });
        
        Schema::dropIfExists('psikolog_reviews');
    }
};
