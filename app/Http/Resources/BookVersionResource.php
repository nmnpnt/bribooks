<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'book_id'        => $this->book_id,
            'version_number' => $this->version_number,
            'label'          => $this->label,
            'change_notes'   => $this->change_notes,
            'snapshot'       => $this->snapshot,
            'created_by'     => new UserResource($this->whenLoaded('creator')),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
