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
     */
    public function __construct(
        private User $user
    ) {}

    /**
     * Find user by ID
     */
    public function find(int $id): ?User
    {
        return $this->user->find($id);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * Find user by ID or throw exception
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User
    {
        return $this->user->findOrFail($id);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }

    /**
     * Get users by role
     *
     * @return Collection<int, User>
     */
    public function getByRole(int $roleId): Collection
    {
        return $this->user->where('role_id', $roleId)->get();
    }

    /**
     * Get users by status
     *
     * @return Collection<int, User>
     */
    public function getByStatus(UserStatus $status): Collection
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
        return $this->getByStatus(UserStatus::Active);
    }

    /**
     * Create user from DTO
     */
    public function createFromDTO(UserDTO $userDTO): User
    {
        return $this->user->create($userDTO->toModelArray());
    }

    /**
     * Update user from DTO
     */
    public function updateFromDTO(int $id, UserDTO $userDTO): bool
    {
        $user = $this->find($id);

        return $user ? $user->update($userDTO->toModelArray()) : false;
    }

    /**
     * Search users by name or email
     *
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
     */
    public function updateStatus(int $id, UserStatus $status): bool
    {
        $user = $this->find($id);

        return $user ? $user->update(['status' => $status->value]) : false;
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, int $roleId): bool
    {
        $user = $this->find($userId);

        return $user ? $user->update(['role_id' => $roleId]) : false;
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId): bool
    {
        $user = $this->find($userId);

        return $user ? $user->update(['role_id' => null]) : false;
    }
}
