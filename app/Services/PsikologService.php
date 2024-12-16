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
                $photoProfilePath = Storage::disk('public')->put('psikolog_photos', $data['photo_profile']);
                $photoProfilePath = 'storage/' . $photoProfilePath; 
            }

            if (!$photoProfilePath) {
                throw new \Exception("Gagal menyimpan foto profil.");
            }

            // Simpan data pengguna
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->phone_number = $data['phone_number'];
            $user->date_birth = $data['date_birth'];
            $user->gender = $data['gender'];
            $user->photo_profile = $photoProfilePath;
            $user->role = 'P'; // P = psikolog
            $user->save();

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

            if ($data['role'] === 'P') {
                $categoryId = 1; // category ID for psikolog
            }else {     
                $categoryId = 2; // category ID for konselor
            }

           // Simpan data psikolog
            $psikolog = new Psikolog();
            $psikolog->user_id = $user->id;
            $psikolog->category_id = $categoryId;
            $psikolog->psikolog_price_id = $psikologPriceId;
            $psikolog->description = $data['description'];
            $psikolog->sipp = $data['sipp'] ?? null;
            $psikolog->practice_start_date = $data['practice_start_date'] ?? null;
            $psikolog->status = 'pending';
            $psikolog->is_active = false;
            $psikolog->save();

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
