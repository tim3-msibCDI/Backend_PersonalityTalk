<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('chat.{chatSessionId}', function ($user, $chatSessionId) {
//     \Log::info("Broadcast auth check", ['user' => $user->id, 'chatSessionId' => $chatSessionId]);
//     return ChatSession::where('id', $chatSessionId)
//         ->where(function ($query) use ($user) {
//             $query->where('user_id', $user->id)
//                   ->orWhere('psi_id', $user->id);
//         })->exists();
// });


