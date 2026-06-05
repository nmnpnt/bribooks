<?php

namespace App\Http\Controllers\Api;

use App\Events\BookApproved;
use App\Events\BookPublished;
use App\Events\BookSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\RejectBookRequest;
use App\Models\Book;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    public function __construct(private ModerationService $moderationService) {}

    /**
     * POST /api/books/{id}/submit
     * Only Authors can submit.
     */
    public function submit(Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user->isAuthor()) {
            abort(403, 'Only authors can submit books.');
        }

        if ($book->user_id !== $user->id) {
            abort(403, 'You do not own this book.');
        }

        if (! $book->canBeSubmitted()) {
            abort(422, "Book cannot be submitted from '{$book->status}' status.");
        }

        // Run content moderation
        $modResult = $this->moderationService->moderate($book);

        if ($modResult['result'] === 'flagged') {
            return response()->json([
                'message'      => 'Book failed content moderation and cannot be submitted.',
                'flagged_items' => $modResult['flagged_items'],
            ], 422);
        }

        DB::transaction(function () use ($book, $user) {
            $book->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        event(new BookSubmitted($book));

        return response()->json([
            'message' => 'Book submitted for review.',
            'data'    => $book->fresh(),
        ]);
    }

    /**
     * POST /api/books/{id}/approve
     * Only Reviewers can approve.
     */
    public function approve(Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user->isReviewer()) {
            abort(403, 'Only reviewers can approve books.');
        }

        if (! in_array($book->status, ['submitted', 'under_review'])) {
            abort(422, "Book must be submitted or under review to approve.");
        }

        DB::transaction(function () use ($book, $user) {
            $book->update([
                'status'      => 'approved',
                'reviewed_by' => $user->id,
                'approved_at' => now(),
            ]);
        });

        event(new BookApproved($book));

        return response()->json([
            'message' => 'Book approved.',
            'data'    => $book->fresh(),
        ]);
    }

    /**
     * POST /api/books/{id}/reject
     * Only Reviewers can reject.
     */
    public function reject(RejectBookRequest $request, Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user->isReviewer()) {
            abort(403, 'Only reviewers can reject books.');
        }

        if (! in_array($book->status, ['submitted', 'under_review'])) {
            abort(422, "Book must be submitted or under review to reject.");
        }

        $book->update([
            'status'           => 'rejected',
            'reviewed_by'      => $user->id,
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Book rejected.',
            'data'    => $book->fresh(),
        ]);
    }

    /**
     * POST /api/books/{id}/publish
     * Only Admins can publish.
     */
    public function publish(Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user->isAdmin()) {
            abort(403, 'Only admins can publish books.');
        }

        if ($book->status !== 'approved') {
            abort(422, 'Book must be approved before publishing.');
        }

        DB::transaction(function () use ($book, $user) {
            $book->update([
                'status'       => 'published',
                'published_by' => $user->id,
                'published_at' => now(),
            ]);
        });

        event(new BookPublished($book));

        return response()->json([
            'message' => 'Book published successfully.',
            'data'    => $book->fresh(),
        ]);
    }
}
