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
            ['price' => 100000],
            ['price' => 50000],
            ['price' => 25000],
        ];

        Schema::disableForeignKeyConstraints();
        PsikologPrice::truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($prices as $price) {
            PsikologPrice::create($price);
        }
    }
}
