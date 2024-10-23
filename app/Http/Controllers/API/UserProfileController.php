<?php

namespace App\Http\Controllers\API;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class UserProfileController extends BaseController
{
    /**
     * User info for navbar
     */
    public function getUserInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        $response = [
            'name' => $user->name,
            'photo_profile' => $user->photo_profile ?? null, 
            'role' => $user->role,
        ];

        return $this->sendResponse('Data pengguna berhasil diambil.', $response );
    }

    /**
     * User info for detail profile page
     */
    public function getUserProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        $profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'photo_profile' => $user->photo_profile ?? null,
            'gender' => $user->gender,
            'date_birth' => $user->date_birth,
            'phone_number' => $user->phone_number
        ];

        // Detail jika user mahasiswa
        if ($user->role === 'M') {
            $mahasiswaDetails = $user->mahasiswa; 
            if ($mahasiswaDetails) {
                $profileData['mahasiswa_details'] = [
                    'universitas' => $mahasiswaDetails->universitas,
                    'jurusan' => $mahasiswaDetails->jurusan,
                ];
            }
        }

         // Detail jika user psikolog
         if ($user->role === 'P') {
            $psikologDetails = $user->psikolog;
            if ($psikologDetails) {
                $profileData['psikolog_details'] = [
                    'sipp' => $psikologDetails->sipp,
                    'practice_start_date' => $psikologDetails->practice_start_date,
                    'description' => $psikologDetails->description,
                    'topics' => $psikologDetails->psikolog_topic->map(function($pt) {
                        return [
                            'id' => $pt->topic->id,
                            'topic_name' => $pt->topic->topic_name,
                        ]; 
                    }),
                ];
            }
        }

        return $this->sendResponse( 'Profil pengguna berhasil diambil.', $profileData);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F', // 'M' = Male, 'F' = Female

            // Validation for Mahasiswa
            'universitas' => 'required_if:role,M|string|max:255',
            'jurusan' => 'required_if:role,M|string|max:255',

            // Validation for Psikolog
            'sipp' => 'required_if:role,P|string|max:255',
            'practice_start_date' => 'required_if:role,P|date',
            'description' => 'nullable|string',
            'topics' => 'required_if:role,P|array',
            'topics.*' => 'exists:topics,id',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib diisi.',

            'universitas.required_if' => 'Universitas wajib diisi.',
            'jurusan.required_if' => 'Jurusan wajib diisi.',

            'sipp.required_if' => 'SIPP wajib diisi.',
            'practice_start_date.required_if' => 'Tanggal mulai praktik wajib diisi.',
            'topics.required_if' => 'Topik wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $user->update($request->only('name', 'email', 'phone_number', 'date_birth', 'gender'));

            // Update untuk mahasiswa
            if ($user->role === 'M') {
                $user->mahasiswa()->update([
                    'universitas' => $request->universitas,
                    'jurusan' => $request->jurusan,
                ]);
            }

            // Update untuk psikolog
            if ($user->role === 'P') {
                $user->psikolog()->update([
                    'sipp' => $request->sipp,
                    'practice_start_date' => $request->practice_start_date,
                    'description' => $request->description,
                ]);

                // Cek apakah ada perubahan dalam topik
                $existingTopicIds = $user->psikolog->psikolog_topic->pluck('topic_id')->toArray();
                $newTopicIds = $request->topics;

                // Jika topik berubah, lakukan pembaruan
                if (array_diff($existingTopicIds, $newTopicIds) || array_diff($newTopicIds, $existingTopicIds)) {
                    // Hapus topik lama
                    $user->psikolog->psikolog_topic()->delete();

                    // Tambahkan topik baru
                    $newTopics = collect($newTopicIds)->map(function ($topicId) {
                        return ['topic_id' => $topicId];
                    });
                    $user->psikolog->psikolog_topic()->createMany($newTopics->toArray());
                }
            }
            
            $user->load('psikolog.psikolog_topic');
            DB::commit();
            return $this->sendResponse('Profil berhasil diperbarui.', $user);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Error. Gagal memperbarui profil: ' . $e->getMessage());
        }
    }

    /**
     * Update to mahasiswa
     */
    public function updateToMahasiswa(Request $request)
    {
        // Check if the user is authenticated
        $user = Auth::user();
        if (!$user || $user->role === 'M') {
            return $this->sendError('Tidak dapat memperbarui. Anda sudah mahasiswa atau tidak terautentikasi.', [], 403);
        }

        // Validate the required fields for mahasiswa
        $validator = Validator::make($request->all(), [
            'jurusan' => 'required|string|max:255',
            'universitas' => 'required|string|max:255',
        ], [
            'jurusan.required' => 'Jurusan wajib diisi.',
            'universitas.required' => 'Universitas wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();
            
            // Update role
            $user->role = 'M';
            $user->save();

            // Create Mahasiswa entry
            Mahasiswa::create([
                'user_id' => $user->id,
                'jurusan' => $request->jurusan,
                'universitas' => $request->universitas,
            ]);
            DB::commit();
            
            return $this->sendResponse('Berhasil diperbarui menjadi Mahasiswa.', null);            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Error. Gagal upgrade ke mahasiswa: ' . $e->getMessage());
        }

    }
}  

    

    


