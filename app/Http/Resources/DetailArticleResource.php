<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'article_title' => $this->article_title,
            'category' => $this->article_category->name,
            'category_id' => $this->article_category->id,
            'article_img' => $this->article_img,
            'publication_date' => Carbon::parse($this->publication_date)->translatedFormat('d F Y'),
            'content' => $this->content, 
            'publisher_name' => $this->admin_writer->name ?? null, 
        ];
    }
}
