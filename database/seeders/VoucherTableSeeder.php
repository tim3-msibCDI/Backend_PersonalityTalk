<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class VoucherTableSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Voucher::truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('vouchers')->insert([
            [
                'code' => 'DISC100',
                'voucher_type' => 'consultation',
                'discount_value' => 10000, 
                'min_transaction_amount' => 50000,
                'valid_from' => Carbon::now()->subDays(10),
                'valid_to' => Carbon::now()->subDays(5),
                'quota' => 100,
                'used' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'COURSE15K',
                'voucher_type' => 'course',
                'discount_value' => 15000, 
                'min_transaction_amount' => 100000,
                'valid_from' => Carbon::now()->subDays(5),
                'valid_to' => Carbon::now()->addDays(60),
                'quota' => 50,
                'used' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'WELCOME20K',
                'voucher_type' => 'consultation',
                'discount_value' => 20000, 
                'min_transaction_amount' => 50000,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(90),
                'quota' => 200,
                'used' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'JADIHEMAT17',
                'voucher_type' => 'consultation',
                'discount_value' => 17000, 
                'min_transaction_amount' => 40000,
                'valid_from' => Carbon::now()->subDays(5),
                'valid_to' => Carbon::now()->addDays(10),
                'quota' => 300,
                'used' => 300,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
