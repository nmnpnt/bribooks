<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $reviewer;
    private User $admin;
    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author   = User::factory()->create(['role' => 'author']);
        $this->reviewer = User::factory()->create(['role' => 'reviewer']);
        $this->admin    = User::factory()->create(['role' => 'admin']);

        $this->book = Book::factory()->create([
            'user_id' => $this->author->id,
            'status'  => 'draft',
        ]);

        $chapter = Chapter::factory()->create(['book_id' => $this->book->id]);
        Page::factory()->create(['chapter_id' => $chapter->id]);
    }

    // ---------------------------------------------------------------
    // Submit

    public function test_author_can_submit_book_that_passes_moderation(): void
    {
        // Mock moderation to always pass
        $this->mockModerationPass();

        $token = auth('api')->login($this->author);
        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/submit")
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'submitted');
    }

    public function test_submit_fails_when_moderation_flags_content(): void
    {
        $this->instance(ModerationService::class, Mockery::mock(ModerationService::class, function ($m) {
            $m->shouldReceive('moderate')->andReturn([
                'result'        => 'flagged',
                'flagged_items' => [['type' => 'profanity', 'word' => 'test']],
            ]);
        }));

        $token = auth('api')->login($this->author);
        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/submit")
             ->assertStatus(422)
             ->assertJsonStructure(['message', 'flagged_items']);
    }

    public function test_reviewer_cannot_submit_book(): void
    {
        $this->mockModerationPass();
        $token = auth('api')->login($this->reviewer);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/submit")
             ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Approve

    public function test_reviewer_can_approve_submitted_book(): void
    {
        $this->book->update(['status' => 'submitted']);
        $token = auth('api')->login($this->reviewer);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/approve")
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'approved');
    }

    public function test_author_cannot_approve_book(): void
    {
        $this->book->update(['status' => 'submitted']);
        $token = auth('api')->login($this->author);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/approve")
             ->assertStatus(403);
    }

    public function test_cannot_approve_draft_book(): void
    {
        $token = auth('api')->login($this->reviewer);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/approve")
             ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Reject

    public function test_reviewer_can_reject_submitted_book(): void
    {
        $this->book->update(['status' => 'submitted']);
        $token = auth('api')->login($this->reviewer);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/reject", ['reason' => 'Content needs major revision.'])
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'rejected');
    }

    public function test_reject_requires_reason(): void
    {
        $this->book->update(['status' => 'submitted']);
        $token = auth('api')->login($this->reviewer);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/reject", [])
             ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Publish

    public function test_admin_can_publish_approved_book(): void
    {
        $this->book->update(['status' => 'approved']);
        $token = auth('api')->login($this->admin);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/publish")
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'published');
    }

    public function test_reviewer_cannot_publish_book(): void
    {
        $this->book->update(['status' => 'approved']);
        $token = auth('api')->login($this->reviewer);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/publish")
             ->assertStatus(403);
    }

    public function test_cannot_publish_non_approved_book(): void
    {
        // Still in draft
        $token = auth('api')->login($this->admin);

        $this->withToken($token)
             ->postJson("/api/books/{$this->book->id}/publish")
             ->assertStatus(422);
    }

    public function test_published_book_is_read_only(): void
    {
        $this->book->update(['status' => 'published']);
        $token = auth('api')->login($this->author);

        $this->withToken($token)
             ->putJson("/api/books/{$this->book->id}", ['title' => 'Attempted Edit'])
             ->assertStatus(403);
    }

    // ---------------------------------------------------------------

    private function mockModerationPass(): void
    {
        $this->instance(ModerationService::class, Mockery::mock(ModerationService::class, function ($m) {
            $m->shouldReceive('moderate')->andReturn([
                'result'        => 'passed',
                'flagged_items' => [],
            ]);
        }));
    }
}
