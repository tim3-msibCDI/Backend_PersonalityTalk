<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PsikologCategory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PsikologCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Psikolog'],
            ['category_name' => 'Konselor'],
        ];

        Schema::disableForeignKeyConstraints();
        PsikologCategory::truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($categories as $category) {
            PsikologCategory::create($category);
        }
    }
}
