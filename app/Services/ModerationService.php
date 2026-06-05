<?php

namespace App\Services;

use App\Models\Book;
use App\Models\ModerationLog;

class ModerationService
{
    /**
     * A basic profanity list. In production, replace/extend with a
     * dedicated library such as `wamania/php-profanity-filter` or
     * an external API (e.g. OpenAI Moderation API).
     */
    private array $profanityList = [
        'fuck', 'shit', 'asshole', 'bitch', 'bastard',
        'cunt', 'dick', 'piss', 'cock', 'damn',
    ];

    /**
     * Restricted words specific to the platform (violence, extremism, etc.)
     */
    private array $restrictedWords = [
        'kill', 'murder', 'suicide', 'bomb', 'terrorist',
        'genocide', 'nazi', 'isis', 'drugs', 'cocaine',
    ];

    /**
     * Run moderation checks on all text content in a book.
     *
     * @return array{ result: 'passed'|'flagged', flagged_items: array }
     */
    public function moderate(Book $book): array
    {
        $book->load('chapters.pages');

        $allText  = $this->extractAllText($book);
        $flagged  = [];

        // Check for profanity
        foreach ($this->profanityList as $word) {
            if ($this->containsWord($allText, $word)) {
                $flagged[] = [
                    'type' => 'profanity',
                    'word' => $word,
                ];
            }
        }

        // Check for restricted words
        foreach ($this->restrictedWords as $word) {
            if ($this->containsWord($allText, $word)) {
                $flagged[] = [
                    'type' => 'restricted_word',
                    'word' => $word,
                ];
            }
        }

        $result = empty($flagged) ? 'passed' : 'flagged';

        // Persist the moderation log
        ModerationLog::create([
            'book_id'      => $book->id,
            'result'       => $result,
            'flagged_items' => $flagged,
            'summary'      => $result === 'passed'
                ? 'No violations detected.'
                : count($flagged) . ' violation(s) detected.',
        ]);

        return [
            'result'        => $result,
            'flagged_items' => $flagged,
        ];
    }

    // ---------------------------------------------------------------

    /**
     * Combine all textual content from book metadata, chapters, and pages.
     */
    private function extractAllText(Book $book): string
    {
        $parts = [
            $book->title,
            $book->description ?? '',
        ];

        foreach ($book->chapters as $chapter) {
            $parts[] = $chapter->title;
            $parts[] = $chapter->description ?? '';

            foreach ($chapter->pages as $page) {
                // Strip HTML tags before checking
                $parts[] = strip_tags($page->content);
            }
        }

        return strtolower(implode(' ', $parts));
    }

    /**
     * Case-insensitive whole-word match.
     */
    private function containsWord(string $text, string $word): bool
    {
        return (bool) preg_match('/\b' . preg_quote($word, '/') . '\b/i', $text);
    }
}
