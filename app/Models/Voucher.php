<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    protected $table = 'vouchers';

    protected $fillable = [
        'code',
        'voucher_type',
        'discount_value',
        'min_transaction_amount',
        'valid_from',
        'valid_to',
        'quota',
        'used',
        'is_active'
    ];

    public function consulTransaction()
    {
        return $this->hasMany(ConsultationTransaction::class, 'voucher_id');
    }

    
}
