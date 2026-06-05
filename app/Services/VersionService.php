<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookVersion;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VersionService
{
    /**
     * Capture the current state of a book as a versioned snapshot.
     *
     * Snapshot schema:
     * {
     *   "metadata": { title, description, genre, language, status },
     *   "chapters": [
     *     { id, title, description, order,
     *       "pages": [ { id, content, content_type, page_number } ] }
     *   ]
     * }
     */
    public function createSnapshot(Book $book, User $creator, array $data): BookVersion
    {
        return DB::transaction(function () use ($book, $creator, $data) {
            $book->load('chapters.pages');

            $snapshot = [
                'metadata' => [
                    'title'       => $book->title,
                    'description' => $book->description,
                    'genre'       => $book->genre,
                    'language'    => $book->language,
                    'status'      => $book->status,
                ],
                'chapters' => $book->chapters->map(fn($c) => $c->toSnapshot())->toArray(),
            ];

            return BookVersion::create([
                'book_id'        => $book->id,
                'created_by'     => $creator->id,
                'version_number' => $book->nextVersionNumber(),
                'label'          => $data['label'] ?? null,
                'change_notes'   => $data['change_notes'] ?? null,
                'snapshot'       => $snapshot,
            ]);
        });
    }

    /**
     * Restore a book's chapters and pages from a version snapshot.
     * This replaces the current live data with the snapshot data.
     */
    public function restoreFromSnapshot(Book $book, BookVersion $version): void
    {
        DB::transaction(function () use ($book, $version) {
            $snapshot = $version->snapshot;

            // Restore metadata
            $book->update($snapshot['metadata']);

            // Delete current chapters (cascades to pages via soft-delete)
            $book->chapters()->delete();

            // Recreate chapters and pages from snapshot
            foreach ($snapshot['chapters'] as $chapterData) {
                $chapter = Chapter::create([
                    'book_id'     => $book->id,
                    'title'       => $chapterData['title'],
                    'description' => $chapterData['description'] ?? null,
                    'order'       => $chapterData['order'],
                ]);

                foreach ($chapterData['pages'] as $pageData) {
                    Page::create([
                        'chapter_id'   => $chapter->id,
                        'content'      => $pageData['content'],
                        'content_type' => $pageData['content_type'],
                        'page_number'  => $pageData['page_number'],
                    ]);
                }
            }
        });
    }
}
