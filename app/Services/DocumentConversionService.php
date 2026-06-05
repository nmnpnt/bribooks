<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentConversionService
{
    /**
     * Convert a .doc/.docx file into chapters and pages inside the given book.
     *
     * Strategy:
     *  1. Use `soffice` (LibreOffice) to convert .docx → .html
     *  2. Parse the HTML, split into "pages" by <h1>/<h2> headings (chapters)
     *     and by a configurable character-limit per page.
     *  3. Persist chapters and pages into the database.
     */
    public function convert(Book $book, string $filePath): void
    {
        $htmlPath = $this->convertToHtml($filePath);

        try {
            $html = file_get_contents($htmlPath);
            $this->parseAndPersist($book, $html);
        } finally {
            // Clean up temp HTML file
            if (file_exists($htmlPath)) {
                @unlink($htmlPath);
            }
        }
    }

    // ---------------------------------------------------------------

    /**
     * Use LibreOffice headless to convert the document to HTML.
     */
    private function convertToHtml(string $filePath): string
    {
        $outDir = dirname($filePath);

        $cmd = sprintf(
            'soffice --headless --convert-to html %s --outdir %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($outDir)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                'LibreOffice conversion failed: ' . implode("\n", $output)
            );
        }

        // LibreOffice replaces extension with .html
        $htmlPath = $outDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.html';

        if (! file_exists($htmlPath)) {
            throw new \RuntimeException('Converted HTML file not found: ' . $htmlPath);
        }

        return $htmlPath;
    }

    /**
     * Parse HTML content; split by heading tags into chapters,
     * then split each chapter's text into pages by character count.
     */
    private function parseAndPersist(Book $book, string $html): void
    {
        // Strip <html>, <head>, <body> wrappers — keep body content
        if (preg_match('/<body[^>]*>(.*?)<\/body>/si', $html, $m)) {
            $html = $m[1];
        }

        // Split by <h1> or <h2> to form chapters
        $sections = preg_split('/(?=<h[12][^>]*>)/i', $html, -1, PREG_SPLIT_NO_EMPTY);

        DB::transaction(function () use ($book, $sections) {
            $chapterOrder = $book->chapters()->max('order') ?? 0;

            foreach ($sections as $sectionHtml) {
                $sectionHtml = trim($sectionHtml);
                if (empty($sectionHtml)) {
                    continue;
                }

                // Extract chapter title from heading
                $title = 'Untitled Chapter';
                if (preg_match('/<h[12][^>]*>(.*?)<\/h[12]>/i', $sectionHtml, $hm)) {
                    $title = strip_tags($hm[1]);
                    // Remove the heading from body content
                    $sectionHtml = preg_replace('/<h[12][^>]*>.*?<\/h[12]>/i', '', $sectionHtml, 1);
                }

                $chapterOrder++;
                $chapter = Chapter::create([
                    'book_id' => $book->id,
                    'title'   => Str::limit($title, 255),
                    'order'   => $chapterOrder,
                ]);

                // Split chapter content into pages (~3000 chars each)
                $pages = $this->splitIntoPages($sectionHtml, 3000);
                foreach ($pages as $index => $pageContent) {
                    Page::create([
                        'chapter_id'   => $chapter->id,
                        'content'      => $pageContent,
                        'content_type' => 'html',
                        'page_number'  => $index + 1,
                    ]);
                }
            }
        });
    }

    /**
     * Split an HTML string into chunks of approximately $maxChars characters
     * without breaking in the middle of a tag.
     */
    private function splitIntoPages(string $html, int $maxChars): array
    {
        if (strlen($html) <= $maxChars) {
            return [trim($html)];
        }

        $pages  = [];
        $buffer = '';

        // Split by block-level tags to avoid splitting mid-element
        $blocks = preg_split('/(<\/(?:p|div|li|blockquote|pre|table|tr|td|th)>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($blocks as $block) {
            $buffer .= $block;
            if (strlen(strip_tags($buffer)) >= $maxChars) {
                $pages[] = trim($buffer);
                $buffer  = '';
            }
        }

        if (! empty(trim($buffer))) {
            $pages[] = trim($buffer);
        }

        return array_filter($pages, fn($p) => ! empty(trim(strip_tags($p))));
    }
}
