<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'result',
        'flagged_items',
        'summary',
    ];

    protected $casts = [
        'flagged_items' => 'array',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
