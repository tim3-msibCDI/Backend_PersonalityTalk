<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PsikologTopic;
use App\Models\Psikolog;
use App\Models\Mahasiswa;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PsikologPrice;
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

    /**
     * ManageUserController constructor.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Mengirim pesan WhatsApp untuk kredensial pengguna
     *
     * @param \App\Models\User $user
     * @param string $randomPassword
     * @return void
     */
    private function sendWhatsAppCredentials(User $user, string $randomPassword)
    {
        $target = $user->phone_number;
        $message = "Selamat, pendaftaran akun Anda berhasil!\n\n" .
                "Berikut adalah informasi login Anda:\n" .
                "Email: " . $user->email . "\n" .
                "Password: " . $randomPassword . "\n\n" .
                "Harap jaga kerahasiaan informasi ini. Selamat menggunakan layanan kami!";

        $this->notificationService->sendWhatsAppMessage($target, $message);
    }

    /**
     * Menampilkan daftar user Umum
     *
     * @return \Illuminate\Http\Response
     */
    public function listUserUmum(){
        $users = User::where('role', 'U')
            ->select('id', 'name', 'phone_number', 'date_birth', 'gender', 'photo_profile')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return $this->sendResponse('List untuk pengguna umum berhasil diambil.', $users);
    }

    
    /**
     * Mencari user Umum berdasarkan nama, nomor telepon, bulan lahir, atau gender
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchUserUmum(Request $request)
    {
        // Validasi input
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = $request->search;

        // Mapping untuk gender
        $genderMapping = [
            'perempuan' => 'F',
            'laki-laki' => 'M',
        ];

        // Mapping untuk bulan dalam bahasa Indonesia ke angka
        $monthMapping = [
            'januari' => '01', 'februari' => '02', 'maret' => '03',
            'april' => '04', 'mei' => '05', 'juni' => '06',
            'juli' => '07', 'agustus' => '08', 'september' => '09',
            'oktober' => '10', 'november' => '11', 'desember' => '12',
        ];

        $searchGender = null;
        $searchMonth = null;

        // Cek pencarian gender
        foreach ($genderMapping as $key => $value) {
            if (stripos($key, strtolower($search)) !== false) {
                $searchGender = $value;
                break;
            }
        }

        // Cek pencarian bulan
        foreach ($monthMapping as $key => $value) {
            if (stripos($key, strtolower($search)) !== false) {
                $searchMonth = $value;
                break;
            }
        }

        // Query pencarian
        $users = User::where('role', 'U')
            ->select('id', 'name', 'phone_number', 'date_birth', 'gender', 'photo_profile')
            ->when($search, function ($query) use ($search, $searchGender, $searchMonth) {
                $query->where(function ($subQuery) use ($search, $searchGender, $searchMonth) {
                    if ($searchGender) {
                        // Filter berdasarkan gender
                        $subQuery->orWhere('gender', $searchGender);
                    }
                    if ($searchMonth) {
                        // Filter berdasarkan bulan lahir
                        $subQuery->orWhere(DB::raw("DATE_FORMAT(date_birth, '%m')"), $searchMonth);
                    }
                    // Filter berdasarkan nama atau nomor telepon
                    $subQuery->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
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

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($randomPassword);
            $user->phone_number = $request->phone_number;
            $user->date_birth = $request->date_birth;
            $user->gender = $request->gender;
            $user->role = 'U';
            $user->photo_profile = $imagePath ?? null;
            $user->save();

            DB::commit();

            //kirim kredensial ke wa
            $this->sendWhatsAppCredentials($user, $randomPassword);
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'photo_profile' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
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
                // Hapus foto lama jika ada
                if ($user->photo_profile) {
                    $oldPhotoPath = str_replace('storage/', '', $user->photo_profile);
                    Storage::disk('public')->delete($oldPhotoPath);
                }
            
                // Simpan foto baru
                $imagePath = Storage::disk('public')->put('user_photos', $request->file('photo_profile'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan foto profile.', [], 500);
                }
            
                // Update path foto profile
                $user->photo_profile = 'storage/' . $imagePath;
            }

            $user->name = $dataToUpdate['name'] ?? $user->name;
            $user->email = $dataToUpdate['email'] ?? $user->email;
            $user->phone_number = $dataToUpdate['phone_number'] ?? $user->phone_number;
            $user->date_birth = $dataToUpdate['date_birth'] ?? $user->date_birth;
            $user->gender = $dataToUpdate['gender'] ?? $user->gender;
            $user->save();

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
    public function destroyUserUmum($id)
    {
        // Cari user dengan role 'U' (Umum)
        $user = User::where('id', $id)->where('role', 'U')->first();
        if (!$user) {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        try {
            DB::beginTransaction();

            // Hapus file photo_profile jika ada
            if ($user->photo_profile) {
                $path = str_replace('storage/', '', $user->photo_profile); 
                Storage::disk('public')->delete($path);
            }

            $user->delete();
            DB::commit();
            return $this->sendResponse('Pengguna umum berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
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
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return $this->sendResponse('List untuk pengguna mahasiswa berhasil diambil.', $users);
    }

    /**
     * Mencari user Mahasiswa berdasarkan nama, nomor telepon, jurusan, atau universitas
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchUserMahasiswa(Request $request)
    {
        // Validasi input
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = $request->search;

        // Query pencarian
        $users = User::where('role', 'M')
            ->with(['mahasiswa:id,user_id,universitas,jurusan']) // Relasi dengan tabel mahasiswa
            ->select('id', 'name', 'phone_number', 'photo_profile')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->orWhere('name', 'like', '%' . $search . '%') // Filter berdasarkan nama
                        ->orWhere('phone_number', 'like', '%' . $search . '%') // Filter berdasarkan nomor telepon
                        ->orWhereHas('mahasiswa', function ($mahasiswaQuery) use ($search) {
                            $mahasiswaQuery->where('universitas', 'like', '%' . $search . '%') // Filter berdasarkan universitas
                                ->orWhere('jurusan', 'like', '%' . $search . '%'); // Filter berdasarkan jurusan
                        });
                });
            })
            ->orderBy('created_at', 'desc')
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

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($randomPassword);
            $user->phone_number = $request->phone_number;
            $user->date_birth = $request->date_birth;
            $user->gender = $request->gender;
            $user->photo_profile = $imagePath;
            $user->role = 'M';
            $user->save();

            $mahasiswa = new Mahasiswa();
            $mahasiswa->user_id = $user->id;
            $mahasiswa->universitas = $request->universitas;
            $mahasiswa->jurusan = $request->jurusan;
            $mahasiswa->save();

            DB::commit();

            //Kirim kredensial pengguna melalui Pesan WhatsApp
            $this->sendWhatsAppCredentials($user, $randomPassword);
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
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
                // Hapus foto lama jika ada
                if ($user->photo_profile) {
                    $oldPhotoPath = str_replace('storage/', '', $user->photo_profile);
                    Storage::disk('public')->delete($oldPhotoPath);
                }
            
                // Simpan foto baru
                $imagePath = Storage::disk('public')->put('user_photos', $request->file('photo_profile'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan foto profile.', [], 500);
                }
            
                // Update path foto profile
                $user->photo_profile = 'storage/' . $imagePath;
            }

            //update data user
            $user->name = $dataToUpdate['name'] ?? $user->name;
            $user->email = $dataToUpdate['email'] ?? $user->email;
            $user->phone_number = $dataToUpdate['phone_number'] ?? $user->phone_number;
            $user->date_birth = $dataToUpdate['date_birth'] ?? $user->date_birth;
            $user->gender = $dataToUpdate['gender'] ?? $user->gender;
            $user->save();

            //update data mahasiswa
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            $mahasiswa->universitas = $request->universitas ?? $mahasiswa->universitas;
            $mahasiswa->jurusan = $request->jurusan ?? $mahasiswa->jurusan;
            $mahasiswa->save();

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
    public function destroyUserMahasiswa($id)
    {
        // Cari user dengan role 'M' (Mahasiswa) dan relasinya
        $user = User::with('mahasiswa')->where('id', $id)->where('role', 'M')->first();
        if (!$user) {
            return $this->sendError('Mahasiswa tidak ditemukan', [], 404);
        }

        try {
            DB::beginTransaction();

            // Hapus relasi mahasiswa jika ada
            if ($user->mahasiswa) {
                $user->mahasiswa->delete();
            }

            // Hapus file photo_profile jika ada
            if ($user->photo_profile) {
                $path = str_replace('storage/', '', $user->photo_profile); // Hilangkan prefix storage/
                Storage::disk('public')->delete($path);
            }
            $user->delete();

            DB::commit();
            return $this->sendResponse('Mahasiswa berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat menghapus mahasiswa.', [$e->getMessage()], 500);
        }
    }
    
    /**
     * Menampilkan daftar pengguna berdasarkan kategori Psikolog/Konselor
     *
     * @param string $category
     * @return \Illuminate\Http\Response
     */
    private function listUserByCategory(string $category)
    {
        // Ambil data user dengan role 'P' dan muat relasi
        $users = User::where('role', 'P')
            ->whereHas('psikolog.psikolog_category', function ($query) use ($category) {
                $query->where('category_name', $category);
            })
            ->whereHas('psikolog', function ($query) {
                $query->where('is_active', true);
            })
            ->with([
                'psikolog.psikolog_topic.topic', 
                'psikolog.psikolog_category'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Format data dengan map
        $formattedUsers = $users->getCollection()->map(function ($user) {
            $psikologDetails = $user->psikolog;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'sipp' => $psikologDetails->sipp,
                'practice_start_date' => Carbon::parse($psikologDetails->practice_start_date)->translatedFormat('d F Y'),
                'topics' => $psikologDetails->psikolog_topic->pluck('topic.topic_name')->toArray(),
            ];
        });

        // Gantikan koleksi asli dengan koleksi yang diformat
        $users->setCollection($formattedUsers);

        return $this->sendResponse("List untuk pengguna $category berhasil diambil.", $users);
    }

    /**
     * Menampilkan daftar Psikolog
     * 
     * @return \Illuminate\Http\Response
     */
    public function listUserPsikolog()
    {
        return $this->listUserByCategory('Psikolog');
    }

    /**
     * Menampilkan daftar Konselor
     * 
     * @return \Illuminate\Http\Response
     */
    public function listUserKonselor()
    {
        return $this->listUserByCategory('Konselor');
    }

    /**
     * Mencari Psikolog berdasarkan nama, sipp, bulan mulai praktik, atau topik
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchUserPsikolog(Request $request)
    {
        // Validasi input
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = $request->search;

        // Mapping untuk bulan dalam bahasa Indonesia ke angka
        $monthMapping = [
            'januari' => '01', 'februari' => '02', 'maret' => '03',
            'april' => '04', 'mei' => '05', 'juni' => '06',
            'juli' => '07', 'agustus' => '08', 'september' => '09',
            'oktober' => '10', 'november' => '11', 'desember' => '12',
        ];

        $searchMonth = null;

        // Cek apakah pencarian sesuai dengan nama bulan
        foreach ($monthMapping as $key => $value) {
            if (stripos($key, strtolower($search)) !== false) {
                $searchMonth = $value;
                break;
            }
        }

        // Query pencarian
        $users = User::where('role', 'P')
            ->whereHas('psikolog', function ($query) use ($searchMonth) {
                // Filter untuk psikolog aktif
                $query->where('is_active', true);

                if ($searchMonth) {
                    // Filter berdasarkan bulan mulai praktik
                    $query->where(DB::raw("DATE_FORMAT(practice_start_date, '%m')"), $searchMonth);
                }
            })
            ->when($search, function ($query) use ($search, $searchMonth) {
                $query->where(function ($subQuery) use ($search, $searchMonth) {
                    $subQuery->orWhere('name', 'like', '%' . $search . '%') // Filter berdasarkan nama
                        ->orWhereHas('psikolog', function ($psikologQuery) use ($search) {
                            $psikologQuery->where('sipp', 'like', '%' . $search . '%') // Filter berdasarkan SIPP
                                ->orWhereHas('psikolog_topic.topic', function ($topicQuery) use ($search) {
                                    $topicQuery->where('topic_name', 'like', '%' . $search . '%'); // Filter berdasarkan topik
                                });
                        });
                });
            })
            ->with(['psikolog.psikolog_topic.topic']) // Relasi dengan topik psikolog
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Format data dengan map
        $formattedUsers = $users->getCollection()->map(function ($user) {
            $psikologDetails = $user->psikolog;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'sipp' => $psikologDetails->sipp,
                'practice_start_date' => Carbon::parse($psikologDetails->practice_start_date)->translatedFormat('d F Y'),
                'topics' => $psikologDetails->psikolog_topic->pluck('topic.topic_name')->toArray(),
            ];
        });

        // Gantikan koleksi asli dengan koleksi yang diformat
        $users->setCollection($formattedUsers);

        return $this->sendResponse('List untuk pengguna Psikolog berhasil diambil.', $users);
    }


    /**
     * Menampilkan detail user berdasarkan ID User dan kategori Psikolog/Konselor
     * 
     * @param int $id
     * @param string $categoryName
     * @return \Illuminate\Http\Response
     */
    private function detailUserByCategory($id, $categoryName)
    {
        // Ambil user dengan role Psikolog ('P') dan muat relasi
        $user = User::where('id', $id)
            ->where('role', 'P')
            ->with(['psikolog.psikolog_topic.topic', 'psikolog.psikolog_category', 'psikolog.bank'])
            ->whereHas('psikolog', function ($query) {
                $query->where('is_active', true); // Hanya ambil psikolog yang aktif
            })
            ->whereHas('psikolog.psikolog_category', function ($query) use ($categoryName) {
                $query->where('category_name', $categoryName); 
            })
            ->select('id', 'email', 'name', 'phone_number', 'photo_profile', 'date_birth', 'gender')
            ->first();

        // Periksa apakah data ditemukan
        if (!$user) {
            return $this->sendError("Pengguna dengan kategori {$categoryName} tidak ditemukan", [], 404);
        }

        // Format detail user
        $psikologDetails = $user->psikolog;
        // dd($psikologDetails);
        $formattedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'photo_profile' => $user->photo_profile,
            'date_birth' => $user->date_birth,
            'gender' => $user->gender,
            'sipp' => $psikologDetails->sipp ?? null,
            'practice_start_date' => Carbon::parse($psikologDetails->practice_start_date)->translatedFormat('Y-m-d'), 
            'bank_id' => $psikologDetails->bank->id ?? null,
            'bank_name' => $psikologDetails->bank->name ?? null,
            'rekening' => $psikologDetails->account_number ?? null,
            'description' => $psikologDetails->description,
            'selected_topics' => $psikologDetails->psikolog_topic->map(function ($topicRelation) {
                return [
                    'id' => $topicRelation->topic->id,
                    'topic_name' => $topicRelation->topic->topic_name,
                ];
            })->toArray(),
        ];

        return $this->sendResponse("Detail pengguna dengan kategori {$categoryName} berhasil diambil.", $formattedUser);
    }

    /**
     * Mendapatkan detail pengguna dengan kategori Psikolog berdasarkan ID.
     * 
     * @param int $id ID pengguna
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailUserPsikolog($id)
    {
        return $this->detailUserByCategory($id, 'Psikolog');
    }

    /**
     * Mendapatkan detail pengguna dengan kategori Konselor berdasarkan ID.
     * 
     * @param int $id ID pengguna
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailUserKonselor($id)
    {
        return $this->detailUserByCategory($id, 'Konselor');
    }

    /**
     * Update user Psikolog/Konselor berdasarkan ID User
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateUserPsikolog(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user || $user->role !== 'P') {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        // Pastikan Psikolog aktif
        if (!$user->psikolog->is_active) {
            return $this->sendError('Akun Psikolog tidak aktif', [], 403);
        }

        // Pastikan relasi dengan tabel Psikolog
        $psikolog = $user->psikolog; // Gunakan relasi "psikolog" pada model User
        if (!$psikolog) {
            return $this->sendError('Data Psikolog tidak ditemukan', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
            'phone_number' => 'sometimes|string|max:20',
            'photo_profile' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'date_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:F,M',
            'sipp' => 'nullable|string',
            'practice_start_date' => 'nullable|date',
            'updated_topics' => 'nullable|array', 
            'bank_id' => 'nullable|exists:payment_methods,id',
            'rekening' => 'nullable|string',
        ], [
            'updated_topics.*' => 'ID topik tidak valid',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain',
            'photo_profile.image' => 'Format gambar tidak sesuai.',
            'photo_profile.mimes' => 'Format gambar harus JPEG, PNG, atau JPG.',
            'photo_profile.max' => 'Ukuran gambar tidak boleh lebih dari 2 MB.',
            'date_birth.date' => 'Format tanggal lahir tidak valid.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'sipp.string' => 'SIPP harus berupa teks.',
            'practice_start_date.date' => 'Format tanggal mulai praktik tidak valid.',
            'updated_topics.*.exists' => 'ID topik tidak valid.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors(), 422);
        }
        $validatedData = $validator->validated();

        try {
            DB::beginTransaction();

            //update foto profile
            if ($request->hasFile('photo_profile')) {
                $imagePath = Storage::disk('public')->put('psikolog_photos', $request->file('photo_profile'));

                if (!$imagePath) {
                    return $this->sendError('Gagal memperbarui foto profil.', [], 500);
                }
            
                // Hapus gambar lama jika ada
                if ($user->photo_profile) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $user->photo_profile));
                }
            
                $user->photo_profile = 'storage/' . $imagePath; 
            }
            
            // Update data user
            $user->name = $validatedData['name'] ?? $user->name;
            $user->email = $validatedData['email'] ?? $user->email;
            $user->phone_number = $validateData['phone_number'] ?? $user->phone_number;
            $user->date_birth = $validatedData['date_birth'] ?? $user->date_birth;
            $user->gender = $validatedData['gender'] ?? $user->gender;
            $user->save();

            // Update data psikolog
            if ($psikolog) {
                //Perbarui data psikolog
                $psikolog->practice_start_date = $validatedData['practice_start_date'] ?? $psikolog->practice_start_date;
                $psikolog->bank_id = $validatedData['bank_id'] ?? $psikolog->bank_id;
                $psikolog->account_number = $validatedData['rekening'] ?? $psikolog->account_number;
                $psikolog->save();

                // Perbarui SIPP dan PsikologPrice
                if (isset($validatedData['sipp'])) {
                    $sipp = $validatedData['sipp'];
                    $sippParts = explode('-', $sipp);
                    $sippCode = $sippParts[2] ?? null;

                    if (!$sippCode) {
                        return $this->sendError('Format SIPP tidak valid.', [], 400);
                    }

                    // Cari PsikologPrice berdasarkan kode SIPP
                    $psikologPrice = PsikologPrice::where('code', $sippCode)->first();
                    $psikologPriceId = $psikologPrice->id ?? 1; 

                    // Perbarui SIPP dan PsikologPrice di tabel psikolog
                    $psikolog->sipp = $sipp;
                    $psikolog->psikolog_price_id = $psikologPriceId;

                    // Perbarui psikolog_category menjadi '1 = Psikolog'
                    $psikolog->category_id = 1;
                    $psikolog->save();
                }

                // Cek apakah ada data updated_topics pada request
                if ($request->has('updated_topics')) {
                    $existingTopicIds = $user->psikolog->psikolog_topic->pluck('topic_id')->toArray();
                    $newTopicIds = $request->updated_topics;

                    // Jika topik berubah, lakukan pembaruan
                    if (array_diff($existingTopicIds, $newTopicIds) || array_diff($newTopicIds, $existingTopicIds)) {
                        // Hapus topik lama
                        $user->psikolog->psikolog_topic()->delete();

                        // Tambahkan topik baru
                        $newTopics = collect($newTopicIds)->map(function ($topicId) {
                            return ['topic_id' => $topicId];
                        });
                        
                        foreach ($newTopics as $topicData) {
                            $topic = new PsikologTopic(); 
                            $topic->topic_id = $topicData['topic_id'];
                            $topic->psikolog_id = $user->psikolog->id; 
                            $topic->save();
                        }
                    }
                    $user->load('psikolog.psikolog_topic');
                }
            }

            DB::commit();
            return $this->sendResponse('Berhasil memperbarui psikolog.', $user);
        }catch (\Exception $e) {   
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat memperbarui psikolog.', [$e->getMessage()], 500);
        }
    }

    public function destroyUserPsikolog($id)
    {
        // Cari user dengan role 'P' (Psikolog) dan muat relasinya
        $user = User::with('psikolog.psikolog_topic')->where('id', $id)->where('role', 'P')->first();

        if (!$user) {
            return $this->sendError('Psikolog tidak ditemukan', [], 404);
        }

        try {
            DB::beginTransaction();

            // Cek apakah ada relasi yang masih terhubung
            if ($user->psikolog) {
                $psikolog = $user->psikolog;

                // Jika masih ada relasi (contoh: Consultation), ubah is_active menjadi false
                $hasRelations = $psikolog->consultation()->exists() || $psikolog->rating()->exists();

                if ($hasRelations) {
                    $psikolog->update(['is_active' => false]);
                    DB::commit();
                    return $this->sendResponse('Psikolog tidak dapat dihapus. Status diubah menjadi tidak aktif.');
                }

                // Hapus data terkait jika tidak ada relasi
                $psikolog->psikolog_topic()->delete();
                $psikolog->delete();
            }

            // Hapus file photo_profile di User jika ada
            if ($user->photo_profile) {
                $path = str_replace('storage/', '', $user->photo_profile); 
                Storage::disk('public')->delete($path);
            }

            $user->delete();

            DB::commit();
            return $this->sendResponse('Psikolog berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Gagal memproses penghapusan psikolog.', [$e->getMessage()], 500);
        }
    }

}
