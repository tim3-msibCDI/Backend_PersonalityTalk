<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Psikolog;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Http\Controllers\API\BaseController;

class ManagePsikologController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Approve Psikolog Registration
     *
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse  
     *      
     */
    public function approvePsikolog($id)
    {
        $psikolog = Psikolog::with('user')->find($id);
        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', [], 404);
        }

        $psikolog->status = 'approved'; //Ubah status menjadi approved
        $psikolog->is_active = true;
        $psikolog->save();

        // Create message and sending notification to phone number
        $target = $psikolog->user->phone_number;
        $message = 'Selamat! Pendaftaran Anda sebagai mitra psikolog telah disetujui.';
        $this->notificationService->sendWhatsAppMessage($target, $message);

        return $this->sendResponse('Pendaftaran psikolog disetujui.', null);        
    }

    /**
     * Reject Psikolog Registration
     *
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse
     *        
     */
    public function rejectPsikolog($id)
    {
        $psikolog = Psikolog::with('user')->find($id);
        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', [], 404);
        }

        // Jika psikolog sudah disetujui, tidak boleh diubah statusnya ke rejected
        if ($psikolog->status === 'approved') {
            return $this->sendError('Psikolog yang sudah disetujui tidak dapat ditolak.', [], 403);
        }

        $psikolog->status = 'rejected'; // Ubah status menjadi rejected
        $psikolog->is_active = false;
        $psikolog->save();

         // Create message and sending notification to phone number
         $target = $psikolog->user->phone_number;
         $message = 'Pendaftaran ditolak'; 
         $this->notificationService->sendWhatsAppMessage($target, $message);

        return $this->sendResponse('Pendaftaran psikolog ditolak.', null);
    }

    /**
     * Menampilkan daftar semua psikolog yang mendaftar
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPsikologRegistrant()
    {
        $psikologs = User::with('psikolog:id,user_id,sipp,status')
            ->select('id', 'name', 'photo_profile') 
            ->where('role', 'P')
            ->orderBy('created_at', 'desc') 
            ->get();

        $data = [];
        foreach ($psikologs as $psikolog) {
            $data[] = [
                'id' => $psikolog->id,
                'name' => $psikolog->name,
                'photo_profile' => $psikolog->photo_profile,
                'sipp' => $psikolog->psikolog->sipp ?? null, 
                'status' => $psikolog->psikolog->status ?? null,
            ];
        }

        return $this->sendResponse('List psikolog yang mendaftar berhasil diambil', $data);
    }

    /**
     * Menampilkan detail psikolog berdasarkan ID
     * 
     * @param int $id ID psikolog
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailPsikolog($id)
    {
        // Ambil user dengan role Psikolog ('P') dan muat relasi
        $user = User::where('id', $id)
            ->where('role', 'P')
            ->with(['psikolog.psikolog_topic.topic', 'psikolog.psikolog_category'])
            ->whereHas('psikolog', function ($query) {
                $query->where('is_active', true); // Hanya ambil psikolog yang aktif
            })
            ->select('id', 'email', 'name', 'phone_number', 'photo_profile', 'date_birth', 'gender')
            ->first();

        // Periksa apakah data ditemukan
        if (!$user) {
            return $this->sendError("Pengguna dengan kategori {$categoryName} tidak ditemukan", [], 404);
        }

        // Format detail user
        $psikologDetails = $user->psikolog;
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
            'description' => $psikologDetails->description,
            'selected_topics' => $psikologDetails->psikolog_topic->map(function ($topicRelation) {
                return $topicRelation->topic->topic_name;
            })->toArray(),
        ];

        return $this->sendResponse("Detail psikolog yang mendaftar berhasil diambil.", $formattedUser);  
    }

}
