<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_list_all_users()
    {
        $admin = User::factory()->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        $adminRole = Role::bySlug('admin')->first();
        $admin->update(['role_id' => $adminRole->id]);

        $users = User::factory()->count(3)->create(['status' => \App\Enums\User\UserStatus::Active->value]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        $this->assertCount(4, $response->json('data')); // 3 created + 1 admin
    }

    #[Test]
    public function manager_can_list_all_users()
    {
        $manager = User::factory()->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        $managerRole = Role::bySlug('manager')->first();
        $manager->update(['role_id' => $managerRole->id]);

        $users = User::factory()->count(2)->create(['status' => \App\Enums\User\UserStatus::Active->value]);

        $token = $this->authenticateUser($manager);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved successfully',
            ]);

        $this->assertCount(3, $response->json('data')); // 2 created + 1 manager
    }

    #[Test]
    public function regular_user_cannot_list_users()
    {
        $user = User::factory()->create();
        $userRole = Role::bySlug('user')->first();
        $user->update(['role_id' => $userRole->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient permissions',
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_list_users()
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function authenticated_user_can_view_own_profile()
    {
        $user = User::factory()->create();

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                ],
                'message' => 'User retrieved successfully',
            ]);
    }

    #[Test]
    public function authenticated_user_can_view_other_user_profile()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token = $this->authenticateUser($user1);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user2->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user2->id,
                    'first_name' => $user2->first_name,
                    'last_name' => $user2->last_name,
                    'email' => $user2->email,
                ],
                'message' => 'User retrieved successfully',
            ]);
    }

    #[Test]
    public function user_cannot_view_nonexistent_user()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/users/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found',
            ]);
    }

    #[Test]
    public function user_can_update_own_profile()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $token = $this->authenticateUser($user);
        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+1234567890',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => $user->email,
                    'phone' => '+1234567890',
                ],
                'message' => 'User updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+1234567890',
        ]);
    }

    #[Test]
    public function user_can_change_avatar_and_new_avatar_is_accessible()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'avatar' => 'avatars/old-avatar.jpg', // Pre-set avatar in database
        ]);

        $token = $this->authenticateUser($user);

        // Update user with new avatar
        $newAvatarFile = \Illuminate\Http\UploadedFile::fake()->create('new-avatar.png', 1000, 'image/png');
        $updateData = [
            'first_name' => 'Jane',
            'avatar' => $newAvatarFile,
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'first_name' => 'Jane',
                    'email' => $user->email,
                ],
                'message' => 'User updated successfully',
            ]);

        // Verify new avatar is accessible in the response
        $userData = $response->json('data');
        $this->assertNotNull($userData['avatar']);
        $this->assertStringStartsWith(config('app.url').'/storage/', $userData['avatar']);

        // Verify user was updated in database with new avatar (different from old one)
        $updatedUser = User::find($user->id);
        $this->assertEquals('Jane', $updatedUser->first_name);
        $this->assertNotNull($updatedUser->avatar);
        $this->assertNotEquals('avatars/old-avatar.jpg', $updatedUser->avatar); // Avatar should have changed
    }

    #[Test]
    public function admin_can_update_any_user_profile()
    {
        $admin = User::factory()->create();
        $adminRole = Role::bySlug('admin')->first();
        $admin->update(['role_id' => $adminRole->id]);

        $targetUser = User::factory()->create([
            'first_name' => 'John',
        ]);

        $token = $this->authenticateUser($admin);

        $updateData = [
            'first_name' => 'Updated',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'first_name' => 'Updated',
                ],
                'message' => 'User updated successfully',
            ]);
    }

    #[Test]
    public function user_cannot_update_other_user_profile()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token = $this->authenticateUser($user1);

        $updateData = [
            'first_name' => 'Hacked',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$user2->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You can only update your own profile',
            ]);
    }

    #[Test]
    public function user_update_validation_fails_with_invalid_data()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $invalidData = [
            'first_name' => '', // Required
            'email' => 'invalid-email', // Invalid format
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$user->id}", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'email']);
    }

    #[Test]
    public function user_cannot_update_profile_with_duplicate_email()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $token = $this->authenticateUser($user1);

        $updateData = [
            'email' => 'user2@example.com', // Duplicate
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$user1->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function unauthenticated_user_cannot_update_profile()
    {
        $user = User::factory()->create();
        $updateData = ['first_name' => 'Updated'];

        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function user_cannot_update_nonexistent_user()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $updateData = ['first_name' => 'Updated'];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson('/api/users/999', $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found',
            ]);
    }
}
