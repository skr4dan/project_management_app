<?php

namespace App\Repositories\Contracts;

use App\DTOs\User\UserDTO;
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
     * @param int $id
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Find user by email
     *
     * @param string $email
     * @return mixed
     */
    public function findByEmail(string $email);

    /**
     * Find users by role
     *
     * @param int $roleId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByRole(int $roleId);

    /**
     * Find users by status
     *
     * @param UserStatus $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByStatus(UserStatus $status);

    /**
     * Get active users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveUsers();

    /**
     * Create user from DTO
     *
     * @param UserDTO $userDTO
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\User\UserDTO $userDTO);

    /**
     * Update user from DTO
     *
     * @param int $id
     * @param UserDTO $userDTO
     * @return bool
     */
    public function updateFromDTO(int $id, \App\DTOs\User\UserDTO $userDTO): bool;

    /**
     * Search users by name or email
     *
     * @param string $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query);

    /**
     * Update user status
     *
     * @param int $id
     * @param UserStatus $status
     * @return bool
     */
    public function updateStatus(int $id, UserStatus $status): bool;

    /**
     * Assign role to user
     *
     * @param int $userId
     * @param int $roleId
     * @return bool
     */
    public function assignRole(int $userId, int $roleId): bool;

    /**
     * Remove role from user
     *
     * @param int $userId
     * @return bool
     */
    public function removeRole(int $userId): bool;
}