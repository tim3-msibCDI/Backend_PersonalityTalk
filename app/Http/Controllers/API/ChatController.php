<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Message;
use App\Events\MessageSent;
use App\Models\Consultation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController;

class ChatController extends BaseController
{
    /**
     * Mendapatkan daftar pesan berdasarkan ID chat session
     * 
     * @param int $chatSessionId ID chat session
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages($chatSessionId)
    {
        $userId = Auth::user()->id;

        $messages = Message::where('chat_session_id', $chatSessionId)
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->sendResponse('Pesan berhasil diambil.', $messages);
    }

    /**
     * Send a message to another user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function sendMessage(Request $request)
    {
        $userId = Auth::user()->id;

        $validated = $request->validate([
            'chat_session_id' => 'required|exists:chat_sessions,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $message = new Message();
        $message->chat_session_id = $validated['chat_session_id'];
        $message->sender_id = $userId;
        $message->receiver_id = $validated['receiver_id'];
        $message->message = $validated['message'];
        $message->save();

        // Broadcast pesan untuk real-time
        // broadcast(new MessageSent($message))->toOthers();

        return $this->sendResponse('Pesan berhasil dikirim.', $message);
    }

    public function getPsikologInfo(Request $request)
    {
        $validated = $request->validate([
            'consul_id' => 'required|exists:consultations,id',
        ]);

        $consultation = Consultation::with([
            'psikolog.user', 
            'psikologSchedule.mainSchedule', 
            ])
            ->whereIn('consul_status', ['completed', 'ongoing'])
            ->where('id', $validated['consul_id'])
            ->first();

        if (!$consultation || !$consultation->psikolog || !$consultation->psikologSchedule) {
            return $this->sendError('Data konsultasi, psikolog, atau jadwal tidak ditemukan.', [], 404);
        }
        $mainSchedule = $consultation->psikologSchedule->mainSchedule;

        $response = [
            'psikolog' => [
                'name' => $consultation->psikolog->user->name,
                'photo_profile' => $consultation->psikolog->user->photo_profile ?? null,
            ],
            'time' => [
                'start_time' => Carbon::parse($mainSchedule->start_hour)->format('H:i'),
                'end_time' => Carbon::parse($mainSchedule->end_hour)->format('H:i'), 
                'date' => $consultation->psikologSchedule->date, 
            ],
            'notes' => $consultation->psikolog_note ?? null, 
        ];

        return $this->sendResponse('Informasi konsultasi berhasil diambil.', $response);
    }


}
