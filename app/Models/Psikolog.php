<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'bank_id',
        'account_number',
        'practice_start_date',
        'status',
        'is_active',
    ];

    public function getYearsOfExperience()
    {
        return floor(Carbon::parse($this->practice_start_date)->diffInYears(Carbon::now()));
    }

    protected $casts = [
        'is_active' => 'boolean',
        'practice_start_date' => 'date', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function psikolog_category()
    {
        return $this->belongsTo(PsikologCategory::class, 'category_id', 'id');
    }

    public function psikolog_price()
    {
        return $this->belongsTo(PsikologPrice::class, 'psikolog_price_id', 'id');
    }

    public function psikolog_topic()
    {
        return $this->hasMany(PsikologTopic::class, 'psikolog_id');
    }

    public function psikologSchedule()
    {
        return $this->hasMany(PsikologSchedule::class, 'psikolog_id');
    }

    public function consultation()
    {
        return $this->hasMany(Consultation::class, 'psi_id');
    }

    public function rating()
    {
        return $this->hasMany(PsikologReview::class, 'psi_id');
    }

    public function bank()
    {
        return $this->belongsTo(PaymentMethod::class, 'bank_id', 'id');
    }
}
