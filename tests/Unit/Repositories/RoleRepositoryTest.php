<?php

namespace Tests\Unit\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RoleRepository $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleRepository = new RoleRepository(new \App\Models\Role());
    }

    #[Test]
    public function it_can_find_role_by_id()
    {
        $role = Role::factory()->create();

        $foundRole = $this->roleRepository->findById($role->id);

        $this->assertInstanceOf(Role::class, $foundRole);
        $this->assertEquals($role->id, $foundRole->id);
    }

    #[Test]
    public function it_returns_null_when_role_not_found_by_id()
    {
        $foundRole = $this->roleRepository->findById(999);

        $this->assertNull($foundRole);
    }

    #[Test]
    public function it_can_find_role_by_slug()
    {
        $role = Role::factory()->create(['slug' => 'admin-role']);

        $foundRole = $this->roleRepository->findBySlug('admin-role');

        $this->assertInstanceOf(Role::class, $foundRole);
        $this->assertEquals('admin-role', $foundRole->slug);
    }

    #[Test]
    public function it_returns_null_when_role_not_found_by_slug()
    {
        $foundRole = $this->roleRepository->findBySlug('non-existent-slug');

        $this->assertNull($foundRole);
    }

    #[Test]
    public function it_can_get_active_roles()
    {
        // Create roles with explicit inactive status first to avoid factory randomness
        Role::factory()->count(2)->create(['is_active' => false]);
        Role::factory()->count(3)->create(['is_active' => true]);

        $roles = $this->roleRepository->getActiveRoles();

        $this->assertGreaterThanOrEqual(3, $roles->count());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $roles);
        $roles->each(function ($role) {
            $this->assertTrue($role->is_active);
        });
    }

    #[Test]
    public function it_returns_empty_collection_when_no_active_roles_exist()
    {
        // Delete all existing roles first
        Role::query()->delete();

        // Ensure no active roles exist by explicitly setting all to inactive
        Role::factory()->count(3)->create(['is_active' => false]);

        $roles = $this->roleRepository->getActiveRoles();

        $this->assertCount(0, $roles);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $roles);
    }

    #[Test]
    public function it_can_create_role_from_dto()
    {
        $roleDTO = new \App\DTOs\Role\RoleDTO(
            id: null,
            slug: 'test-role',
            name: 'Test Role',
            permissions: ['users.view', 'users.create'],
            is_active: true,
            created_at: null,
            updated_at: null
        );

        $role = $this->roleRepository->createFromDTO($roleDTO);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Test Role', $role->name);
        $this->assertEquals('test-role', $role->slug);
        $this->assertEquals(['users.view', 'users.create'], $role->permissions);
        $this->assertTrue($role->is_active);
        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
            'slug' => 'test-role',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_update_role_from_dto()
    {
        $role = Role::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
            'permissions' => ['users.view'],
            'is_active' => true,
        ]);

        $roleDTO = new \App\DTOs\Role\RoleDTO(
            id: $role->id,
            slug: 'updated-slug',
            name: 'Updated Name',
            permissions: ['users.view', 'users.create'],
            is_active: false,
            created_at: $role->created_at,
            updated_at: $role->updated_at
        );

        $updated = $this->roleRepository->updateFromDTO($role->id, $roleDTO);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Name', $role->fresh()->name);
        $this->assertEquals('updated-slug', $role->fresh()->slug);
        $this->assertEquals(['users.view', 'users.create'], $role->fresh()->permissions);
        $this->assertFalse($role->fresh()->is_active);
    }

    #[Test]
    public function it_returns_false_when_update_from_dto_fails()
    {
        $roleDTO = new \App\DTOs\Role\RoleDTO(
            id: null,
            slug: 'test-role',
            name: 'Test Role',
            permissions: ['users.view'],
            is_active: true,
            created_at: null,
            updated_at: null
        );

        $updated = $this->roleRepository->updateFromDTO(999, $roleDTO);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_update_role_permissions()
    {
        $role = Role::factory()->create(['permissions' => ['users.view']]);
        $newPermissions = ['users.view', 'users.create', 'users.edit'];

        $updated = $this->roleRepository->updatePermissions($role->id, $newPermissions);

        $this->assertTrue($updated);
        $this->assertEquals($newPermissions, $role->fresh()->permissions);
    }

    #[Test]
    public function it_returns_false_when_update_permissions_fails()
    {
        $updated = $this->roleRepository->updatePermissions(999, ['users.view']);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_add_permission_to_role()
    {
        $role = Role::factory()->create(['permissions' => ['users.view']]);

        $added = $this->roleRepository->addPermission($role->id, 'users.create');

        $this->assertTrue($added);
        $this->assertContains('users.create', $role->fresh()->permissions);
        $this->assertContains('users.view', $role->fresh()->permissions);
    }

    #[Test]
    public function it_returns_false_when_add_permission_fails()
    {
        $added = $this->roleRepository->addPermission(999, 'users.create');

        $this->assertFalse($added);
    }

    #[Test]
    public function it_does_not_add_duplicate_permissions()
    {
        $role = Role::factory()->create(['permissions' => ['users.view']]);

        $added = $this->roleRepository->addPermission($role->id, 'users.view');

        $this->assertTrue($added);
        $freshPermissions = $role->fresh()->permissions;
        $this->assertCount(1, array_filter($freshPermissions, fn($p) => $p === 'users.view'));
    }

    #[Test]
    public function it_can_remove_permission_from_role()
    {
        $role = Role::factory()->create(['permissions' => ['users.view', 'users.create']]);

        $removed = $this->roleRepository->removePermission($role->id, 'users.create');

        $this->assertTrue($removed);
        $this->assertContains('users.view', $role->fresh()->permissions);
        $this->assertNotContains('users.create', $role->fresh()->permissions);
    }

    #[Test]
    public function it_returns_false_when_remove_permission_fails()
    {
        $removed = $this->roleRepository->removePermission(999, 'users.create');

        $this->assertFalse($removed);
    }

    #[Test]
    public function it_returns_true_when_removing_non_existent_permission()
    {
        $role = Role::factory()->create(['permissions' => ['users.view']]);

        $removed = $this->roleRepository->removePermission($role->id, 'users.create');

        $this->assertTrue($removed); // Repository returns true even if permission wasn't present
        $this->assertContains('users.view', $role->fresh()->permissions);
    }

    #[Test]
    public function it_can_check_if_role_has_permission()
    {
        $role = Role::factory()->create(['permissions' => ['users.view', 'users.create']]);

        $this->assertTrue($this->roleRepository->hasPermission($role->id, 'users.view'));
        $this->assertTrue($this->roleRepository->hasPermission($role->id, 'users.create'));
        $this->assertFalse($this->roleRepository->hasPermission($role->id, 'users.edit'));
    }

    #[Test]
    public function it_returns_false_when_checking_permission_for_non_existent_role()
    {
        $hasPermission = $this->roleRepository->hasPermission(999, 'users.view');

        $this->assertFalse($hasPermission);
    }

    #[Test]
    public function it_can_get_users_count_for_role()
    {
        $role = Role::factory()->create();
        User::factory()->count(3)->create(['role_id' => $role->id]);
        User::factory()->count(2)->create(); // Different role

        $count = $this->roleRepository->getUsersCount($role->id);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_returns_zero_users_count_for_role_with_no_users()
    {
        $role = Role::factory()->create();

        $count = $this->roleRepository->getUsersCount($role->id);

        $this->assertEquals(0, $count);
    }

    #[Test]
    public function it_can_activate_role()
    {
        $role = Role::factory()->create(['is_active' => false]);

        $activated = $this->roleRepository->activate($role->id);

        $this->assertTrue($activated);
        $this->assertTrue($role->fresh()->is_active);
    }

    #[Test]
    public function it_returns_false_when_activate_fails()
    {
        $activated = $this->roleRepository->activate(999);

        $this->assertFalse($activated);
    }

    #[Test]
    public function it_can_deactivate_role()
    {
        $role = Role::factory()->create(['is_active' => true]);

        $deactivated = $this->roleRepository->deactivate($role->id);

        $this->assertTrue($deactivated);
        $this->assertFalse($role->fresh()->is_active);
    }

    #[Test]
    public function it_returns_false_when_deactivate_fails()
    {
        $deactivated = $this->roleRepository->deactivate(999);

        $this->assertFalse($deactivated);
    }
}
