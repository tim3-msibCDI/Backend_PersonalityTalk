<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use App\Models\PsikologPrice;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class UserProfileController extends BaseController
{
    /**
     * Get User Info
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
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
     * Get Detail User Profile
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
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
            'phone_number' => $user->phone_number,
            'joined_since' =>$user->created_at->translatedFormat('d F Y'),
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
            $bankDetails = $user->psikolog->bank;
            if ($psikologDetails) {
                $profileData['psikolog_details'] = [
                    'bank_id' => $bankDetails->id ?? null,
                    'bank_name' => $bankDetails->name ?? null,
                    'rekening' => $psikologDetails->account_number ?? null,
                    'sipp' => $psikologDetails->sipp,
                    'practice_start_date' => $psikologDetails->practice_start_date 
                        ? Carbon::parse($psikologDetails->practice_start_date)->format('Y-m-d') 
                        : null,
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
     * Update User Profile
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
            'date_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:M,F',
        
            // Mahasiswa
            'universitas' => 'sometimes|string|max:255',
            'jurusan' => 'sometimes|string|max:255',
        
            // Psikolog
            'bank_id' => 'sometimes|exists:payment_methods,id',
            'rekening' => 'sometimes|string|max:255',
            'sipp' => 'sometimes|string|max:255',
            'practice_start_date' => 'sometimes|date',
            'description' => 'nullable|string',
            'topics' => 'sometimes|array',
            'topics.*' => 'exists:topics,id',
        ], [
            'phone_number.regex' => 'Format nomor telepon salah.',

            'universitas.required_if' => 'Universitas wajib diisi.',
            'jurusan.required_if' => 'Jurusan wajib diisi.',

            'sipp.required_if' => 'SIPP wajib diisi.',
            'practice_start_date.required_if' => 'Tanggal mulai praktik wajib diisi.',
            'topics.required_if' => 'Topik wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors(), 422);
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

                // Update untuk SIPP dan juga harga psikolog
                if (empty($validator->validated()['sipp'])) {
                    $psikologPriceId = 1; // Default ID if SIPP is empty
                } else {
                    $sippParts = explode('-', $validator->validated()['sipp']);
                    $sippCode = $sippParts[2] ?? null;

                    if (!$sippCode) {
                        throw new \Exception("Format SIPP tidak valid.");  
                    }

                    $psikologPrice = PsikologPrice::where('code', $sippCode)->first();
                    $psikologPriceId = $psikologPrice->id ?? 1;
                }

                // Update
                $user->psikolog()->update([
                    'sipp' => $request->sipp,
                    'psikolog_price_id' => $psikologPriceId,
                    'practice_start_date' => $request->practice_start_date,
                    'description' => $request->description,
                    'bank_id' => $request->bank_id,
                    'account_number' => $request->rekening,
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
            return $this->sendError('Gagal Memperbarui profil.', $e->getMessage(), 500);
        }
    }

    /**
     * Upgrade to Mahasiswa
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
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
            return $this->sendError('Error, Gagal upgrade ke mahasiswa.', [$e->getMessage()], 500);
        }

    }

    /**
     * Update Password
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function updatePassword(Request $request){

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'new_password.required' => 'Password baru wajib diisi',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi Gagal', $validator->errors(), 422);
        }

        try {
            // Periksa apakah password saat ini cocok
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Password saat ini tidak cocok.', 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->sendResponse('Password berhasil diperbarui.', null);
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat memperbarui password.', [$e->getMessage()], 500);
        }
    }

}  

    

    


