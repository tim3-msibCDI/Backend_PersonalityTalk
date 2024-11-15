<?php

namespace App\Http\Controllers\API;

use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Http\Controllers\API\BaseController;

class ManagePsikologController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationSesrvice;
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
}
