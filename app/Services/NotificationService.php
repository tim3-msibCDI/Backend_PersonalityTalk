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
        $token = config('app.fonnte_token');

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
        ]);
    
        return $response->body();
    }
}
