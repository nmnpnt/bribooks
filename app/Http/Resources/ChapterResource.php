<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'book_id'     => $this->book_id,
            'title'       => $this->title,
            'description' => $this->description,
            'order'       => $this->order,
            'pages'       => PageResource::collection($this->whenLoaded('pages')),
            'pages_count' => $this->whenLoaded('pages', fn() => $this->pages->count()),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
