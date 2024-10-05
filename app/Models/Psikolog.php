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
        'pskolog_price_id',
        'description',
        'sipp',
        'is_active',
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

    




}
