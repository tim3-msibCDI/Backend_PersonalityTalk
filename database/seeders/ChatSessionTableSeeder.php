<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatSessionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('chat_sessions')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('chat_sessions')->insert([
            [
                'id' => '1',
                'user_id' => '12',
                'psi_id' => '3',
                'consultation_id' => '14',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'created_at' => '2024-11-29 08:19:19',
                'updated_at' => '2024-11-29 08:19:19',
            ],
            [
                'id' => '2',
                'user_id' => '12',
                'psi_id' => '6',
                'consultation_id' => '18',
                'start_time' => '06:00:00',
                'end_time' => '07:00:00',
                'created_at' => '2024-12-02 02:32:00',
                'updated_at' => '2024-12-02 02:32:00',
            ],
            [
                'id' => '3',
                'user_id' => '12',
                'psi_id' => '3',
                'consultation_id' => '19',
                'start_time' => '06:00:00',
                'end_time' => '07:00:00',
                'created_at' => '2024-12-02 03:21:34',
                'updated_at' => '2024-12-02 03:21:34',
            ],
            [
                'id' => '4',
                'user_id' => '12',
                'psi_id' => '1',
                'consultation_id' => '21',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'created_at' => '2024-12-03 01:45:16',
                'updated_at' => '2024-12-03 01:45:16',
            ],
            // [
            //     'id' => '5',
            //     'user_id' => '12',
            //     'psi_id' => '4',
            //     'consultation_id' => '1',
            //     'start_time' => '10:37:35',
            //     'end_time' => '16:37:42',
            //     'created_at' => '2024-12-03 03:37:52',
            //     'updated_at' => '2024-12-03 03:37:54',
            // ],
            [
                'id' => '6',
                'user_id' => '12',
                'psi_id' => '1',
                'consultation_id' => '26',
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'created_at' => '2024-12-13 07:31:44',
                'updated_at' => '2024-12-13 07:31:44',
            ],
            [
                'id' => '7',
                'user_id' => '12',
                'psi_id' => '1',
                'consultation_id' => '27',
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'created_at' => '2024-12-14 03:35:35',
                'updated_at' => '2024-12-14 03:35:35',
            ],
            [
                'id' => '8',
                'user_id' => '12',
                'psi_id' => '6',
                'consultation_id' => '32',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'created_at' => '2024-12-16 04:26:02',
                'updated_at' => '2024-12-16 04:26:02',
            ],
        ]);
    }
}
