<?php

namespace Database\Seeders;

use App\Models\PsikologPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PsikologPriceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prices = [
            ['code' => '','price' => 50000],
            ['code' => '01','price' => 100000],
            ['code' => '02','price' => 200000],
            ['code' => '03','price' => 300000],
            ['code' => '04','price' => 400000],

        ];

        Schema::disableForeignKeyConstraints();
        PsikologPrice::truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($prices as $price) {
            PsikologPrice::create($price);
        }
    }
}
