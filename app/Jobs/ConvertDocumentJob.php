<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\DocumentConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly Book   $book,
        public readonly string $filePath
    ) {}

    public function handle(DocumentConversionService $service): void
    {
        Log::info("Starting document conversion for book #{$this->book->id}");

        try {
            $service->convert($this->book, $this->filePath);
            Log::info("Document conversion complete for book #{$this->book->id}");
        } catch (\Throwable $e) {
            Log::error("Document conversion failed for book #{$this->book->id}: {$e->getMessage()}");
            throw $e;
        } finally {
            // Clean up the uploaded file after conversion attempt
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ConvertDocumentJob permanently failed for book #{$this->book->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
