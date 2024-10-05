<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsikologCategory extends Model
{
    use HasFactory;

    protected $table = 'psikolog_categories';
    protected $fillable = [
        'category_name', 
    ];

    public function category()
    {
        return $this->hasMany(Psikolog::class, 'category_id');
    }
}
