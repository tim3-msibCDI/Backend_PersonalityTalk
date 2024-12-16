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
        Schema::create('consul_transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // Pastikan tabel menggunakan InnoDB
            $table->charset = 'utf8'; // Charset untuk mendukung emoji
            $table->collation = 'utf8_unicode_ci'; // Collation untuk Unicode penuh
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->unsignedBigInteger('consultation_id'); 
            $table->unsignedBigInteger('voucher_id')->nullable(); 
            $table->unsignedBigInteger('payment_method_id');
            $table->decimal('consul_fee', 10, 2); 
            
            // Payment Gateway Attributes
            $table->enum('status', ['pending', 'completed', 'failed', 'canceled'])->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->timestamp('payment_expiration')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->string('failure_reason')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('set null');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('restrict');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consul_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['consultation_id']);
            $table->dropForeign(['voucher_id']);
            $table->dropForeign(['payment_method_id']);
        });
        Schema::dropIfExists('consul_transactions');
    }
};
