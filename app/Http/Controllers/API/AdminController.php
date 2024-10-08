<?php

namespace App\Http\Controllers\API;

use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class AdminController extends BaseController
{
    public function approvePsikolog($id)
    {
        $psikolog = Psikolog::find($id);
        if (!$psikolog) {
            return response()->json(['message' => 'Psikolog tidak ditemukan'], 404);
        }

        $psikolog->status = 'approved'; //Ubah status menjadi approved
        $psikolog->is_active = true;
        $psikolog->save();

        return response()->json(['message' => 'Pendaftaran psikolog disetujui']);
    }

    public function rejectPsikolog($id)
    {
        $psikolog = Psikolog::find($id);
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

        return response()->json(['message' => 'Pendaftaran psikolog ditolak']);
    }
}
