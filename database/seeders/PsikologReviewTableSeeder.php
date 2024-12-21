<?php

namespace Database\Seeders;

use App\Models\PsikologReview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PsikologReviewTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PsikologReview::truncate();
        Schema::enableForeignKeyConstraints();

        PsikologReview::insert([
            // Psikolog 1
            [
                'user_id' => 12,
                'psi_id' => 1,
                'consul_id' => 4,
                'rating' => 5,
                'review' => 'Psikolog yang sangat profesional dan membantu.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 1,
                'consul_id' => 5,
                'rating' => 4,
                'review' => 'Sesi konsultasi berjalan lancar. Sangat direkomendasikan!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 1,
                'consul_id' => 7,
                'rating' => 5,
                'review' => 'Membantu, tetapi perlu lebih ramah dalam komunikasi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Psikolog 2
            [
                'user_id' => 12,
                'psi_id' => 2,
                'consul_id' => 1,
                'rating' => 5,
                'review' => 'Pengalaman luar biasa, sangat direkomendasikan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 2,
                'consul_id' => 6,
                'rating' => 4,
                'review' => 'Penjelasan sangat membantu, tetapi perlu lebih fokus.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 2,
                'consul_id' => 8,
                'rating' => 5,
                'review' => 'Psikolog sangat ramah dan profesional.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
           
            // Psikolog 3
            [
                'user_id' => 12,
                'psi_id' => 3,
                'consul_id' => 2,
                'rating' => 5,
                'review' => 'Sangat direkomendasikan untuk sesi berikutnya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 3,
                'consul_id' => 3,
                'rating' => 2,
                'review' => 'Kurang cocok dengan pendekatan psikolog.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 3,
                'consul_id' => 9,
                'rating' => 4,
                'review' => 'Konsultasi membantu, tetapi durasinya agak terbatas.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'user_id' => 9,
            //     'psi_id' => 3,
            //     'rating' => 5,
            //     'review' => 'Penjelasan sangat mudah dimengerti.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 3,
            //     'rating' => 3,
            //     'review' => 'Ada beberapa poin yang kurang relevan.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            
            // [
            //     'user_id' => 8,
            //     'psi_id' => 4,
            //     'rating' => 4,
            //     'review' => 'Psikolog cukup membantu, tetapi bisa lebih baik.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 4,
            //     'rating' => 5,
            //     'review' => 'Konsultasi terbaik yang pernah saya alami.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 4,
            //     'rating' => 4,
            //     'review' => 'Sesi berjalan lancar, saya puas.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 4,
            //     'rating' => 5,
            //     'review' => 'Sangat profesional dan empati tinggi.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 4,
            //     'rating' => 5,
            //     'review' => 'Layanan sangat memuaskan, membantu banyak.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            

            // [
            //     'user_id' => 8,
            //     'psi_id' => 5,
            //     'rating' => 4,
            //     'review' => 'Psikolog cukup membantu, tetapi bisa lebih baik.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 5,
            //     'rating' => 5,
            //     'review' => 'Konsultasi terbaik yang pernah saya alami.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 5,
            //     'rating' => 4,
            //     'review' => 'Sesi berjalan lancar, saya puas.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 5,
            //     'rating' => 5,
            //     'review' => 'Sangat profesional dan empati tinggi.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 5,
            //     'rating' => 5,
            //     'review' => 'Layanan sangat memuaskan, membantu banyak.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],

            // Psikolog 6
            [
                'user_id' => 12,
                'psi_id' => 6,
                'consul_id' => 10,
                'rating' => 5,
                'review' => 'Sangat direkomendasikan untuk sesi berikutnya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 12,
                'psi_id' => 6,
                'consul_id' => 11,
                'rating' => 2,
                'review' => 'Kurang cocok dengan pendekatan psikolog.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
           
            // [
            //     'user_id' => 9,
            //     'psi_id' => 6,
            //     'rating' => 4,
            //     'review' => 'Konsultasi membantu, tetapi durasinya agak terbatas.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 9,
            //     'psi_id' => 6,
            //     'rating' => 5,
            //     'review' => 'Penjelasan sangat mudah dimengerti.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'user_id' => 8,
            //     'psi_id' => 6,
            //     'rating' => 3,
            //     'review' => 'Ada beberapa poin yang kurang relevan.',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
        ]);
    }
}
