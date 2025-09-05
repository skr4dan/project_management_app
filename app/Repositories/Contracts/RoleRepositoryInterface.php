<?php

namespace App\Repositories\Contracts;

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
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Find role by slug
     *
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
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\Role\RoleDTO $roleDTO);

    /**
     * Update role from DTO
     */
    public function updateFromDTO(int $id, \App\DTOs\Role\RoleDTO $roleDTO): bool;

    /**
     * Update role permissions
     *
     * @param  array<int, string>  $permissions
     */
    public function updatePermissions(int $id, array $permissions): bool;

    /**
     * Add permission to role
     */
    public function addPermission(int $roleId, string $permission): bool;

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, string $permission): bool;

    /**
     * Check if role has permission
     */
    public function hasPermission(int $roleId, string $permission): bool;

    /**
     * Get users count for role
     */
    public function getUsersCount(int $roleId): int;

    /**
     * Activate role
     */
    public function activate(int $id): bool;

    /**
     * Deactivate role
     */
    public function deactivate(int $id): bool;
}
