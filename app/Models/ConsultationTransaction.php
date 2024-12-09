<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationTransaction extends Model
{
    use HasFactory;

    protected $table = 'consul_transactions';

    protected $fillable = [
        'payment_number',
        'user_id',
        'consultation_id',
        'voucher_id',
        'payment_method_id',
        'consul_fee',
        'discount_amount',
        'payment_proof',
        'sender_name',
        'sender_bank',
        'status',
        'transaction_reference',
        'payment_gateway_response',
        'payment_expiration',
        'payment_completed_at',
        'failure_reason',
        'commission_transfer_status',
        'commission_transfer_proof',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id', 'id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }
}
