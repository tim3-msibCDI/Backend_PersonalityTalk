<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CreateConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rating = number_format($this->rating ?? 0, 1);

        return [
            'psikolog_name' => $this->psikolog->user->name,
            'photo_profile' => $this->psikolog->user->photo_profile,
            'category' => $this->psikolog->psikolog_category->category_name,
            'rating' => $rating,
            'years_of_experience' => $this->psikolog->getYearsOfExperience(),
            'price' => $this->psikolog->psikolog_price->price,
            'topic' => $this->selectedTopic->topic_name,
            'consultation_date' => Carbon::parse($this->selectedSchedule->date)->translatedFormat('l, j F'),
            'consultation_time' => Carbon::parse($this->selectedSchedule->mainSchedule->start_hour)->format('H:i') . ' - ' .
                Carbon::parse($this->selectedSchedule->mainSchedule->end_hour)->format('H:i'),
    
            // Tambahan untuk transaction dan payment
            'transaction' => [
                'payment_method_name' => $this->payment->name ?? null, 
                'status' => $this->transaction->status ?? null,
                'no_pemesanan' => $this->transaction->payment_number ?? null,
                'total_harga' => $this->transaction->consul_fee ?? null,
                'diskon' => $this->transaction->discount_amount ?? null,
                'total_pembayaran' => $this->finalAmount ?? null,
            ],
        ];
    }
}