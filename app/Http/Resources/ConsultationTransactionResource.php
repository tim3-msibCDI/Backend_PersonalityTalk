<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Hitung harga konsultasi setelah diskon
        $consultation_price = $this->consul_fee - $this->discount_amount;
        $psikolog_comission = $this->consul_fee * 0.6;

        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'user_name' => $this->user->name,
            'payment_date' => Carbon::parse($this->payment_completed_at) ? Carbon::parse($this->payment_completed_at)->format('d-m-Y H:i') : null,
            'payment_method' => $this->paymentMethod->name,
            'status' => $this->status,
            'consul_fee' => $this->consul_fee,
            'psikolog_comission' => $psikolog_comission,
            'payment_proof' => $this->payment_proof,
        ];
    }
}

