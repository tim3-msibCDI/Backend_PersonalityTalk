<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class AdminProfileController extends BaseController
{
     /**
     * Get Admin Info
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function getAdminInfo(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return $this->sendError('Admin tidak ditemukan', [], 404);
        }
        $data = [
            'name' => $admin->name,
            'photo_profile' => $admin->photo_profile ?? null, 
        ];
    
        return $this->sendResponse('Data admin berhasil diambil.', $data);
    }

    /**
     * Get Detail Admin Profile
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function getAdminProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return $this->sendError('Admin tidak ditemukan', [], 404);
        }
        $profileData = [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone_number' => $admin->phone_number,
            'photo_profile' => $admin->photo_profile ?? null, 
        ];

        return $this->sendResponse( 'Profil pengguna berhasil diambil.', $profileData);
    }

    /**
     * Update Admin Profile
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function updateAdminProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:admins,email,' . $admin->id,
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'phone_number.regex' => 'Format nomor telepon salah.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors(), 422);
        }

        try {
            $admin->name = $request->input('name');
            $admin->email = $request->input('email');
            $admin->phone_number = $request->input('phone_number');
            $admin->save();
            return $this->sendResponse('Profil admin berhasil diperbarui.', $admin); 
        } catch (\Exception $e) {
            return $this->sendError('Gagal memperbarui profil.', $e->getMessage(), 500);
        }
    }

    /**
     * Update Password Admin
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function updateAdminPassword(Request $request){

        $admin = Auth::guard('admin')->user();
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'new_password.required' => 'Password baru wajib diisi',
            'new_password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validasi Gagal', $validator->errors(), 422);
        }

        try {
            // Periksa apakah password saat ini cocok
            if (!Hash::check($request->current_password, $admin->password)) {
                return $this->sendError('Password saat ini tidak cocok.', 400);
            }

            $admin->password = Hash::make($request->new_password);
            $admin->save();

            return $this->sendResponse('Password admin berhasil diperbarui.', null);
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat memperbarui password admin.', [$e->getMessage()], 500);
        }
    }
}
