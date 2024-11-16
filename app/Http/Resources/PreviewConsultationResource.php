<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PreviewConsultationResource extends JsonResource
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

        ];
    }
}

