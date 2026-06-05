<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'created_by',
        'version_number',
        'label',
        'change_notes',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
