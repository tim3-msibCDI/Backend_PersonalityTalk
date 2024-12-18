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
            $table->unsignedBigInteger('consul_id')->nullable(false)->change();
        });
    }

     /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psikolog_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('consul_id')->nullable()->change();
        });
    }
};
