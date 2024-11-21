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
        Schema::table('psikolog', function (Blueprint $table) {
            $table->string('sipp')->nullable()->change();
            $table->unsignedBigInteger('bank_id')->nullable()->after('sipp');
            $table->string('account_number')->nullable()->after('bank_id');

            $table->foreign('bank_id')->references('id')->on('payment_methods')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psikolog', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn(['bank_id', 'account_number']);
            $table->string('sipp')->nullable(false)->change();
        });
    }
};
