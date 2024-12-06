<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Psikolog;
use App\Models\Consultation;
use Illuminate\Http\Request;
use App\Services\PsikologService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PsikologController extends BaseController
{   
    
    protected $psikologService;

    // PsikologService constructor
    public function __construct(PsikologService $psikologService)
    {
        parent::__construct();
        $this->psikologService = $psikologService;
    }

    /**
     * Psikolog Registration
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function psikologRegister(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            // Validasi untuk tabel 'users'
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F',
            'photo_profile' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'role' => 'required|in:P,K',

            // Validasi untuk tabel 'psikolog'
            'description' => 'required|string|max:255',
            'sipp' => 'required_if:role,P|string|max:255',
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
            'sipp.required_if' => 'Psikolog wajib mengisi SIPP.',
            'photo_profile.required' => 'Foto profil wajib diunggah.',
            'photo_profile.image' => 'Foto profil harus berupa gambar.',
            'topics.required' => 'Pilih setidaknya satu topik keahlian.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal.', $validator->errors(), 422);
        }

        try {
            // Menggunakan function yang terdapat pada PsikologService
            $psikolog = $this->psikologService->registerPsikolog($request->all());
            return $this->sendResponse('Pendaftaran psikolog berhasil dilakukan', $psikolog);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat registrasi psikolog.', [$e->getMessage()], 500);
        }
    }

    // List Chat Konsultasi Psikolog
    public function listChatConsultation(){
        $user = Auth::user();
        $psikolog = $user->psikolog;

        $consultation = Consultation::with([
            'psikologSchedule.mainSchedule', 
            'user', 
            'psikolog.user',
            'topic',
            'chatSession'
        ])
        ->where('psi_id', $psikolog->id)
        ->whereIn('consul_status', ['completed', 'ongoing', 'scheduled']) 
        ->get();

        $data = $consultation->map(function ($item) {
            return [
                'consul_id' => $item->id,
                'client_name' => $item->user->name ?? null,
                'topic' => $item->topic->topic_name ?? null,
                'date' => Carbon::parse($item->psikologSchedule->date)->format('j M Y') ?? null,
                'start_hour' => Carbon::parse($item->psikologSchedule->mainSchedule->start_hour)->format('H:i') ?? null,
                'end_hour' => Carbon::parse($item->psikologSchedule->mainSchedule->end_hour)->format('H:i') ?? null,
                'status' => $item->consul_status ?? null,
                'keluhan' => $item->patient_complaint ?? null,
                'chat_session_id' => $item->chatSession->id ?? null,
            ];
        });
        
        return $this->sendResponse('List chat consultation', $data);
    }

    public function detailComplaintUser($consulId)
    {
        $user = Auth::user();
        $psikolog = $user->psikolog;

        // Cari konsultasi berdasarkan ID dan psikolog yang terkait
        $consultation = Consultation::where('id', $consulId)
            ->where('psi_id', $psikolog->id)
            ->first(); 
        
        // Periksa jika konsultasi tidak ditemukan
        if (!$consultation) {
            return $this->sendError('Konsultasi tidak ditemukan', [], 404);
        }

        $complaint = $consultation->patient_complaint;
        return $this->sendResponse('Detail Keluhan berhasil diambil', ['keluhan' => $complaint]);
    }


}
