<?php

namespace App\DTOs\Auth;

/**
 * Login Data Transfer Object
 *
 * Represents login credentials as a value object for transfer between layers.
 * Immutable and validated to ensure data integrity.
 */
readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
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
            email: strtolower($data['email'] ?? ''),
            password: $data['password'] ?? '',
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->email)) {
            throw new \InvalidArgumentException('Email is required');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (empty($this->password)) {
            throw new \InvalidArgumentException('Password is required');
        }
    }
}
