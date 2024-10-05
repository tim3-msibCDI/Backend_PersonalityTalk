<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
    public function loginAction(Request $request)
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
                'name' => $user->name
            ];

            return $this->sendResponse($success, 'Anda berhasil login.');
        } else {
            return $this->sendError('Unauthorised', ['error' => 'Unauthorised'], 401);
        }
    }

    public function registerSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F', // 'M' = Male, 'F' = Female
            'role' => 'required|in:M,U', // 'M' = mahasiswa, 'U' = umum
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            // 'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Gender wajib diisi.',
            'role.required' => 'Role wajib diisi.',
        ]);

        // Jika validasi gagal, kembalikan error
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        // Buat pengguna baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash password
            'phone_number' => $request->phone_number,
            'date_birth' => $request->date_birth,
            'gender' => $request->gender,
            'role' => $request->role,
        ]);

        // Buat token untuk pengguna baru
        $token = $user->createToken('auth_token')->plainTextToken;

        $success = [
            'token' => $token,
            'name' => $user->name,
        ];

        return $this->sendResponse($success, 'Anda berhasil terdaftar.');
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
            $googleUser = Socialite::driver('google')->stateless()->user();
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
