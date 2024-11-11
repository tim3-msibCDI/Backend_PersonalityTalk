<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsikologPrice extends Model
{
    use HasFactory;

    protected $table = 'psikolog_prices';
    protected $fillable = [
        'code',
        'price', 
    ];

    public function psikolog()
    {
        return $this->hasMany(Psikolog::class, 'psikolog_price_id');
    }
}
