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
        $this->call(UserTableSeeder::class);
        $this->call(MainScheduleTableSeeder::class);
        $this->call(ArticleCategoryTableSeeder::class);
        $this->call(AdminTableSeeder::class);
        $this->call(ArticleTableSeeder::class);
        $this->call(DiseasesTableSeeder::class);
        $this->call(VoucherTableSeeder::class);
        $this->call(PaymentMethodTableSeeder::class);
        $this->call(VoucherTableSeeder::class);
        $this->call(MitraTableSeeder::class);
        $this->call(PsikologReviewTableSeeder::class);



        
    }
}
