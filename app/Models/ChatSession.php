<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $table = 'chat_sessions';

    protected $fillable = [
        'user_id', 
        'psi_id', 
        'consultation_id', 
        'start_time', 
        'end_time'
    ];

    public function messages() {
        return $this->hasMany(Message::class, 'chat_session_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function psikolog() {
        return $this->belongsTo(User::class, 'psi_id', 'id');
    }
}

