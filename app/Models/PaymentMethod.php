<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'type',
        'bank_code',
        'logo',
        'owner',
        'no_rek',
        'is_active',
    ];

    public function consulPaymentTransaction()
    {
        return $this->hasMany(ConsultationTransaction::class, 'payment_method_id');
    }
} 