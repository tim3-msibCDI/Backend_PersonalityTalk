<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Psikolog extends Model
{
    use HasFactory;

    protected $table = 'psikolog';
    protected $fillable = [
        'user_id', 
        'category_id',
        'psikolog_price_id',
        'description',
        'sipp',
        'practice_start_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'practice_start_date' => 'date', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function psikolog_category()
    {
        return $this->belongsTo(PsikologCategory::class, 'category_id', 'id');
    }

    public function psikolog_price()
    {
        return $this->belongsTo(PsikologPrice::class, 'pskolog_price_id', 'id');
    }

    public function psikolog_topic()
    {
        return $this->hasMany(PsikologTopic::class, 'psikolog_id');
    }

}
