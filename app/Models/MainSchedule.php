<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainSchedule extends Model
{
    use HasFactory;

    protected $table = 'main_schedules';
    protected $fillable = [
        'day', 
        'start_hour', 
        'end_hour' 
    ];

    /**
     * Relation to PsychologistSchedule
     */
    public function psikologSchedules()
    {
        return $this->hasMany(PsikologSchedule::class, 'msch_id');
    }


}
