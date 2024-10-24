<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'article_img' => $this->article_img,
            'article_title' => $this->article_title,
            'publication_date' => Carbon::parse($this->publication_date)->translatedFormat('d F Y'),
            'admin_writer' => $this->whenLoaded('admin_writer', function () {
                return [
                    'id' => $this->admin_writer->id,
                    'name' => $this->admin_writer->name,
                ];
            }),
            'article_category' => $this->whenLoaded('article_category', function () {
                return [
                    'id' => $this->article_category->id,
                    'category_name' => $this->article_category->name,
                ];
            }),
        ];
    }
}

