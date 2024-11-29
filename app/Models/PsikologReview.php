<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsikologReview extends Model
{
    use HasFactory;

    protected $table = 'psikolog_reviews';

    protected $fillable = [
        'user_id',
        'psi_id',
        'consul_id',
        'rating',
        'review',
    ];

    public function psikolog()
    {
        return $this->belongsTo(Psikolog::class, 'psi_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(Topic::class, 'user_id', 'id');
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consul_id', 'id');
    }
    
}
