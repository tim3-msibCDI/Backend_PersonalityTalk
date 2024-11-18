<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Psikolog;
use App\Models\PsikologTopic;
use Illuminate\Database\Seeder;
use App\Services\PsikologService;
use Illuminate\Support\Facades\Schema;

class PsikologTableSeeder extends Seeder
{
    public function run()
    {    
        $psikologService = new PsikologService();

        $psikologs = [
            [
                'name' => 'Faris Fanani, S.Psi.',
                'email' => 'tes123@gmail.com',
                'password' => 'farisfanani',
                'phone_number' => '082146130950',
                'date_birth' => '1980-01-01',
                'gender' => 'M',
                'photo_profile' => new \Illuminate\Http\UploadedFile(storage_path('app/public/psikolog_photos/profile-img.jpg'), 'profile-img.jpg'),
                'category_id' => 1,
                'description' => 'Ahli dalam terapi keluarga dan percintaan.',
                'sipp' => '20181011-2023-02-1987',
                'practice_start_date' => '2015-05-01',
                'topics' => [1, 2, 3, 4, 5, 6],
            ],
            [
                'name' => 'Dewi Lestari, S.Psi., M.Psi.',
                'email' => 'dewilesta@gmail.com',
                'password' => 'dewilestari',
                'phone_number' => '082146130950',
                'date_birth' => '1985-02-15',
                'gender' => 'F',
                'photo_profile' => new \Illuminate\Http\UploadedFile(storage_path('app/public/psikolog_photos/profile-img.jpg'), 'profile-img.jpg'),
                'category_id' => 1,
                'description' => 'Ahli dalam psikologi klinis dan anak-anak.',
                'sipp' => '20181011-2023-01-1987',
                'practice_start_date' => '2016-06-10',
                'topics' => [1, 2, 4, 5, 6],
            ],
            [
                'name' => 'Budi Santoso, S.Psi., M.Psi.',
                'email' => 'budibudibudi@gmail.com',
                'password' => 'budisantoso',
                'phone_number' => '082146130950',
                'date_birth' => '1978-12-25',
                'gender' => 'M',
                'photo_profile' => new \Illuminate\Http\UploadedFile(storage_path('app/public/psikolog_photos/profile-img.jpg'), 'profile-img.jpg'),
                'category_id' => 1,
                'description' => 'Berpengalaman dalam terapi trauma dan PTSD.',
                'sipp' => '20181011-2023-03-1999',
                'practice_start_date' => '2012-03-18',
                'topics' => [1, 3],
            ],
            [
                'name' => 'Nina Anwar, S.Psi.',
                'email' => 'nianiania@gmail.com',
                'password' => 'ninaanwar',
                'phone_number' => '082146130950',
                'date_birth' => '1990-07-12',
                'gender' => 'F',
                'photo_profile' => new \Illuminate\Http\UploadedFile(storage_path('app/public/psikolog_photos/profile-img.jpg'), 'profile-img.jpg'),
                'category_id' => 2,
                'description' => 'Ahli dalam konseling pendidikan dan remaja.',
                'sipp' => '20181011-2023-04-1980',
                'practice_start_date' => '2018-09-07',
                'topics' => [1, 2, 3, 4, 5, 7, 9, 10],
            ],
            [
                'name' => 'Agus Riyadi, S.Psi.',
                'email' => 'pulopuloharjo@gmail.com',
                'password' => 'agusriyadi',
                'phone_number' => '082146130950',
                'date_birth' => '1982-05-05',
                'gender' => 'M',
                'photo_profile' => new \Illuminate\Http\UploadedFile(storage_path('app/public/psikolog_photos/profile-img.jpg'), 'profile-img.jpg'),
                'category_id' => 2,
                'description' => 'Spesialis terapi kecanduan dan manajemen stres.',
                'sipp' => '20181011-2023-00-1987',
                'practice_start_date' => '2013-11-11',
                'topics' => [1,2, 3, 6, 7, 8],
            ],
            [
                'name' => 'Laila Hasan, S.Psi., M.Psi.',
                'email' => 'fajaryumnaadani@alumni.undip.ac.id',
                'password' => 'lailahasan',
                'phone_number' => '082146130950',
                'date_birth' => '1987-03-09',
                'gender' => 'F',
                'photo_profile' => new \Illuminate\Http\UploadedFile(storage_path('app/public/psikolog_photos/profile-img.jpg'), 'profile-img.jpg'),
                'category_id' => 1,
                'description' => 'Konsultan karir dan hubungan interpersonal.',
                'sipp' => '20181011-2023-02-1987',
                'practice_start_date' => '2017-07-21',
                'topics' => [1, 2, 5, 9, 10, 11],
            ],
        ];

        Schema::disableForeignKeyConstraints();
        User::truncate();
        Psikolog::truncate();
        PsikologTopic::truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($psikologs as $data) {
            $psikologService->registerPsikolog($data);
        }
    }
}
