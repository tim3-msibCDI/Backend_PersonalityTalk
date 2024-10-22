<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'topics';
    protected $fillable = [
        'topic_name', 
    ];

    public function psikolog_topic()
    {
        return $this->hasMany(PsikologTopic::class, 'topic_id');
    }

}
