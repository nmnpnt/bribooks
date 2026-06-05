<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test Author',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'author',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user'  => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('password123'),
            'role'     => 'author',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'fail@example.com', 'password' => bcrypt('correct')]);

        $this->postJson('/api/auth/login', ['email' => 'fail@example.com', 'password' => 'wrong'])
             ->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user  = User::factory()->create(['role' => 'author']);
        $token = auth('api')->login($user);

        $this->withToken($token)
             ->getJson('/api/profile')
             ->assertStatus(200)
             ->assertJsonPath('user.email', $user->email);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
             ->postJson('/api/logout')
             ->assertStatus(200)
             ->assertJson(['message' => 'Logged out successfully.']);
    }
}
