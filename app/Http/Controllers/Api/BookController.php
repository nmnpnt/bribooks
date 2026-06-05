<?php

namespace App\Http\Controllers\Api;

use App\Events\BookCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(private BookService $bookService) {}

    /**
     * GET /api/books
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth('api')->user();
        $query = Book::with(['author', 'chapters']);

        // Authors only see their own books; reviewers/admins see all
        if ($user->isAuthor()) {
            $query->where('user_id', $user->id);
        }

        // Optional status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $books = $query->latest()->paginate(15);

        return response()->json([
            'data'  => BookResource::collection($books),
            'meta'  => [
                'current_page' => $books->currentPage(),
                'last_page'    => $books->lastPage(),
                'total'        => $books->total(),
            ],
        ]);
    }

    /**
     * POST /api/books
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->createBook(
            auth('api')->user(),
            $request->validated()
        );

        event(new BookCreated($book));

        return response()->json([
            'message' => 'Book created successfully.',
            'data'    => new BookResource($book),
        ], 201);
    }

    /**
     * GET /api/books/{id}
     */
    public function show(Book $book): JsonResponse
    {
        $this->authorizeBookAccess($book);

        $book->load(['author', 'chapters.pages', 'versions']);

        return response()->json([
            'data' => new BookResource($book),
        ]);
    }

    /**
     * PUT /api/books/{id}
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $this->authorizeOwner($book);
        $this->denyIfReadOnly($book);

        $book = $this->bookService->updateBook($book, $request->validated());

        return response()->json([
            'message' => 'Book updated successfully.',
            'data'    => new BookResource($book),
        ]);
    }

    /**
     * DELETE /api/books/{id}
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorizeOwner($book);
        $this->denyIfReadOnly($book);

        $book->delete();

        return response()->json(['message' => 'Book deleted successfully.']);
    }

    // ---------------------------------------------------------------
    // Helpers

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
