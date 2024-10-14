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
        Schema::create('disease_complaints', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('disease_id')->unsigned();
            $table->bigInteger('complaint_id')->unsigned();
            $table->foreign('disease_id')->references('id')->on('diseases')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('complaint_id')->references('id')->on('complaints')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disease_complaints', function (Blueprint $table) {
            $table->dropForeign(['disease_id']);
            $table->dropForeign(['complaint_id']);
        });
        
        Schema::dropIfExists('disease_complaints');
    }
};
