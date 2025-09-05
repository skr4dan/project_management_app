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

        $this->userRepository = new UserRepository(new User());
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
    public function it_can_find_users_by_role()
    {
        $role = \App\Models\Role::factory()->create();
        User::factory()->count(2)->create(['role_id' => $role->id]);
        User::factory()->count(1)->create(); // Different role

        $users = $this->userRepository->getByRole($role->id);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
        $users->each(function ($user) use ($role) {
            $this->assertEquals($role->id, $user->role_id);
        });
    }

    #[Test]
    public function it_can_find_users_by_status()
    {
        User::factory()->count(3)->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        User::factory()->count(2)->create(['status' => \App\Enums\User\UserStatus::Inactive->value]);

        $users = $this->userRepository->getByStatus(\App\Enums\User\UserStatus::Active);

        $this->assertCount(3, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
        $users->each(function ($user) {
            $this->assertEquals(\App\Enums\User\UserStatus::Active, $user->status);
        });
    }

    #[Test]
    public function it_can_get_active_users()
    {
        User::factory()->count(4)->create(['status' => \App\Enums\User\UserStatus::Active->value]);
        User::factory()->count(2)->create(['status' => \App\Enums\User\UserStatus::Inactive->value]);

        $users = $this->userRepository->getActiveUsers();

        $this->assertCount(4, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
        $users->each(function ($user) {
            $this->assertEquals(\App\Enums\User\UserStatus::Active, $user->status);
        });
    }

    #[Test]
    public function it_can_create_user_from_dto()
    {
        $role = \App\Models\Role::factory()->create();
        $plainPassword = 'password123';

        $userDTO = new \App\DTOs\User\UserDTO(
            id: null,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            password: $plainPassword,
            role_id: $role->id,
            status: \App\Enums\User\UserStatus::Active,
            avatar: null,
            phone: null,
            remember_token: null,
            created_at: null,
            updated_at: null
        );

        $user = $this->userRepository->createFromDTO($userDTO);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->first_name);
        $this->assertEquals('Doe', $user->last_name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(\App\Enums\User\UserStatus::Active, $user->status);
        $this->assertEquals($role->id, $user->role_id);
        $this->assertTrue(password_verify($plainPassword, $user->password));
    }

    #[Test]
    public function it_can_update_user_from_dto()
    {
        $user = User::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
        ]);

        $userDTO = new \App\DTOs\User\UserDTO(
            id: $user->id,
            first_name: 'Updated',
            last_name: 'Name',
            email: 'updated@example.com',
            password: 'newpassword',
            role_id: $user->role_id,
            status: \App\Enums\User\UserStatus::Inactive,
            avatar: $user->avatar,
            phone: $user->phone,
            remember_token: $user->remember_token,
            created_at: $user->created_at,
            updated_at: $user->updated_at
        );

        $updated = $this->userRepository->updateFromDTO($user->id, $userDTO);

        $this->assertTrue($updated);
        $this->assertEquals('Updated', $user->fresh()->first_name);
        $this->assertEquals('Name', $user->fresh()->last_name);
        $this->assertEquals('updated@example.com', $user->fresh()->email);
        $this->assertEquals(\App\Enums\User\UserStatus::Inactive, $user->fresh()->status);
        $this->assertTrue(password_verify('newpassword', $user->fresh()->password));
    }

    #[Test]
    public function it_returns_false_when_update_from_dto_fails()
    {
        $userDTO = new \App\DTOs\User\UserDTO(
            id: null,
            first_name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'password',
            role_id: 1,
            status: \App\Enums\User\UserStatus::Active,
            avatar: null,
            phone: null,
            remember_token: null,
            created_at: null,
            updated_at: null
        );

        $updated = $this->userRepository->updateFromDTO(999, $userDTO);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_search_users()
    {
        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        User::factory()->create(['email' => 'jane@example.com']);
        User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Smith'
        ]);

        $results = $this->userRepository->search('John');

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->first_name);
        $this->assertEquals('Doe', $results->first()->last_name);
    }

    #[Test]
    public function it_can_update_user_status()
    {
        $user = User::factory()->create(['status' => \App\Enums\User\UserStatus::Active->value]);

        $updated = $this->userRepository->updateStatus($user->id, \App\Enums\User\UserStatus::Blocked);

        $this->assertTrue($updated);
        $this->assertEquals(\App\Enums\User\UserStatus::Blocked, $user->fresh()->status);
    }

    #[Test]
    public function it_returns_false_when_update_status_fails()
    {
        $updated = $this->userRepository->updateStatus(999, \App\Enums\User\UserStatus::Active);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_assign_role_to_user()
    {
        $user = User::factory()->create(['role_id' => null]);
        $role = \App\Models\Role::factory()->create();

        $assigned = $this->userRepository->assignRole($user->id, $role->id);

        $this->assertTrue($assigned);
        $this->assertEquals($role->id, $user->fresh()->role_id);
    }

    #[Test]
    public function it_returns_false_when_assign_role_fails()
    {
        $assigned = $this->userRepository->assignRole(999, 1);

        $this->assertFalse($assigned);
    }

    #[Test]
    public function it_can_remove_role_from_user()
    {
        $role = \App\Models\Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $removed = $this->userRepository->removeRole($user->id);

        $this->assertTrue($removed);
        $this->assertNull($user->fresh()->role_id);
    }

    #[Test]
    public function it_returns_false_when_remove_role_fails()
    {
        $removed = $this->userRepository->removeRole(999);

        $this->assertFalse($removed);
    }
}
