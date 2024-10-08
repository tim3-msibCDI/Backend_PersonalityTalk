<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Sending WA notification to user
     */
    public function sendWhatsAppMessage($target, $message)
    {
        $token = env('FONNTE_TOKEN');

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message
        ]);
    
        return $response->body();
    }
}
