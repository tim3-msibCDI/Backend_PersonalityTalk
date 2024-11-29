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
        Schema::table('psikolog_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('consul_id')->after('user_id')->nullable();
            $table->foreign('consul_id')->references('id')->on('consultations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {  
        Schema::table('psikolog_reviews', function (Blueprint $table) {
            $table->dropForeign(['consul_id']);
            $table->dropColumn('consul_id');

        });
    }
};
