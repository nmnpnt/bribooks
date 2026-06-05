<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // JWT Interface
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'name' => $this->name,
        ];
    }

    // Relationships
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    // Role helpers
    public function isAuthor(): bool
    {
        return $this->role === 'author';
    }

    public function isReviewer(): bool
    {
        return $this->role === 'reviewer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
