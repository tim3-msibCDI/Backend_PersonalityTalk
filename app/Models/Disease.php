<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    protected $table = 'diseases';

    protected $fillable = [
        'disease_name',
        'disease_img',
        'content',
        'admin_id',
    ];

    public function admin_writer()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}
