<?php

namespace App\Http\Controllers\API;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // Jika yang login adalah mahasiswa
        if ($user->role === 'M') {
            $mahasiswaDetails = $user->mahasiswa; 
            if ($mahasiswaDetails) {
                $profileData['mahasiswa_details'] = [
                    'universitas' => $mahasiswaDetails->universitas,
                    'jurusan' => $mahasiswaDetails->jurusan,
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
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib diisi.',

            'universitas.required_if' => 'Universitas wajib diisi jika role adalah mahasiswa.',
            'jurusan.required_if' => 'Jurusan wajib diisi jika role adalah mahasiswa.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $user->update($request->only('name', 'email', 'phone_number', 'date_birth', 'gender'));

            // Jika user adalah mahasiswa maka update
            if ($user->role === 'M') {
                $user->mahasiswa()->update([
                    'universitas' => $request->universitas,
                    'jurusan' => $request->jurusan,
                ]);
            }

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
            return $this->sendError('Validation Error', $validator->errors(), 422);
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
            throw new \Exception('Error. Gagal update password ' . $e->getMessage());
        }
    }

}  

    

    


