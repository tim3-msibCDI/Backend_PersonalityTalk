<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TopicTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $topics = [
            ['topic_name' => 'Umum'],
            ['topic_name' => 'Keluarga'],
            ['topic_name' => 'Komunikasi'],
            ['topic_name' => 'Percintaan'],
            ['topic_name' => 'Karir'],
            ['topic_name' => 'Minat Bakat'],
            ['topic_name' => 'Depresi'],
            ['topic_name' => 'Pendidikan'],
            ['topic_name' => 'Kecemasan'],
            ['topic_name' => 'Kepribadian'],
            ['topic_name' => 'Kecanduan'],
            ['topic_name' => 'Kesepian'],
        ];

        Schema::disableForeignKeyConstraints();
        Topic::truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($topics as $topic) {
            Topic::create($topic);
        }
    }
}
