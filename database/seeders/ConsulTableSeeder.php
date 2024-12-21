<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ConsulTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {  
        Schema::disableForeignKeyConstraints();
        DB::table('consultations')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('consultations')->insert([
            ['id' => 1, 'user_id' => 12, 'psi_id' => 2, 'psch_id' => 136, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 02:05:20', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 2, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 192, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 02:43:38', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 3, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 228, 'topic_id' => 3, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 03:25:09', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 4, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 48, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 03:32:36', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 5, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 46, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 03:34:47', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 6, 'user_id' => 12, 'psi_id' => 2, 'psch_id' => 137, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 03:41:19', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 7, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 47, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 04:05:08', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 8, 'user_id' => 12, 'psi_id' => 2, 'psch_id' => 138, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 04:06:23', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 9, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 197, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 04:12:37', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 10, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 496, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 04:14:27', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 11, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 497, 'topic_id' => 2, 'consul_status' => 'pending', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-28 04:19:49', 'updated_at' => '2024-11-28 04:19:49'],
            ['id' => 12, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 201, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-29 02:17:21', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 13, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 203, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-29 03:15:58', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 14, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 204, 'topic_id' => 2, 'consul_status' => 'ongoing', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-29 03:19:56', 'updated_at' => '2024-11-29 08:19:19'],
            ['id' => 15, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 526, 'topic_id' => 1, 'consul_status' => 'pending', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-30 01:50:24', 'updated_at' => '2024-11-30 01:50:24'],
            ['id' => 16, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 76, 'topic_id' => 1, 'consul_status' => 'pending', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-30 02:50:06', 'updated_at' => '2024-11-30 02:50:06'],
            ['id' => 17, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 527, 'topic_id' => 11, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-11-30 04:10:53', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 18, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 1096, 'topic_id' => 1, 'consul_status' => 'completed', 'patient_complaint' => 'Saya sedang patah hati', 'psikolog_note' => null, 'created_at' => '2024-12-02 01:45:31', 'updated_at' => '2024-12-02 03:17:58'],
            ['id' => 19, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 817, 'topic_id' => 3, 'consul_status' => 'completed', 'patient_complaint' => 'kunug', 'psikolog_note' => null, 'created_at' => '2024-12-02 03:20:03', 'updated_at' => '2024-12-02 03:36:52'],
            ['id' => 20, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 1097, 'topic_id' => 1, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-02 07:22:24', 'updated_at' => '2024-12-02 07:26:42'],
            ['id' => 21, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 646, 'topic_id' => 1, 'consul_status' => 'completed', 'patient_complaint' => 'halo', 'psikolog_note' => null, 'created_at' => '2024-12-03 01:28:34', 'updated_at' => '2024-12-14 04:47:52'],
            ['id' => 22, 'user_id' => 12, 'psi_id' => 2, 'psch_id' => 754, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-04 08:31:58', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 23, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 666, 'topic_id' => 6, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-11 02:26:50', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 24, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 665, 'topic_id' => 5, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-11 02:43:07', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 25, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 206, 'topic_id' => 2, 'consul_status' => 'failed', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-12 03:10:55', 'updated_at' => '2024-12-14 03:47:01'],
            ['id' => 26, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 690, 'topic_id' => 1, 'consul_status' => 'completed', 'patient_complaint' => 'Sakit nih boss', 'psikolog_note' => null, 'created_at' => '2024-12-13 07:30:21', 'updated_at' => '2024-12-14 04:47:52'],
            ['id' => 27, 'user_id' => 12, 'psi_id' => 1, 'psch_id' => 700, 'topic_id' => 4, 'consul_status' => 'scheduled', 'patient_complaint' => 'sdada]\ndsada\ndsad\nDSAda\nDasd\ndsad\ndasd\ndadsa\nDsad\ndsad\ndsad\nDsad\ndsad\ndsad\nDsad\nDsada\ndsad\ndsad\nDSAD', 'psikolog_note' => null, 'created_at' => '2024-12-14 03:29:32', 'updated_at' => '2024-12-15 10:40:00'],
            ['id' => 31, 'user_id' => 12, 'psi_id' => 3, 'psch_id' => 194, 'topic_id' => 2, 'consul_status' => 'pending', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-15 03:33:58', 'updated_at' => '2024-12-15 03:33:58'],
            ['id' => 32, 'user_id' => 12, 'psi_id' => 6, 'psch_id' => 1104, 'topic_id' => 5, 'consul_status' => 'scheduled', 'patient_complaint' => '', 'psikolog_note' => null, 'created_at' => '2024-12-16 03:55:08', 'updated_at' => '2024-12-16 04:26:02']
        ]);
    }
}
