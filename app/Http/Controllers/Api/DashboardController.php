<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $cacheKey = "dashboard:{$user->id}";

        // Cache for 5 minutes (bonus: Redis if available)
        $data = Cache::remember($cacheKey, 300, function () use ($user) {

            if ($user->isAuthor()) {
                return $this->authorDashboard($user);
            }

            if ($user->isReviewer()) {
                return $this->reviewerDashboard();
            }

            return $this->adminDashboard();
        });

        return response()->json(['data' => $data]);
    }

    // ---------------------------------------------------------------

    private function authorDashboard($user): array
    {
        $books = Book::where('user_id', $user->id)->get();

        $byStatus = $books->groupBy('status')->map->count();

        $recentBooks = Book::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'created_at']);

        return [
            'role'         => 'author',
            'total_books'  => $books->count(),
            'by_status'    => $byStatus,
            'recent_books' => $recentBooks,
        ];
    }

    private function reviewerDashboard(): array
    {
        $pending = Book::whereIn('status', ['submitted', 'under_review'])->count();

        $recentlyReviewed = Book::where('reviewed_by', auth('api')->id())
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('approved_at')
            ->take(5)
            ->get(['id', 'title', 'status', 'approved_at']);

        return [
            'role'              => 'reviewer',
            'pending_review'    => $pending,
            'recently_reviewed' => $recentlyReviewed,
        ];
    }

    private function adminDashboard(): array
    {
        $stats = Book::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentPublished = Book::where('status', 'published')
            ->latest('published_at')
            ->take(5)
            ->get(['id', 'title', 'published_at']);

        return [
            'role'             => 'admin',
            'books_by_status'  => $stats,
            'recently_published' => $recentPublished,
            'total_users'      => \App\Models\User::count(),
        ];
    }
}
