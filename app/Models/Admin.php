<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'photo_profile',
        'phone_number',
        'email',
        'password',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'admin_id');
    }

    public function diseases()
    {
        return $this->hasMany(Diseases::class, 'admin_id');
    }

}
