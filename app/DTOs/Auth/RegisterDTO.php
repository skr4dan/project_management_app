<?php

namespace App\DTOs\Auth;

use App\Enums\User\UserStatus;

/**
 * Register Data Transfer Object
 *
 * Represents user registration data as a value object for transfer between layers.
 * Immutable and validated to ensure data integrity.
 */
readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $password_confirmation = null,
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
            name: $data['name'] ?? '',
            email: strtolower($data['email'] ?? ''),
            password: $data['password'] ?? '',
            password_confirmation: $data['password_confirmation'] ?? null,
        );
    }

    /**
     * Convert DTO to user creation array
     *
     * @return array<string, mixed>
     */
    public function toUserArray(): array
    {
        return [
            'first_name' => explode(' ', $this->name)[0],
            'last_name' => trim(str_replace(explode(' ', $this->name)[0], '', $this->name)),
            'email' => $this->email,
            'password' => $this->password,
            'status' => UserStatus::Active,
        ];
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Name is required');
        }

        if (empty($this->email)) {
            throw new \InvalidArgumentException('Email is required');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (empty($this->password)) {
            throw new \InvalidArgumentException('Password is required');
        }

        if (strlen($this->password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        if ($this->password_confirmation !== null && $this->password !== $this->password_confirmation) {
            throw new \InvalidArgumentException('Password confirmation does not match');
        }
    }
}
