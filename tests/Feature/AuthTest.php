<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'P@ssword123',
            'password_confirmation' => 'P@ssword123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                ],
                'message' => 'Registration successful',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function user_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'password' => 'P@ssword123',
            'password_confirmation' => 'P@ssword123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function user_cannot_register_with_invalid_data()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('P@ssword123'),
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'P@ssword123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                ],
                'message' => 'Login successful',
            ]);
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('P@ssword123'),
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'WrongP@ss123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    #[Test]
    public function user_cannot_login_with_nonexistent_email()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'P@ssword123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    #[Test]
    public function authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'message' => 'User profile retrieved successfully',
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        // Verify token is invalidated
        $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/auth/user')
            ->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_refresh_token()
    {
        $user = User::factory()->create();
        $oldToken = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($oldToken))
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                ],
                'message' => 'Token refreshed successfully',
            ]);

        $newToken = $response->json('data.access_token');

        // Verify old token is invalidated (JWT blacklisting is enabled)
        $this->withHeaders($this->getAuthHeader($oldToken))
            ->getJson('/api/auth/user')
            ->assertStatus(401);

        // Verify new token works
        $this->withHeaders($this->getAuthHeader($newToken))
            ->getJson('/api/auth/user')
            ->assertStatus(200);
    }

    #[Test]
    public function unauthenticated_user_cannot_refresh_token()
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function login_validation_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function login_validation_fails_with_invalid_email()
    {
        $loginData = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function register_validation_fails_with_password_mismatch()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function register_validation_fails_with_weak_password()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function protected_route_requires_authentication()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function protected_route_allows_authenticated_user()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    #[Test]
    public function protected_route_rejects_invalid_token()
    {
        $invalidToken = 'invalid.jwt.token';

        $response = $this->withHeaders(['Authorization' => "Bearer {$invalidToken}"])
            ->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function protected_route_rejects_malformed_token()
    {
        $malformedToken = 'not-a-jwt-token-at-all';

        $response = $this->withHeaders(['Authorization' => "Bearer {$malformedToken}"])
            ->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function protected_route_rejects_expired_token()
    {
        // Create a user and get a token
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        // Manually expire the token by setting a past TTL
        $this->travel(config('jwt.ttl') + 1)->minutes();

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);

        // Reset config
        config(['jwt.ttl' => 60]);
    }

    #[Test]
    public function different_users_have_different_access()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $token1 = $this->authenticateUser($user1);
        $token2 = $this->authenticateUser($user2);

        // User 1 should see their own data
        $response1 = $this->withHeaders($this->getAuthHeader($token1))
            ->getJson('/api/auth/user');

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user1->id,
                    'email' => 'user1@example.com',
                ],
            ]);

        // User 2 should see their own data
        $response2 = $this->withHeaders($this->getAuthHeader($token2))
            ->getJson('/api/auth/user');

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user2->id,
                    'email' => 'user2@example.com',
                ],
            ]);

        // User 1 data should be different from User 2 data
        $this->assertNotEquals(
            $response1->json('data.id'),
            $response2->json('data.id')
        );
    }
}
