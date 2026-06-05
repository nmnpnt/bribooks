<?php

namespace App\Http\Controllers\Api;

use App\Events\BookVersionCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\CreateVersionRequest;
use App\Http\Resources\BookVersionResource;
use App\Models\Book;
use App\Models\BookVersion;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;

class BookVersionController extends Controller
{
    public function __construct(private VersionService $versionService) {}

    /**
     * POST /api/books/{id}/versions
     * Create a new version snapshot of the current book state.
     */
    public function store(CreateVersionRequest $request, Book $book): JsonResponse
    {
        $this->authorizeOwner($book);

        $version = $this->versionService->createSnapshot(
            $book,
            auth('api')->user(),
            $request->validated()
        );

        event(new BookVersionCreated($version));

        return response()->json([
            'message' => 'Version created successfully.',
            'data'    => new BookVersionResource($version),
        ], 201);
    }

    /**
     * GET /api/books/{id}/versions
     */
    public function index(Book $book): JsonResponse
    {
        $this->authorizeBookAccess($book);

        $versions = $book->versions()
            ->with('creator')
            ->paginate(10);

        return response()->json([
            'data' => BookVersionResource::collection($versions),
            'meta' => [
                'current_page' => $versions->currentPage(),
                'last_page'    => $versions->lastPage(),
                'total'        => $versions->total(),
            ],
        ]);
    }

    /**
     * GET /api/books/{id}/versions/{versionId}
     */
    public function show(Book $book, BookVersion $version): JsonResponse
    {
        $this->authorizeBookAccess($book);

        if ($version->book_id !== $book->id) {
            abort(404, 'Version not found for this book.');
        }

        $version->load('creator');

        return response()->json([
            'data' => new BookVersionResource($version),
        ]);
    }

    /**
     * POST /api/books/{id}/versions/{versionId}/restore
     * Rollback book content to a previous version.
     */
    public function restore(Book $book, BookVersion $version): JsonResponse
    {
        $this->authorizeOwner($book);

        if ($book->isReadOnly()) {
            abort(403, 'Published books are read-only.');
        }

        if ($version->book_id !== $book->id) {
            abort(404, 'Version not found for this book.');
        }

        $this->versionService->restoreFromSnapshot($book, $version);

        return response()->json([
            'message' => "Book restored to version {$version->version_number}.",
        ]);
    }

    // ---------------------------------------------------------------

    private function authorizeOwner(Book $book): void
    {
        if ($book->user_id !== auth('api')->id()) {
            abort(403, 'You do not own this book.');
        }
    }

    private function authorizeBookAccess(Book $book): void
    {
        $user = auth('api')->user();
        if ($user->isAuthor() && $book->user_id !== $user->id) {
            abort(403, 'You do not have access to this book.');
        }
    }
}
