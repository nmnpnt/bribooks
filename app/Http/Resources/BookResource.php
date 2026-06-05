<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'genre'            => $this->genre,
            'language'         => $this->language,
            'status'           => $this->status,
            'cover_image'      => $this->cover_image,
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'author'           => new UserResource($this->whenLoaded('author')),
            'chapters'         => ChapterResource::collection($this->whenLoaded('chapters')),
            'versions_count'   => $this->whenLoaded('versions', fn() => $this->versions->count()),
            'submitted_at'     => $this->submitted_at?->toIso8601String(),
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'published_at'     => $this->published_at?->toIso8601String(),
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
