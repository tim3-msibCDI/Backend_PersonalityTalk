<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;


class AuthController extends BaseController
{
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function userloginAction(Request $request)
    {
        Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ], 
        [
            'email.required' => 'Email wajib diisi',
            'password.required' => 'Password wajib diisi',
        ])->validate();

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            $success = [
                'token' => $token,
                'name' => $user->name,
                'role' => $user->role
            ];

            return $this->sendResponse($success, 'Anda berhasil login.');
        } else {
            return $this->sendError('Unauthorised', ['error' => 'Unauthorised'], 401);
        }
    }

    public function userRegisterSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F', // 'M' = Male, 'F' = Female
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Gender wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction(); 

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), 
                'phone_number' => $request->phone_number,
                'date_birth' => $request->date_birth,
                'gender' => $request->gender,
                'role' => 'U' // Role pengguna umum
            ]);

            DB::commit();

            // Buat token untuk pengguna baru
            $token = $user->createToken('auth_token')->plainTextToken;

            $success = [
                'token' => $token,
                'name' => $user->name,
                'role' =>$user->role,
            ];

            return $this->sendResponse($success, 'Anda berhasil terdaftar.');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Register Error', 'Terjadi kesalahan saat mendaftarkan pengguna: ' . $e->getMessage(), 500);
        }
    }

    public function mahasiswaRegisterSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F', // 'M' = Male, 'F' = Female
            'universitas' => 'required|string|max:255',
            'nim' => 'required|string|max:100',
            'jurusan' => 'required|string|max:255',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib diisi.',
            'universitas.required' => 'Universitas wajib diisi.',
            'nim.required' => 'NIM wajib diisi.',
            'jurusan.required' => 'Jurusan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction(); 

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'date_birth' => $request->date_birth,
                'gender' => $request->gender,
                'role' => 'M', // Role mahasiswa
            ]);

            // Masukkan data mahasiswa 
            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'universitas' => $request->universitas,
                'jurusan' => $request->jurusan,
            ]);
            DB::commit();

            // Buat token untuk pengguna baru
            $token = $user->createToken('auth_token')->plainTextToken;

            $success = [
                'token' => $token,
                'name' => $user->name,
            ];

            return $this->sendResponse($success, 'Anda berhasil terdaftar.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Register Error', 'Terjadi kesalahan saat mendaftarkan mahasiswa: ' . $e->getMessage(), 500);
        }
    }


    // Redirect ke Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Menangani callback dari Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->users();
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                Auth::login($user);
            } else {
                // Jika pengguna belum terdaftar, buat akun baru
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt('password_default'),
                ]);
                Auth::login($user);
            }
            $token = $user->createToken('auth_token')->plainTextToken;

            return redirect()->to('http://localhost:3000/auth/callback?token=' . $token);
        } catch (\Exception $e) {
            return redirect('/login');
        }
    }

    


}
