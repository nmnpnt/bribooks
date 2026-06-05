<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;

class ChapterController extends Controller
{
    /**
     * GET /api/books/{id}/chapters
     */
    public function index(Book $book): JsonResponse
    {
        $this->authorizeBookAccess($book);

        $chapters = $book->chapters()->with('pages')->get();

        return response()->json([
            'data' => ChapterResource::collection($chapters),
        ]);
    }

    /**
     * POST /api/books/{id}/chapters
     */
    public function store(StoreChapterRequest $request, Book $book): JsonResponse
    {
        $this->authorizeOwner($book);
        $this->denyIfReadOnly($book);

        // Auto-assign order (append to end)
        $order = $book->chapters()->max('order') + 1;

        $chapter = $book->chapters()->create([
            'title'       => $request->title,
            'description' => $request->description,
            'order'       => $request->order ?? $order,
        ]);

        return response()->json([
            'message' => 'Chapter created successfully.',
            'data'    => new ChapterResource($chapter),
        ], 201);
    }

    /**
     * PUT /api/chapters/{id}
     */
    public function update(UpdateChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $this->authorizeOwner($chapter->book);
        $this->denyIfReadOnly($chapter->book);

        $chapter->update($request->validated());

        return response()->json([
            'message' => 'Chapter updated.',
            'data'    => new ChapterResource($chapter),
        ]);
    }

    /**
     * DELETE /api/chapters/{id}
     */
    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorizeOwner($chapter->book);
        $this->denyIfReadOnly($chapter->book);

        $chapter->delete();

        return response()->json(['message' => 'Chapter deleted.']);
    }

    // ---------------------------------------------------------------

    private function authorizeBookAccess(Book $book): void
    {
        $user = auth('api')->user();
        if ($user->isAuthor() && $book->user_id !== $user->id) {
            abort(403, 'You do not have access to this book.');
        }
    }

    private function authorizeOwner(Book $book): void
    {
        if ($book->user_id !== auth('api')->id()) {
            abort(403, 'You do not own this book.');
        }
    }

    private function denyIfReadOnly(Book $book): void
    {
        if ($book->isReadOnly()) {
            abort(403, 'Published books are read-only.');
        }
    }
}
