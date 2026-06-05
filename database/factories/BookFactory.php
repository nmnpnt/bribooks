<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory()->author(),
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'genre'       => fake()->randomElement(['Fiction', 'Non-Fiction', 'Adventure', 'Romance', 'Sci-Fi']),
            'language'    => 'en',
            'status'      => 'draft',
        ];
    }

    public function draft(): static      { return $this->state(['status' => 'draft']); }
    public function submitted(): static  { return $this->state(['status' => 'submitted']); }
    public function approved(): static   { return $this->state(['status' => 'approved']); }
    public function published(): static  { return $this->state(['status' => 'published']); }
    public function rejected(): static   { return $this->state(['status' => 'rejected']); }
}
