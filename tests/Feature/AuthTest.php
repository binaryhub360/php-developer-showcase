<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_succeeds(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    public function test_duplicate_email_registration_fails(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $payload = [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_succeeds_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => Hash::make('SecretPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'loginuser@example.com',
            'password' => 'SecretPassword123!',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'loginuser@example.com',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => ['token', 'token_type'],
            ]);
    }

    public function test_login_fails_with_incorrect_credentials(): void
    {
        User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => Hash::make('SecretPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'loginuser@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_authenticated_user_can_retrieve_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Profile User',
                    'email' => 'profile@example.com',
                ],
            ]);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->postJson('/api/v1/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_protected_endpoints_reject_unauthenticated_users(): void
    {
        $endpoints = [
            ['method' => 'getJson', 'url' => '/api/v1/me'],
            ['method' => 'postJson', 'url' => '/api/v1/logout'],
            ['method' => 'getJson', 'url' => '/api/v1/projects'],
            ['method' => 'postJson', 'url' => '/api/v1/projects'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint['method'];
            $response = $this->$method($endpoint['url']);
            $response->assertStatus(Response::HTTP_UNAUTHORIZED)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ]);
        }
    }
}
