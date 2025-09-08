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
        $user = User::factory()->regularUser()->create();
        $userRole = Role::bySlug('user')->first();
        $user->update(['role_id' => $userRole->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to view users.',
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
        $user = User::factory()->regularUser()->create();

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
        $user1 = User::factory()->regularUser()->create();
        $user2 = User::factory()->regularUser()->create();

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
        $user = User::factory()->regularUser()->create();
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
        $admin = User::factory()->regularUser()->create();
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
        $user1 = User::factory()->regularUser()->create();
        $user2 = User::factory()->regularUser()->create();

        $token = $this->authenticateUser($user1);

        $updateData = [
            'first_name' => 'Hacked',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/users/{$user2->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You can only update your own profile.',
            ]);
    }

    #[Test]
    public function user_update_validation_fails_with_invalid_data()
    {
        $user = User::factory()->regularUser()->create();
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
        $user = User::factory()->regularUser()->create();
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
        $user = User::factory()->regularUser()->create();
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

    #[Test]
    public function user_resource_returns_correct_structure_with_role_loaded()
    {
        $user = User::factory()->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        $adminRole = Role::bySlug('admin')->first();
        $user->update(['role_id' => $adminRole->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role' => [
                        'id',
                        'slug',
                        'name',
                    ],
                    'status',
                    'avatar',
                    'phone',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify the actual data structure
        $data = $response->json('data');
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals($user->first_name, $data['first_name']);
        $this->assertEquals($user->last_name, $data['last_name']);
        $this->assertEquals($user->email, $data['email']);
        $this->assertEquals($user->status->value, $data['status']);

        // Verify role relationship
        $this->assertEquals($adminRole->id, $data['role']['id']);
        $this->assertEquals($adminRole->slug, $data['role']['slug']);
        $this->assertEquals($adminRole->name, $data['role']['name']);
    }

    #[Test]
    public function user_resource_returns_correct_structure_with_no_role_loaded()
    {
        $user = User::factory()->create([
            'status' => \App\Enums\User\UserStatus::Active->value,
            'role_id' => null, // No role assigned
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role', // Should be null when not loaded
                    'status',
                    'avatar',
                    'phone',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify the actual data structure
        $data = $response->json('data');
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals($user->first_name, $data['first_name']);
        $this->assertEquals($user->last_name, $data['last_name']);
        $this->assertEquals($user->email, $data['email']);
        $this->assertEquals($user->status->value, $data['status']);

        // Verify role relationship is null when not loaded
        $this->assertNull($data['role']);
    }

    #[Test]
    public function user_resource_handles_null_avatar_correctly()
    {
        $user = User::factory()->create([
            'status' => \App\Enums\User\UserStatus::Active->value,
            'avatar' => null,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNull($data['avatar']);
    }

    #[Test]
    public function user_resource_handles_present_avatar_correctly()
    {
        $user = User::factory()->create([
            'status' => \App\Enums\User\UserStatus::Active->value,
            'avatar' => 'avatars/test.jpg',
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(asset('storage/avatars/test.jpg'), $data['avatar']);
    }

    #[Test]
    public function user_list_resource_returns_correct_structure_with_roles()
    {
        $admin = User::factory()->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        $adminRole = Role::bySlug('admin')->first();
        $admin->update(['role_id' => $adminRole->id]);

        $manager = User::factory()->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        $managerRole = Role::bySlug('manager')->first();
        $manager->update(['role_id' => $managerRole->id]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role' => [
                            'id',
                            'slug',
                            'name',
                        ],
                        'status',
                        'avatar',
                        'phone',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        // Verify that each user has a role loaded
        $users = $response->json('data');
        foreach ($users as $userData) {
            $this->assertArrayHasKey('role', $userData);
            $this->assertIsArray($userData['role']);
            $this->assertArrayHasKey('id', $userData['role']);
            $this->assertArrayHasKey('slug', $userData['role']);
            $this->assertArrayHasKey('name', $userData['role']);
        }

        // Find the admin user and verify their role
        $adminData = collect($users)->firstWhere('id', $admin->id);
        $this->assertEquals($adminRole->id, $adminData['role']['id']);
        $this->assertEquals($adminRole->slug, $adminData['role']['slug']);
        $this->assertEquals($adminRole->name, $adminData['role']['name']);

        // Find the manager user and verify their role
        $managerData = collect($users)->firstWhere('id', $manager->id);
        $this->assertEquals($managerRole->id, $managerData['role']['id']);
        $this->assertEquals($managerRole->slug, $managerData['role']['slug']);
        $this->assertEquals($managerRole->name, $managerData['role']['name']);
    }

    #[Test]
    public function user_resource_handles_null_phone_correctly()
    {
        $user = User::factory()->create([
            'status' => \App\Enums\User\UserStatus::Active->value,
            'phone' => null,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNull($data['phone']);
    }

    #[Test]
    public function user_resource_handles_present_phone_correctly()
    {
        $user = User::factory()->create([
            'status' => \App\Enums\User\UserStatus::Active->value,
            'phone' => '+1-555-123-4567',
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('+1-555-123-4567', $data['phone']);
    }
}
