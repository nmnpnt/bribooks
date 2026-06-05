<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chapter_id',
        'content',
        'content_type',
        'page_number',
    ];

    // Relationships
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    // Snapshot for versioning
    public function toSnapshot(): array
    {
        return [
            'id'           => $this->id,
            'content'      => $this->content,
            'content_type' => $this->content_type,
            'page_number'  => $this->page_number,
        ];
    }
}
