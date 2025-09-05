<?php

namespace App\Repositories\Contracts;

use App\DTOs\Role\RoleDTO;

/**
 * Role Repository Interface
 *
 * Defines methods for role data access operations.
 */
interface RoleRepositoryInterface
{
    /**
     * Find role by ID
     *
     * @param int $id
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Find role by slug
     *
     * @param string $slug
     * @return mixed
     */
    public function findBySlug(string $slug);

    /**
     * Get active roles only
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveRoles();

    /**
     * Create role from DTO
     *
     * @param RoleDTO $roleDTO
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\Role\RoleDTO $roleDTO);

    /**
     * Update role from DTO
     *
     * @param int $id
     * @param RoleDTO $roleDTO
     * @return bool
     */
    public function updateFromDTO(int $id, \App\DTOs\Role\RoleDTO $roleDTO): bool;

    /**
     * Update role permissions
     *
     * @param int $id
     * @param array<int, string> $permissions
     * @return bool
     */
    public function updatePermissions(int $id, array $permissions): bool;

    /**
     * Add permission to role
     *
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public function addPermission(int $roleId, string $permission): bool;

    /**
     * Remove permission from role
     *
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public function removePermission(int $roleId, string $permission): bool;

    /**
     * Check if role has permission
     *
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public function hasPermission(int $roleId, string $permission): bool;

    /**
     * Get users count for role
     *
     * @param int $roleId
     * @return int
     */
    public function getUsersCount(int $roleId): int;

    /**
     * Activate role
     *
     * @param int $id
     * @return bool
     */
    public function activate(int $id): bool;

    /**
     * Deactivate role
     *
     * @param int $id
     * @return bool
     */
    public function deactivate(int $id): bool;
}
