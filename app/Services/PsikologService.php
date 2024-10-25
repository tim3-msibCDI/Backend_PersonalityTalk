<?php

namespace App\Services;

use App\Models\User;
use App\Models\Psikolog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PsikologService
{
    /**
     * Function untuk mendaftarkan psikolog baru
     *
     * @param array $data
     * @return Psikolog
     * @throws \Exception
     */
    public function registerPsikolog(array $data)
    {
        try {
            DB::beginTransaction();

            // Simpan foto profil jika ada
            $photoProfilePath = null;
            if (isset($data['photo_profile'])) {
                $photoProfilePath = Storage::disk('public')->put('profile_photos', $data['photo_profile']);
            }

            // Simpan data pengguna
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'],
                'date_birth' => $data['date_birth'],
                'gender' => $data['gender'],
                'photo_profile' => $photoProfilePath,
                'role' => 'P', // P = psikolog
            ]);

            // Simpan data psikolog
            $psikolog = Psikolog::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'],
                'psikolog_price_id' => $data['psikolog_price_id'],
                'description' => $data['description'],
                'sipp' => $data['sipp'],
                'practice_start_date' => $data['practice_start_date'],
                'status' => 'pending', // Default status pending
                'is_active' => false, // Default false
            ]);

            // Simpan topik keahlian
            foreach ($data['topics'] as $topicId) {
                DB::table('psikolog_topics')->insert([
                    'psikolog_id' => $psikolog->id,
                    'topic_id' => $topicId,
                ]);
            }

            DB::commit();
            return $psikolog;

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat registrasi psikolog.', [$e->getMessage()], 500);
        }
    }
}
