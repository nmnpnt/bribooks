<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed test users (one per role)
        $admin = User::firstOrCreate(
            ['email' => 'admin@bribooks.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        $reviewer = User::firstOrCreate(
            ['email' => 'reviewer@bribooks.com'],
            ['name' => 'Reviewer User', 'password' => Hash::make('password'), 'role' => 'reviewer']
        );

        $author = User::firstOrCreate(
            ['email' => 'author@bribooks.com'],
            ['name' => 'Author User', 'password' => Hash::make('password'), 'role' => 'author']
        );

        // Seed a sample book with chapters and pages
        $book = Book::firstOrCreate(
            ['title' => 'My First Adventure', 'user_id' => $author->id],
            [
                'description' => 'A thrilling story about exploration.',
                'genre'       => 'Adventure',
                'language'    => 'en',
                'status'      => 'draft',
            ]
        );

        $chapter = Chapter::firstOrCreate(
            ['book_id' => $book->id, 'title' => 'Chapter 1: The Beginning'],
            ['description' => 'Where it all starts.', 'order' => 1]
        );

        Page::firstOrCreate(
            ['chapter_id' => $chapter->id, 'page_number' => 1],
            [
                'content'      => '<p>It was a bright morning when the hero set off on their journey...</p>',
                'content_type' => 'html',
            ]
        );

        $this->command->info('✅ Seeded: admin@bribooks.com | reviewer@bribooks.com | author@bribooks.com (password: password)');
    }
}
