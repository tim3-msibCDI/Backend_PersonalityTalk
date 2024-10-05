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
        Schema::create('psikolog_topics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('psikolog_id')->unsigned();
            $table->bigInteger('topic_id')->unsigned();
            $table->foreign('psikolog_id')->references('id')->on('psikolog')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('topic_id')->references('id')->on('topics')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psikolog_topics', function (Blueprint $table) {
            $table->dropForeign(['psikolog_id']);
            $table->dropForeign(['topic_id']);
        });

        Schema::dropIfExists('psikolog_topics');
    }
};
