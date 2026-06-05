<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookService
{
    /**
     * Create a new book for the given author.
     */
    public function createBook(User $author, array $data): Book
    {
        return DB::transaction(function () use ($author, $data) {
            return Book::create([
                'user_id'     => $author->id,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'genre'       => $data['genre'] ?? null,
                'language'    => $data['language'] ?? 'en',
                'status'      => 'draft',
            ]);
        });
    }

    /**
     * Update an existing book's metadata.
     */
    public function updateBook(Book $book, array $data): Book
    {
        return DB::transaction(function () use ($book, $data) {
            $book->update(array_filter([
                'title'       => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'genre'       => $data['genre'] ?? null,
                'language'    => $data['language'] ?? null,
            ], fn($v) => $v !== null));

            return $book->fresh();
        });
    }
}
