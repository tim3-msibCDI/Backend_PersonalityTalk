<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $table = 'consultations';

    protected $fillable = [
        'user_id',
        'psi_id',
        'psch_id',
        'topic_id',
        'consul_status',
        'patient_complaint',
        'psikolog_note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function psikolog()
    {
        return $this->belongsTo(Psikolog::class, 'psi_id', 'id');
    }

    public function psikologSchedule()
    {
        return $this->belongsTo(PsikologSchedule::class, 'psch_id', 'id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'id');
    }

    public function consulTransaction()
    {
        return $this->hasMany(ConsultationTransaction::class, 'consultation_id');
    }

    public function review()
    {
        return $this->hasOne(PsikologReview::class, 'consul_id');
    }

    public function chatSession()
    {
        return $this->HasOne(ChatSession::class, 'consultation_id');
    }

}
 