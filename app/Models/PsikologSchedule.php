<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsikologSchedule extends Model
{
    use HasFactory;

    protected $table = 'psikolog_schedules';

    protected $fillable = [
        'psikolog_id', 
        'date', 
        'is_available', 
        'msch_id'
    ];

    /**
     * Relation to MainSchedule
     */
    public function mainSchedule()
    {
        return $this->belongsTo(MainSchedule::class, 'msch_id');
    }

    /**
     * Relation to Psychologist
     */
    public function psikolog()
    {
        return $this->belongsTo(Psikolog::class, 'psikolog_id');
    }
}
