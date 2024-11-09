<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        Schema::disableForeignKeyConstraints();
        PaymentMethod::truncate();
        Schema::enableForeignKeyConstraints();
        
        PaymentMethod::insert([
            [
                'name' => 'Midtrans',
                'type' => 'Pembayaran Otomatis',
                'bank_code' => null,
                'logo' => 'payment_methods/midtrans.png', 
                'no_rek' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank Syariah Indonesia (BSI)',
                'type' => 'Transfer Bank',
                'bank_code' => '451', 
                'logo' => 'payment_methods/bsi.png', 
                'no_rek' => '999999999',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
