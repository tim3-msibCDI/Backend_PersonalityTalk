<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ArticleCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        Schema::disableForeignKeyConstraints();
        ArticleCategory::truncate();
        Schema::enableForeignKeyConstraints();

        // Definisikan array kategori yang akan di-seed
        $categories = [
            ['name' => 'Teknologi'],
            ['name' => 'Kesehatan'],
            ['name' => 'Pendidikan'],
            ['name' => 'Bisnis'],
            ['name' => 'Hiburan'],
            ['name' => 'Olahraga'],
            ['name' => 'Travel'],
            ['name' => 'Gaya Hidup'],
            ['name' => 'Politik'],
            ['name' => 'Kuliner'],
        ];

        // Masukkan data kategori ke dalam tabel article_categories
        foreach ($categories as $category) {
            ArticleCategory::create($category);
        }
    }
}
