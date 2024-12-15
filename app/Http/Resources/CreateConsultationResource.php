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
            'id_transaction' => $this->transaction->id,
            'id_consultation' => $this->consultation->id,
            // 'psch_id' => $this->selectedSchedule->id, 
            'psi_id' => $this->psikolog->id,
            'client_id' => $this->user->id ?? null,
            'chat_session_id' => $this->chatSession->id ?? null,
            'chat_status' => $this->consultation->consul_status ?? null,
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
            'patient_complaint' => $this->consultation->patient_complaint ?? null,
    
            // Tambahan untuk transaction dan payment
            'transaction' => [
                'payment_method_name' => $this->payment->name ?? null,
                'owner_bank' => $this->payment->owner ?? null,
                'no_rekening' => $this->payment->no_rek ?? null,
                'status' => $this->transaction->status ?? null,
                'no_pemesanan' => $this->transaction->payment_number ?? null,
                'total_harga' => $this->transaction->consul_fee ?? null,
                'diskon' => $this->transaction->discount_amount ?? null,
                'total_pembayaran' => $this->finalAmount ?? null,
                'booking_date' => $this->transaction->created_at ? Carbon::parse($this->transaction->created_at)->translatedFormat('d-m-Y H:i') : null,
                'payment_date' => $this->transaction->payment_completed_at ? Carbon::parse($this->transaction->payment_completed_at)->translatedFormat('d-m-Y H:i') : null,
            ],
        ];
    }
}
