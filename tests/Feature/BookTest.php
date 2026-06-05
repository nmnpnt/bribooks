<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $otherAuthor;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author      = User::factory()->create(['role' => 'author']);
        $this->otherAuthor = User::factory()->create(['role' => 'author']);
        $this->token       = auth('api')->login($this->author);
    }

    public function test_author_can_create_book(): void
    {
        $this->withToken($this->token)
             ->postJson('/api/books', [
                 'title'       => 'My Great Book',
                 'description' => 'A wonderful story.',
                 'genre'       => 'Fiction',
             ])
             ->assertStatus(201)
             ->assertJsonPath('data.title', 'My Great Book')
             ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('books', ['title' => 'My Great Book', 'user_id' => $this->author->id]);
    }

    public function test_author_can_list_their_books(): void
    {
        Book::factory()->count(3)->create(['user_id' => $this->author->id]);
        Book::factory()->count(2)->create(['user_id' => $this->otherAuthor->id]);

        $response = $this->withToken($this->token)->getJson('/api/books');
        $response->assertStatus(200);

        // Author should only see their own 3 books
        $this->assertCount(3, $response->json('data'));
    }

    public function test_author_can_update_their_own_draft_book(): void
    {
        $book = Book::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);

        $this->withToken($this->token)
             ->putJson("/api/books/{$book->id}", ['title' => 'Updated Title'])
             ->assertStatus(200)
             ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_author_cannot_update_another_authors_book(): void
    {
        $book = Book::factory()->create(['user_id' => $this->otherAuthor->id]);

        $this->withToken($this->token)
             ->putJson("/api/books/{$book->id}", ['title' => 'Hacked Title'])
             ->assertStatus(403);
    }

    public function test_author_cannot_edit_published_book(): void
    {
        $book = Book::factory()->create(['user_id' => $this->author->id, 'status' => 'published']);

        $this->withToken($this->token)
             ->putJson("/api/books/{$book->id}", ['title' => 'New Title'])
             ->assertStatus(403);
    }

    public function test_author_can_delete_draft_book(): void
    {
        $book = Book::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);

        $this->withToken($this->token)
             ->deleteJson("/api/books/{$book->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }

    public function test_non_author_cannot_create_book(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $token    = auth('api')->login($reviewer);

        $this->withToken($token)
             ->postJson('/api/books', ['title' => 'Reviewer Book'])
             ->assertStatus(403);
    }
}
