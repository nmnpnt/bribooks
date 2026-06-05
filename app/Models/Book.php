<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'genre',
        'status',
        'cover_image',
        'language',
        'reviewed_by',
        'published_by',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function versions()
    {
        return $this->hasMany(BookVersion::class)->orderBy('version_number', 'desc');
    }

    public function moderationLogs()
    {
        return $this->hasMany(ModerationLog::class);
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isReadOnly(): bool
    {
        return $this->status === 'published';
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    // Next version number
    public function nextVersionNumber(): int
    {
        return ($this->versions()->max('version_number') ?? 0) + 1;
    }
}
