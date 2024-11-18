<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ManageUserController extends BaseController
{   
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Menampilkan daftar user Umum
     *
     * @return \Illuminate\Http\Response
     */
    public function listUserUmum(){
        $users = User::where('role', 'U')
            ->select('id', 'name', 'phone_number', 'date_birth', 'gender', 'photo_profile')
            ->paginate(10);
        return $this->sendResponse('List untuk pengguna umum berhasil diambil.', $users);
    }

    /**
     * Membuat user Umum
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeUserUmum(Request $request){
        
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'photo_profile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ],[
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone_number.regex' => 'Format nomor telepon salah.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib diisi.',
            'photo_profile.image' => 'Format gambar tidak sesuai.',
            'photo_profile.mimes' => 'Format gambar harus JPEG, PNG, atau JPG.',
            'photo_profile.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();

            if ($request->hasFile('photo_profile')) {
                $imagePath = Storage::disk('public')->put('user_photos', $request->file('photo_profile'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan foto profile.', [], 500);
                }
            }
            $imagePath = 'storage/' . $imagePath; 

            // Generate random password
            $randomPassword = Str::random(8);
    
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($randomPassword),
                'phone_number' => $request->phone_number,
                'date_birth' => $request->date_birth,
                'gender' => $request->gender,
                'role' => 'U',
                'photo_profile' => $imagePath ?? null,
            ]);
            DB::commit();

            //Kirim kredensial pengguna melalui Pesan WhatsApp
            $target = $user->phone_number;
            $message = "Selamat, pendaftaran akun Anda berhasil!\n\n" .
                    "Berikut adalah informasi login Anda:\n" .
                    "Email: " . $user->email . "\n" .
                    "Password: " . $randomPassword . "\n\n" .
                    "Harap jaga kerahasiaan informasi ini. Selamat menggunakan layanan kami!";
            $this->notificationService->sendWhatsAppMessage($target, $message);

            return $this->sendResponse('Pengguna umum baru berhasil ditambahkan.', $user);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat menambahkan pengguna.', [$e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan detail user Umum berdasarkan ID User
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function detailUserUmum($id){
        $user = User::select('id', 'name', 'email', 'phone_number', 'date_birth', 'gender', 'photo_profile', 'role')
            ->find($id);
        return $this->sendResponse('Detail untuk pengguna umum berhasil diambil.', $user);
    }

    /**
     * Mengupdate user Umum berdasarkan ID User
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUserUmum(Request $request, $id)
    {
        $user = User::where('id', $id)->where('role', 'U')->first();
        if (!$user) {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }
        
        $validatedData = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'phone_number' => 'string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'date',
            'gender' => 'string',
            'photo_profile' => 'image|mimes:jpeg,png,jpg|max:2048',
        ],[
            'name.string' => 'Nama harus berupa teks.',
            'email.email' => 'Format email salah.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone_number.regex' => 'Format nomor telepon salah.',
            'date_birth.date' => 'Format tanggal lahir salah.',
            'gender.string' => 'Jenis kelamin harus berupa teks.',
            'photo_profile.image' => 'Format gambar tidak sesuai.',
            'photo_profile.mimes' => 'Format gambar harus JPEG, PNG, atau JPG.',
            'photo_profile.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }
        
        try {
            DB::beginTransaction();
            $dataToUpdate = $validatedData->validated();

            if ($request->hasFile('photo_profile')) {
                $imagePath = Storage::disk('public')->put('user_photos', $request->file('photo_profile'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan foto profile.', [], 500);
                }
                $imagePath = 'storage/' . $imagePath;
                $dataToUpdate['photo_profile'] = $imagePath;

            }
            $user->update($dataToUpdate);
            DB::commit();
            return $this->sendResponse('Pengguna umum berhasil diupdate.', $user);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat memperbarui pengguna.', [$e->getMessage()], 500);
        }
    }

    /**
     * Menghapus user Umum berdasarkan ID User
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyUserUmum($id){   
        $user = User::where('id', $id)->where('role', 'U')->first();
        if (!$user) {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        try {
            $user->delete();
            return $this->sendResponse('Pengguna umum berhasil dihapus.');
        }catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menghapus pengguna.', [$e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan daftar user Mahasiswa
     * 
     * @return \Illuminate\Http\Response
     */
    public function listUserMahasiswa()
    {
        $users = User::where('role', 'M')
            ->with(['mahasiswa:id,user_id,universitas,jurusan']) 
            ->select('id', 'name', 'phone_number', 'photo_profile')
            ->paginate(10);
        return $this->sendResponse('List untuk pengguna mahasiswa berhasil diambil.', $users);
    }

    /**
     * Menampilkan detail user Mahasiswa berdasarkan ID User
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function detailUserMahasiswa($id)
    {
        $user = User::where('id', $id)->where('role', 'M')
            ->with(['mahasiswa:id,user_id,universitas,jurusan']) 
            ->select('id', 'email', 'name', 'phone_number', 'photo_profile', 'date_birth', 'gender')
            ->first();
        return $this->sendResponse('Detail untuk pengguna mahasiswa berhasil diambil.', $user);
    }

    /**
     * Store a newly created Mahasiswa user in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     */
    public function storeUserMahasiswa(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'photo_profile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'date_birth' => 'required|date',
            'gender' => 'required|string',
            'universitas' => 'required|string',
            'jurusan' => 'required|string',
        ],[
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email salah.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone_number.regex' => 'Format nomor telepon salah.',
            'date_birth.required' => 'Tanggal lahir wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib diisi.',
            'photo_profile.image' => 'Format gambar tidak sesuai.',
            'photo_profile.mimes' => 'Format gambar harus JPEG, PNG, atau JPG.',
            'photo_profile.max' => 'Ukuran gambar maksimal 2MB.',
            'universitas.required' => 'Universitas wajib diisi.',
            'jurusan.required' => 'Jurusan wajib diisi.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal.', $validatedData->errors(), 400);
        }   

        try {
            DB::beginTransaction();

            if ($request->hasFile('photo_profile')) {
                $imagePath = Storage::disk('public')->put('user_photos', $request->file('photo_profile'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan foto profile.', [], 500);
                }
                $imagePath = 'storage/' . $imagePath;
            }
            $randomPassword = Str::random(8);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($randomPassword),
                'phone_number' => $request->phone_number,
                'date_birth' => $request->date_birth,
                'gender' => $request->gender,
                'photo_profile' => $imagePath,
                'role' => 'M',
            ]);

            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'universitas' => $request->universitas,
                'jurusan' => $request->jurusan,
            ]);
            DB::commit();

            //Kirim kredensial pengguna melalui Pesan WhatsApp
            $target = $user->phone_number;
            $message = "Selamat, pendaftaran akun Anda berhasil!\n\n" .
                    "Berikut adalah informasi login Anda:\n" .
                    "Email: " . $user->email . "\n" .
                    "Password: " . $randomPassword . "\n\n" .
                    "Harap jaga kerahasiaan informasi ini. Selamat menggunakan layanan kami!";
            $this->notificationService->sendWhatsAppMessage($target, $message);

            return $this->sendResponse('Pengguna mahasiswa berhasil ditambahkan.', $user);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat menambahkan pengguna mahasiswa.', [$e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui data pengguna Mahasiswa berdasarkan ID User
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUserMahasiswa(Request $request, $id){
        $user = User::find($id);
        if (!$user || $user->role !== 'M') {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'phone_number' => 'string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'date',
            'gender' => 'string',
            'photo_profile' => 'image|mimes:jpeg,png,jpg|max:2048',
            'universitas' => 'string',
            'jurusan' => 'string',
        ],[
            'name.string' => 'Nama harus berupa teks.',
            'email.email' => 'Format email salah.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone_number.regex' => 'Format nomor telepon salah.',
            'photo_profile.image' => 'Format gambar tidak sesuai.',
            'photo_profile.mimes' => 'Format gambar harus JPEG, PNG, atau JPG.',
            'photo_profile.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors(), 422);
        }
        
        try {
            DB::beginTransaction();
            $dataToUpdate = $validator->validated();
            if ($request->hasFile('photo_profile')) {
                $imagePath = Storage::disk('public')->put('user_photos', $request->file('photo_profile'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan foto profile.', [], 500);
                }
                $imagePath = 'storage/' . $imagePath;
                $dataToUpdate['photo_profile'] = $imagePath;
            }
            $user->update($dataToUpdate);

            //update data mahasiswa
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            $mahasiswa->update([
                'universitas' => $request->universitas,
                'jurusan' => $request->jurusan,
            ]);

            DB::commit();
            return $this->sendResponse('Pengguna mahasiswa berhasil diupdate.', $user);
        }catch (\Exception $e) {   
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat memperbarui pengguna.', [$e->getMessage()], 500);
        }
    }

    /**
     * Menghapus user Mahasiswa berdasarkan ID User
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyUserMahasiswa($id){   
        $user = User::where('id', $id)->where('role', 'M')->first();
        if (!$user) {
            return $this->sendError('Mahasiswa tidak ditemukan', [], 404);
        }
        try {
            $user->delete();
            return $this->sendResponse('Mahasiswa berhasil dihapus.');
        }catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menghapus mahasiswa.', [$e->getMessage()], 500);
        }
    }

    public function listUserPsikolog()
    {
        $users = User::where('role', 'P')->with('psikolog')->paginate(10);
        return $this->sendResponse('List untuk pengguna psikolog berhasil diambil.', $users);
    }
}
