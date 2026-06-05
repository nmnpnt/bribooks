<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private ModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ModerationService();
    }

    public function test_clean_book_passes_moderation(): void
    {
        $book = $this->createBookWithContent('A beautiful adventure story.');

        $result = $this->service->moderate($book);

        $this->assertEquals('passed', $result['result']);
        $this->assertEmpty($result['flagged_items']);
    }

    public function test_book_with_profanity_is_flagged(): void
    {
        $book = $this->createBookWithContent('This is some shit content.');

        $result = $this->service->moderate($book);

        $this->assertEquals('flagged', $result['result']);
        $this->assertNotEmpty($result['flagged_items']);
        $this->assertEquals('profanity', $result['flagged_items'][0]['type']);
    }

    public function test_book_with_restricted_word_is_flagged(): void
    {
        $book = $this->createBookWithContent('The bomb exploded violently.');

        $result = $this->service->moderate($book);

        $this->assertEquals('flagged', $result['result']);
        $types = array_column($result['flagged_items'], 'type');
        $this->assertContains('restricted_word', $types);
    }

    public function test_moderation_log_is_persisted(): void
    {
        $book = $this->createBookWithContent('A clean story.');

        $this->service->moderate($book);

        $this->assertDatabaseHas('moderation_logs', [
            'book_id' => $book->id,
            'result'  => 'passed',
        ]);
    }

    public function test_html_tags_are_stripped_before_checking(): void
    {
        // The word "shit" inside an HTML attribute should be caught
        $book = $this->createBookWithContent('<p class="shit-class">A normal paragraph.</p>');

        $result = $this->service->moderate($book);

        // "shit" appears as part of a CSS class — after strip_tags it becomes "A normal paragraph."
        // so it should pass (we strip tags before checking)
        $this->assertEquals('passed', $result['result']);
    }

    // ---------------------------------------------------------------

    private function createBookWithContent(string $content): Book
    {
        $author = User::factory()->create(['role' => 'author']);
        $book   = Book::factory()->create(['user_id' => $author->id]);

        $chapter = Chapter::factory()->create(['book_id' => $book->id]);
        Page::factory()->create([
            'chapter_id'   => $chapter->id,
            'content'      => $content,
            'content_type' => 'html',
        ]);

        return $book;
    }
}
