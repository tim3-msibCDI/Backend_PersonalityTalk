<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TopicTableSeeder::class);
        $this->call(PsikologCategoryTableSeeder::class);
        $this->call(PsikologPriceTableSeeder::class);
        $this->call(PsikologTableSeeder::class);
        $this->call(MainScheduleTableSeeder::class);
        $this->call(ArticleCategoryTableSeeder::class);




    }
}
