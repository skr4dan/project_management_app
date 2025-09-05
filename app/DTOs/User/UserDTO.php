<?php

namespace App\DTOs\User;

use App\Enums\User\UserStatus;

/**
 * User Data Transfer Object
 *
 * Represents user data as a value object for transfer between layers.
 * Immutable and validated to ensure data integrity.
 */
class UserDTO
{
    public function __construct(
        public ?int $id,
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $password,
        public ?int $role_id,
        public UserStatus $status,
        public ?string $avatar,
        public ?string $phone,
        public ?string $remember_token,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        $this->validate();
    }

    /**
     * Create DTO from array data
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            first_name: $data['first_name'] ?? '',
            last_name: $data['last_name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            role_id: $data['role_id'] ?? null,
            status: isset($data['status'])
                ? ($data['status'] instanceof UserStatus ? $data['status'] : UserStatus::from($data['status']))
                : UserStatus::Active,
            avatar: $data['avatar'] ?? null,
            phone: $data['phone'] ?? null,
            remember_token: $data['remember_token'] ?? null,
            created_at: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updated_at: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null,
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => $this->password,
            'role_id' => $this->role_id,
            'status' => $this->status->value,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'remember_token' => $this->remember_token,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get fillable data for model creation/update
     *
     * @return array<string, mixed>
     */
    public function toModelArray(): array
    {
        $data = $this->toArray();

        // Remove readonly fields
        unset($data['id'], $data['created_at'], $data['updated_at']);

        return $data;
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validate(): void
    {
        if (empty(trim($this->first_name))) {
            throw new \InvalidArgumentException('First name cannot be empty');
        }

        if (empty(trim($this->last_name))) {
            throw new \InvalidArgumentException('Last name cannot be empty');
        }

        if (empty(trim($this->email))) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }
    }

    /**
     * Create a new instance with modified data
     *
     * @param array<string, mixed> $changes
     * @return self
     */
    public function with(array $changes): self
    {
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => $this->password,
            'role_id' => $this->role_id,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'remember_token' => $this->remember_token,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return new self(...array_merge($data, $changes));
    }

    /**
     * Get user's full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Check if user is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Check if user is blocked
     *
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    /**
     * Check if user is inactive
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === UserStatus::Inactive;
    }

    /**
     * Get user initials
     *
     * @return string
     */
    public function getInitials(): string
    {
        return strtoupper($this->first_name[0] . $this->last_name[0]);
    }

    /**
     * Check if user has a role assigned
     *
     * @return bool
     */
    public function hasRole(): bool
    {
        return $this->role_id !== null;
    }
}
