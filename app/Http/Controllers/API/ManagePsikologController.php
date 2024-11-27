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
    public function approvePsikolog($id_psikolog)
    {
        $psikolog = Psikolog::with('user')->find($id_psikolog);
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
    public function rejectPsikolog($id_psikolog)
    {
        $psikolog = Psikolog::with('user')->find($id_psikolog);
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
        // Ambil daftar psikolog berdasarkan tabel Psikolog
        $psikologs = Psikolog::with('user:id,name,photo_profile')
            ->select('id', 'user_id', 'sipp', 'status') // Pilih kolom yang relevan
            ->orderBy('created_at', 'desc')
            ->get();
    
        $data = [];
        foreach ($psikologs as $psikolog) {
            $data[] = [
                'id_psikolog' => $psikolog->id,
                'name' => $psikolog->user->name,
                'photo_profile' => $psikolog->user->photo_profile,
                'sipp' => $psikolog->sipp,
                'status' => $psikolog->status,
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
    public function detailPsikolog($id_psikolog)
    {
        // Cari psikolog berdasarkan id_psikolog dan muat relasi
        $psikolog = Psikolog::with(['user', 'psikolog_topic.topic', 'psikolog_category'])
            ->find($id_psikolog);

        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', [], 404);
        }

        // Format detail psikolog
        $formattedPsikolog = [
            'id_psikolog' => $psikolog->id,
            'name' => $psikolog->user->name,
            'email' => $psikolog->user->email,
            'phone_number' => $psikolog->user->phone_number,
            'photo_profile' => $psikolog->user->photo_profile,
            'date_birth' => $psikolog->user->date_birth,
            'gender' => $psikolog->user->gender,
            'sipp' => $psikolog->sipp,
            'practice_start_date' => Carbon::parse($psikolog->practice_start_date)->translatedFormat('Y-m-d'),
            'description' => $psikolog->description,
            'selected_topics' => $psikolog->psikolog_topic->map(function ($topicRelation) {
                return $topicRelation->topic->topic_name;
            })->toArray(),
        ];

        return $this->sendResponse("Detail psikolog yang mendaftar berhasil diambil.", $formattedPsikolog);
    }


}
