<?php

namespace App\Http\Controllers\API;

use App\Models\Message;
use App\Events\MessageSent;
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
        $validated = $request->validate([
            'chat_session_id' => 'required|exists:chat_sessions,id',
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'chat_session_id' => $validated['chat_session_id'],
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        // Broadcast pesan untuk real-time
        // broadcast(new MessageSent($message))->toOthers();

        return $this->sendResponse('Pesan berhasil dikirim.', $message);
    }

    public function getUserInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('Pengguna tidak ditemukan', [], 404);
        }

        $response = [
            'name' => $user->name,
            'photo_profile' => $user->photo_profile ?? null, 
            'role' => $user->role,
        ];  

        return $this->sendResponse('Informasi pengguna berhasil diambil.', $response);
    }

}
