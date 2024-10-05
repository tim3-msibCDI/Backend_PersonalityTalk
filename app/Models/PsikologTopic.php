<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsikologTopic extends Model
{
    use HasFactory;

    protected $table = 'psikolog_topics';
    protected $fillable = [
        'psikolog_id', 
        'topic_id'
    ];

    public function psikolog()
    {
        return $this->belongsTo(Psikolog::class, 'psikolog_id', 'id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'id');
    }
}
