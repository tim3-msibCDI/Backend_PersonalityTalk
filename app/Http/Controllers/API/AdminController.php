<?php

namespace App\Http\Controllers\API;

use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Http\Controllers\API\BaseController;

class AdminController extends BaseController
{   
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     *  Approve a psychologist registration request.
     */
    public function approvePsikolog($id)
    {
        $psikolog = Psikolog::with('user')->find($id);
        if (!$psikolog) {
            return response()->json(['message' => 'Psikolog tidak ditemukan'], 404);
        }

        $psikolog->status = 'approved'; //Ubah status menjadi approved
        $psikolog->is_active = true;
        $psikolog->save();

        // Create message and sending notification to phone number
        $target = $psikolog->user->phone_number;
        $message = 'Selamat! Pendaftaran Anda sebagai mitra psikolog telah disetujui.';
        $this->notificationService->sendWhatsAppMessage($target, $message);

        return response()->json(['message' => 'Pendaftaran psikolog disetujui']);
    }

    /**
     *  Reject a psychologist registration request.
     */
    public function rejectPsikolog($id)
    {
        $psikolog = Psikolog::with('user')->find($id);
        // dd($psikolog);
        if (!$psikolog) {
            return response()->json(['message' => 'Psikolog tidak ditemukan'], 404);
        }

        // Jika psikolog sudah disetujui, tidak boleh diubah statusnya ke rejected
        if ($psikolog->status === 'approved') {
            return response()->json(['message' => 'Psikolog yang sudah disetujui tidak dapat ditolak'], 403);
        }

        $psikolog->status = 'rejected'; // Ubah status menjadi rejected
        $psikolog->is_active = false;
        $psikolog->save();

         // Create message and sending notification to phone number
         $target = $psikolog->user->phone_number;
         $message = 'Pendaftaran ditolak'; 
         $this->notificationService->sendWhatsAppMessage($target, $message);

        return response()->json(['message' => 'Pendaftaran psikolog ditolak']);
    }
}
