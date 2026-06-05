<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'book_id',
        'title',
        'description',
        'order',
    ];

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class)->orderBy('page_number');
    }

    // Snapshot for versioning
    public function toSnapshot(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'order'       => $this->order,
            'pages'       => $this->pages->map(fn($p) => $p->toSnapshot())->toArray(),
        ];
    }
}
