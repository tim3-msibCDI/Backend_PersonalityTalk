<?php

namespace Database\Seeders;

use App\Models\Mitra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MitraTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Schema::disableForeignKeyConstraints();
        Mitra::truncate();
        Schema::enableForeignKeyConstraints();

        Mitra::insert([
            [
                'name' => 'SMK Telkom Purwokerto',
                'img' => 'storage/mitra_photos/SMK-Telkom-Purwokerto.png',
                'description' => 'SMK Telkom Purwokerto adalah salah satu sekolah teknik di Purwokerto, Jawa Tengah, Indonesia.',
            ],
            [
                'name' => 'SMK Muhammadiyah 1 Semarang',
                'img' => 'storage/mitra_photos/SMKMuhammadiyah1Semarang.png',
                'description' => 'SMK Muhammadiyah 1 Semarang adalah salah satu sekolah teknik di Semarang, Jawa Tengah, Indonesia.',
            ],
            [
                'name' => 'SMK Yayasan Pharmasi Semarang',
                'img' => 'storage/mitra_photos/SMKYayasanPharmasiSemarang.png',
                'description' => 'SMK Yayasan Pharmasi Semarang adalah salah satu sekolah teknik di Semarang, Jawa Tengah, Indonesia.',
            ]
        ]);
    }
}
