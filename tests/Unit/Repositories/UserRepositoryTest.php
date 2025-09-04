<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository();
    }

    #[Test]
    public function it_can_find_user_by_email()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $foundUser = $this->userRepository->findByEmail('john@example.com');

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals('john@example.com', $foundUser->email);
    }

    #[Test]
    public function it_returns_null_when_user_not_found_by_email()
    {
        $foundUser = $this->userRepository->findByEmail('nonexistent@example.com');

        $this->assertNull($foundUser);
    }

    #[Test]
    public function it_can_find_user_by_id()
    {
        $user = User::factory()->create();

        $foundUser = $this->userRepository->findById($user->id);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    #[Test]
    public function it_returns_null_when_user_not_found_by_id()
    {
        $foundUser = $this->userRepository->findById(999);

        $this->assertNull($foundUser);
    }

    #[Test]
    public function it_can_create_new_user()
    {
        $plainPassword = 'password123';
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => $plainPassword,
        ];

        $user = $this->userRepository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        // Password should be hashed automatically by the model
        $this->assertTrue(password_verify($plainPassword, $user->password));
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function it_can_update_user_attributes()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updated = $this->userRepository->update($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated@example.com', $user->fresh()->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    #[Test]
    public function it_returns_false_when_update_fails()
    {
        $user = User::factory()->create();

        // Delete the user to simulate update failure
        $user->delete();

        $updated = $this->userRepository->update($user, [
            'name' => 'Updated Name',
        ]);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_delete_user()
    {
        $user = User::factory()->create();

        $deleted = $this->userRepository->delete($user);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    #[Test]
    public function it_returns_false_when_delete_fails()
    {
        // Create a user instance that doesn't exist in the database
        $user = new User([
            'id' => 99999,
            'name' => 'Non-existent User',
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // This should return false since the user doesn't exist
        $deleted = $this->userRepository->delete($user);

        $this->assertFalse($deleted);
    }

    #[Test]
    public function it_can_get_all_users()
    {
        User::factory()->count(3)->create();

        $users = $this->userRepository->getAll();

        $this->assertCount(3, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_users_exist()
    {
        $users = $this->userRepository->getAll();

        $this->assertCount(0, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
    }

    #[Test]
    public function it_preserves_user_attributes_when_creating()
    {
        $plainPassword = 'testpassword';
        $emailVerifiedAt = now();
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $plainPassword,
            'email_verified_at' => $emailVerifiedAt,
        ];

        $user = $this->userRepository->create($userData);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        // Password should be hashed automatically
        $this->assertTrue(password_verify($plainPassword, $user->password));
    }

    #[Test]
    public function it_handles_mass_assignment_properly()
    {
        $plainPassword = 'testpass';
        $userData = [
            'name' => 'Mass Assignment Test',
            'email' => 'mass@test.com',
            'password' => $plainPassword,
        ];

        $user = $this->userRepository->create($userData);

        $this->assertEquals('Mass Assignment Test', $user->name);
        $this->assertEquals('mass@test.com', $user->email);
        // Verify password was hashed
        $this->assertTrue(password_verify($plainPassword, $user->password));
    }

    #[Test]
    public function it_only_updates_specified_attributes()
    {
        $originalEmail = 'original@example.com';
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => $originalEmail,
        ]);

        $this->userRepository->update($user, [
            'name' => 'Updated Name',
        ]);

        $updatedUser = $user->fresh();
        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals($originalEmail, $updatedUser->email);
    }
}
