<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Psikolog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PsikologController extends BaseController
{
    protected $psikologService;

    // PsikologService constructor
    public function __construct(PsikologService $psikologService)
    {
        $this->psikologService = $psikologService;
    }

    /**
     * Handle the psikolog registration process
     */
    public function psikologRegister(Request $request)
    {
        // Validasi input untuk user dan psikolog
        $validator = Validator::make($request->all(), [
            // Validasi untuk tabel 'users'
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F',
            'photo_profile' => 'required|image|mimes:jpeg,png,jpg|max:2048', 

            // Validasi untuk tabel 'psikolog'
            'category_id' => 'required|exists:psikolog_categories,id',
            'psikolog_price_id' => 'required|exists:psikolog_prices,id',
            'description' => 'required|string|max:255',
            'sipp' => 'required|string|max:255',
            'practice_start_date' => 'required|date',
            'topics' => 'required|array', // Topik keahlian harus dalam bentuk array
            'topics.*' => 'exists:topics,id', // Setiap topik harus ada di tabel 'topics'
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus valid dan terdiri dari 10-15 angka.',
            'photo_profile.required' => 'Foto profil wajib diunggah.',
            'photo_profile.image' => 'Foto profil harus berupa gambar.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'psikolog_price_id.required' => 'Harga wajib dipilih.',
            'topics.required' => 'Pilih setidaknya satu topik keahlian.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Menggunakan function yang terdapat pada PsikologService
            $psikolog = $this->psikologService->registerPsikolog($request->all());

            return response()->json([
                'message' => 'Psikolog berhasil didaftarkan.',
                'psikolog' => $psikolog,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan saat registrasi psikolog. ' . $e->getMessage()], 500);
        }
    }

}
