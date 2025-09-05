<?php

namespace App\Repositories\Contracts;

use App\Enums\User\UserStatus;

/**
 * User Repository Interface
 *
 * Defines methods for user data access operations.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID
     *
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Find user by email
     *
     * @return mixed
     */
    public function findByEmail(string $email);

    /**
     * Get users by role
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRole(int $roleId);

    /**
     * Get users by status
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(UserStatus $status);

    /**
     * Get active users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveUsers();

    /**
     * Create user from DTO
     *
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\User\UserDTO $userDTO);

    /**
     * Update user from DTO
     */
    public function updateFromDTO(int $id, \App\DTOs\User\UserDTO $userDTO): bool;

    /**
     * Search users by name or email
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query);

    /**
     * Update user status
     */
    public function updateStatus(int $id, UserStatus $status): bool;

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, int $roleId): bool;

    /**
     * Remove role from user
     */
    public function removeRole(int $userId): bool;
}
