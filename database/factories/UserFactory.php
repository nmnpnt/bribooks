<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'author',
            'remember_token'    => Str::random(10),
        ];
    }

    public function author(): static
    {
        return $this->state(['role' => 'author']);
    }

    public function reviewer(): static
    {
        return $this->state(['role' => 'reviewer']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }
}
