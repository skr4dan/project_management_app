<?php

namespace App\Repositories;

use App\DTOs\Role\RoleDTO;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role Repository Implementation
 *
 * Handles all role-related database operations using DTOs.
 * Follows SOLID principles with single responsibility focus.
 */
class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Create a new role repository instance
     *
     * @param Role $role
     */
    public function __construct(
        private Role $role
    ) {}

    /**
     * Find role by ID
     *
     * @param int $id
     * @return Role|null
     */
    public function find(int $id): ?Role
    {
        return $this->role->find($id);
    }

    /**
     * Find role by ID
     *
     * @param int $id
     * @return Role|null
     */
    public function findById(int $id): ?Role
    {
        return $this->find($id);
    }

    /**
     * Find role by slug
     *
     * @param string $slug
     * @return Role|null
     */
    public function findBySlug(string $slug): ?Role
    {
        return $this->role->where('slug', $slug)->first();
    }

    /**
     * Get active roles only
     *
     * @return Collection<int, Role>
     */
    public function getActiveRoles(): Collection
    {
        return $this->role->where('is_active', true)->get();
    }

    /**
     * Create role from DTO
     *
     * @param RoleDTO $roleDTO
     * @return Role
     */
    public function createFromDTO(RoleDTO $roleDTO): Role
    {
        return $this->role->create($roleDTO->toModelArray());
    }

    /**
     * Update role from DTO
     *
     * @param int $id
     * @param RoleDTO $roleDTO
     * @return bool
     */
    public function updateFromDTO(int $id, RoleDTO $roleDTO): bool
    {
        $role = $this->find($id);
        return $role ? $role->update($roleDTO->toModelArray()) : false;
    }

    /**
     * Update role permissions
     *
     * @param int $id
     * @param array<int, string> $permissions
     * @return bool
     */
    public function updatePermissions(int $id, array $permissions): bool
    {
        $role = $this->find($id);
        return $role ? $role->update(['permissions' => $permissions]) : false;
    }

    /**
     * Add permission to role
     *
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public function addPermission(int $roleId, string $permission): bool
    {
        $role = $this->find($roleId);

        if (!$role) {
            return false;
        }

        $permissions = $role->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            return $this->updatePermissions($roleId, $permissions);
        }

        return true;
    }

    /**
     * Remove permission from role
     *
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public function removePermission(int $roleId, string $permission): bool
    {
        $role = $this->find($roleId);

        if (!$role) {
            return false;
        }

        $permissions = $role->permissions ?? [];
        $permissions = array_diff($permissions, [$permission]);

        return $this->updatePermissions($roleId, array_values($permissions));
    }

    /**
     * Check if role has permission
     *
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public function hasPermission(int $roleId, string $permission): bool
    {
        $role = $this->find($roleId);

        if (!$role) {
            return false;
        }

        return $role->hasPermission($permission);
    }

    /**
     * Get users count for role
     *
     * @param int $roleId
     * @return int
     */
    public function getUsersCount(int $roleId): int
    {
        $role = $this->find($roleId);

        return $role ? $role->users()->count() : 0;
    }

    /**
     * Activate role
     *
     * @param int $id
     * @return bool
     */
    public function activate(int $id): bool
    {
        $role = $this->find($id);
        return $role ? $role->update(['is_active' => true]) : false;
    }

    /**
     * Deactivate role
     *
     * @param int $id
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        $role = $this->find($id);
        return $role ? $role->update(['is_active' => false]) : false;
    }
}
