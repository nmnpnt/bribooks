<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private Book $book;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['role' => 'author']);
        $this->token  = auth('api')->login($this->author);

        $this->book = Book::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);

        $chapter = Chapter::factory()->create(['book_id' => $this->book->id, 'order' => 1]);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1]);
    }

    public function test_author_can_create_version_snapshot(): void
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/books/{$this->book->id}/versions", [
                'label'        => 'v1.0',
                'change_notes' => 'Initial draft snapshot.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.label', 'v1.0');

        $this->assertDatabaseHas('book_versions', [
            'book_id'        => $this->book->id,
            'version_number' => 1,
        ]);
    }

    public function test_version_numbers_increment_sequentially(): void
    {
        $this->withToken($this->token)->postJson("/api/books/{$this->book->id}/versions", []);
        $this->withToken($this->token)->postJson("/api/books/{$this->book->id}/versions", []);

        $r3 = $this->withToken($this->token)
            ->postJson("/api/books/{$this->book->id}/versions", []);

        $r3->assertJsonPath('data.version_number', 3);
    }

    public function test_snapshot_contains_chapters_and_pages(): void
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/books/{$this->book->id}/versions", []);

        $snapshot = $response->json('data.snapshot');

        $this->assertArrayHasKey('metadata', $snapshot);
        $this->assertArrayHasKey('chapters', $snapshot);
        $this->assertNotEmpty($snapshot['chapters']);
        $this->assertNotEmpty($snapshot['chapters'][0]['pages']);
    }

    public function test_author_can_list_versions(): void
    {
        $this->withToken($this->token)->postJson("/api/books/{$this->book->id}/versions", []);
        $this->withToken($this->token)->postJson("/api/books/{$this->book->id}/versions", []);

        $this->withToken($this->token)
             ->getJson("/api/books/{$this->book->id}/versions")
             ->assertStatus(200)
             ->assertJsonPath('meta.total', 2);
    }

    public function test_author_can_restore_from_snapshot(): void
    {
        // Create snapshot at v1
        $versionResp = $this->withToken($this->token)
            ->postJson("/api/books/{$this->book->id}/versions", ['label' => 'v1']);

        $versionId = $versionResp->json('data.id');

        // Change the book title after snapshot
        $this->book->update(['title' => 'Changed After Snapshot']);

        // Restore from v1
        $this->withToken($this->token)
             ->postJson("/api/books/{$this->book->id}/versions/{$versionId}/restore")
             ->assertStatus(200);

        // Verify title was rolled back
        $this->book->refresh();
        $this->assertNotEquals('Changed After Snapshot', $this->book->title);
    }
}
