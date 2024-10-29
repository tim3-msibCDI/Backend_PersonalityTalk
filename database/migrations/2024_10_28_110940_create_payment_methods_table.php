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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id(); 
            $table->string('name', 100); 
            $table->enum('type', ['QRIS', 'Bank Transfer']); 
            $table->string('bank_code', 50)->nullable(); 
            $table->string('logo'); 
            $table->boolean('is_active')->default(1); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
