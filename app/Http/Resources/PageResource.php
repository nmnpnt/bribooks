<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'chapter_id'   => $this->chapter_id,
            'content'      => $this->content,
            'content_type' => $this->content_type,
            'page_number'  => $this->page_number,
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
