<?php

namespace Database\Factories;

use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chapter_id'   => Chapter::factory(),
            'content'      => '<p>' . implode('</p><p>', fake()->paragraphs(3)) . '</p>',
            'content_type' => 'html',
            'page_number'  => fake()->numberBetween(1, 200),
        ];
    }
}
