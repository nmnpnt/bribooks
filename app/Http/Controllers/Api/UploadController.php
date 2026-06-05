<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\UploadManuscriptRequest;
use App\Jobs\ConvertDocumentJob;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * POST /api/books/{id}/upload
     * Upload a .doc or .docx manuscript; dispatches conversion job.
     */
    public function upload(UploadManuscriptRequest $request, Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if ($book->user_id !== $user->id) {
            abort(403, 'You do not own this book.');
        }

        if ($book->isReadOnly()) {
            abort(403, 'Published books are read-only.');
        }

        // Store the uploaded file
        $file = $request->file('manuscript');
        $path = $file->store("manuscripts/{$book->id}", 'local');

        // Dispatch background conversion job
        ConvertDocumentJob::dispatch($book, storage_path("app/{$path}"));

        return response()->json([
            'message'   => 'Manuscript uploaded. Conversion is being processed in the background.',
            'file_path' => $path,
        ], 202);
    }
}
