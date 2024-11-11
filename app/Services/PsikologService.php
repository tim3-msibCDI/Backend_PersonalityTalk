<?php

namespace App\Services;

use App\Models\User;
use App\Models\Psikolog;
use App\Models\PsikologPrice;
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

            // Tentukan psikolog_price_id berdasarkan kode pada sipp
            if (empty($data['sipp'])) {
                $psikologPriceId = 1; // Default ID if SIPP is empty
            } else {
                $sippParts = explode('-', $data['sipp']);
                $sippCode = $sippParts[2] ?? null;

                if (!$sippCode) {
                    throw new \Exception("Format SIPP tidak valid.");  
                }

                $psikologPrice = PsikologPrice::where('code', $sippCode)->first();
                $psikologPriceId = $psikologPrice->id ?? 1;
            }

            // Simpan data psikolog
            $psikolog = Psikolog::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'],
                'psikolog_price_id' => $psikologPriceId,
                'description' => $data['description'],
                'sipp' => $data['sipp'],
                'practice_start_date' => $data['practice_start_date'],
                'status' => 'pending',
                'is_active' => false,
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
            throw $e;
        }
    }

}
