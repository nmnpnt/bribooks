<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Chapter;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * GET /api/chapters/{id}/pages
     */
    public function index(Chapter $chapter): JsonResponse
    {
        $this->authorizeBookAccess($chapter);

        $pages = $chapter->pages()->get();

        return response()->json([
            'data' => PageResource::collection($pages),
        ]);
    }

    /**
     * POST /api/chapters/{id}/pages
     */
    public function store(StorePageRequest $request, Chapter $chapter): JsonResponse
    {
        $book = $chapter->book;
        $this->authorizeOwner($book);
        $this->denyIfReadOnly($book);

        // Auto-assign page number
        $pageNumber = $chapter->pages()->max('page_number') + 1;

        $page = $chapter->pages()->create([
            'content'      => $request->content,
            'content_type' => $request->content_type ?? 'html',
            'page_number'  => $request->page_number ?? $pageNumber,
        ]);

        return response()->json([
            'message' => 'Page created successfully.',
            'data'    => new PageResource($page),
        ], 201);
    }

    /**
     * PUT /api/pages/{id}
     */
    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $book = $page->chapter->book;
        $this->authorizeOwner($book);
        $this->denyIfReadOnly($book);

        $page->update($request->validated());

        return response()->json([
            'message' => 'Page updated.',
            'data'    => new PageResource($page),
        ]);
    }

    /**
     * DELETE /api/pages/{id}
     */
    public function destroy(Page $page): JsonResponse
    {
        $book = $page->chapter->book;
        $this->authorizeOwner($book);
        $this->denyIfReadOnly($book);

        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    // ---------------------------------------------------------------

    private function authorizeBookAccess(Chapter $chapter): void
    {
        $user = auth('api')->user();
        if ($user->isAuthor() && $chapter->book->user_id !== $user->id) {
            abort(403, 'You do not have access to this chapter.');
        }
    }

    private function authorizeOwner($book): void
    {
        if ($book->user_id !== auth('api')->id()) {
            abort(403, 'You do not own this book.');
        }
    }

    private function denyIfReadOnly($book): void
    {
        if ($book->isReadOnly()) {
            abort(403, 'Published books are read-only.');
        }
    }
}
