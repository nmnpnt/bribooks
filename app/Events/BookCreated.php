<?php

namespace App\Events;

use App\Models\Book;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Book $book) {}
}
