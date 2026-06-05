<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    public function definition(): array
    {
        static $order = 0;
        return [
            'book_id'     => Book::factory(),
            'title'       => 'Chapter ' . fake()->numberBetween(1, 50) . ': ' . fake()->sentence(3),
            'description' => fake()->sentence(),
            'order'       => ++$order,
        ];
    }
}
