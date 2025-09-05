<?php

namespace App\Repositories;

use App\DTOs\User\UserDTO;
use App\Enums\User\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Repository Implementation
 *
 * Handles all user-related database operations using DTOs.
 * Follows SOLID principles with single responsibility focus.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Create a new user repository instance
     *
     * @param User $user
     */
    public function __construct(
        private User $user
    ) {}

    /**
     * Find user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function find(int $id): ?User
    {
        return $this->user->find($id);
    }

    /**
     * Find user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * Find user by ID or throw exception
     *
     * @param int $id
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User
    {
        return $this->user->findOrFail($id);
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }

    /**
     * Find users by role
     *
     * @param int $roleId
     * @return Collection<int, User>
     */
    public function findByRole(int $roleId): Collection
    {
        return $this->user->where('role_id', $roleId)->get();
    }

    /**
     * Find users by status
     *
     * @param UserStatus $status
     * @return Collection<int, User>
     */
    public function findByStatus(UserStatus $status): Collection
    {
        return $this->user->with('role')->where('status', $status->value)->get();
    }

    /**
     * Get active users
     *
     * @return Collection<int, User>
     */
    public function getActiveUsers(): Collection
    {
        return $this->findByStatus(UserStatus::Active);
    }

    /**
     * Create user from DTO
     *
     * @param UserDTO $userDTO
     * @return User
     */
    public function createFromDTO(UserDTO $userDTO): User
    {
        return $this->user->create($userDTO->toModelArray());
    }

    /**
     * Update user from DTO
     *
     * @param int $id
     * @param UserDTO $userDTO
     * @return bool
     */
    public function updateFromDTO(int $id, UserDTO $userDTO): bool
    {
        $user = $this->find($id);
        return $user ? $user->update($userDTO->toModelArray()) : false;
    }

    /**
     * Search users by name or email
     *
     * @param string $query
     * @return Collection<int, User>
     */
    public function search(string $query): Collection
    {
        return $this->user
            ->where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Update user status
     *
     * @param int $id
     * @param UserStatus $status
     * @return bool
     */
    public function updateStatus(int $id, UserStatus $status): bool
    {
        $user = $this->find($id);
        return $user ? $user->update(['status' => $status->value]) : false;
    }

    /**
     * Assign role to user
     *
     * @param int $userId
     * @param int $roleId
     * @return bool
     */
    public function assignRole(int $userId, int $roleId): bool
    {
        $user = $this->find($userId);
        return $user ? $user->update(['role_id' => $roleId]) : false;
    }

    /**
     * Remove role from user
     *
     * @param int $userId
     * @return bool
     */
    public function removeRole(int $userId): bool
    {
        $user = $this->find($userId);
        return $user ? $user->update(['role_id' => null]) : false;
    }
}