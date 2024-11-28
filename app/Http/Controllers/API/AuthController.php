<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use App\Services\PsikologService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;


class AuthController extends BaseController
{
    protected $psikologService;

    public function __construct(PsikologService $psikologService)
    {
        parent::__construct();
        $this->psikologService = $psikologService;
    }

    /**
     * Login User, Mahasiswa, Psikolog
     *
     * @unauthenticated
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function userloginAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ], 
        [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email salah',
            'password.required' => 'Password wajib diisi',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal.', $validator->errors(), 422);
        }
    
        // Cek apakah email ada di database
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendError('Email anda tidak terdaftar.', [], 404);
        }

         // Cek apakah user adalah psikolog dan belum di-approve atau tidak aktif
        if ($user->role === 'P') {
            $psikolog = $user->psikolog; 
            if (!$psikolog) {
                return $this->sendError('Akun psikolog tidak ditemukan.', [], 404);
            }
            if (!$psikolog->is_active || $psikolog->status !== 'approved') {
                return $this->sendError('Status akun Anda belum di-approve atau tidak aktif.', [], 403);
            }
        }
    
        // Cek apakah password benar
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return $this->sendError('Password anda salah.', [], 401);
        }
    
        // Jika email dan password benar, lakukan login
        $token = $user->createToken('auth_user_token')->plainTextToken;
        $success = [
            'token' => $token,
            'name' => $user->name,
            'role' => $user->role
        ];
    
        return $this->sendResponse('Anda berhasil login.', $success);
    }
    
    /**
     * Register User, Mahasiswa
     *
     * @unauthenticated
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|regex:/^[0-9]{10,}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F', // 'M' = Male, 'F' = Female
            'role' => 'required|in:U,M,P,K', // 'U' = Umum, 'M' = Mahasiswa, 'P' = Psikolog/Konselor

            // Validasi tambahan untuk mahasiswa
            'universitas' => 'required_if:role,M|string|max:255',
            'jurusan' => 'required_if:role,M|string|max:255',

            //Validasi tambahan untuk psikolog dan konselor
            'photo_profile' => 'required_if:role,P,K|image|mimes:jpeg,png,jpg|max:2048', 
            'description' => 'required_if:role,P,K|string|max:255',
            'sipp' => 'required_if:role,P|string|max:255',
            'practice_start_date' => 'required_if:role,P|date',
            'topics' => 'required_if:role,P,K|array',
            'topics.*' => 'exists:topics,id',

        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password harus terdiri dari minimal 8 karakter.',
            'phone_number.regex' => 'Format nomor telepon salah.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib diisi.',
            'role.required' => 'Role wajib dipilih.',

            'universitas.required_if' => 'Universitas wajib diisi jika role adalah mahasiswa.',
            'jurusan.required_if' => 'Jurusan wajib diisi jika role adalah mahasiswa.',

            'photo_profile.required_if' => 'Foto profil wajib diisi.',
            'description.required_if' => 'Deskripsi wajib diisi.',
            'sipp.required_if' => 'SIPP wajib diisi.',
            'practice_start_date.required_if' => 'Tanggal mulai praktik wajib diisi.',
            'topics.required_if' => 'Topik keahlian wajib diisi.',
            'topics.*.exists' => 'Topik tidak valid.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors(), 422);
        }
       
        try {
            DB::beginTransaction();

            if($request->role === 'M' || $request->role === 'U'){
                 // Buat user baru
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'phone_number' => $request->phone_number,
                    'date_birth' => $request->date_birth,
                    'gender' => $request->gender,
                    'role' => $request->role, // 'U', 'M', atau 'P'
                ]);

                // Jika role adalah 'M', maka simpan data tambahan mahasiswa
                if ($request->role === 'M') {
                    Mahasiswa::create([
                        'user_id' => $user->id,
                        'universitas' => $request->universitas,
                        'jurusan' => $request->jurusan,
                    ]);
                }

            }elseif ($request->role === 'P' || $request->role === 'K') {
                // Buat user psikolog dengan menggunakan psikolog service
                $this->psikologService->registerPsikolog($request->all());
                DB::commit();
                $roleName = $request->role === 'P' ? 'psikolog' : 'konselor'; 
                return $this->sendResponse("Pendaftaran anda sebagai $roleName berhasil dilakukan.");
            }

            DB::commit();

            $token = $user->createToken('auth_user_token')->plainTextToken;
            $success = [
                'name' => $user->name,
                'role' => $user->role,
                'token' => $token,
            ];
            return $this->sendResponse('Pendaftaran anda berhasil dilakukan.', $success);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat mendaftarkan pengguna: ', [$e->getMessage()], 500);
        }
    }

    /**
     * Logout User, Mahasiswa, Psikolog
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function logoutAction(Request $request){
        // Hapus token pengguna saat ini
        $request->user()->currentAccessToken()->delete();
        return $this->sendResponse('Anda berhasil logout sebagai User.');
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
            $token = $user->createToken('auth_user_token')->plainTextToken;

            return redirect()->to('http://localhost:3000/auth/callback?token=' . $token);
        } catch (\Exception $e) {
            return redirect('/login');
        }
    }

    


}
