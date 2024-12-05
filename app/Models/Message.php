<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{

    protected $table = 'messages';

    protected $fillable = [
        'chat_session_id', 
        'sender_id', 
        'receiver_id', 
        'message',
        'created_at',
        'updated_at'
    ];


    public function chatSession() {
        return $this->belongsTo(ChatSession::class, 'chat_session_id', 'id');
    }

    public function sender() {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function receiver() {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }
}

