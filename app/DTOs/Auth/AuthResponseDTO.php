<?php

namespace App\DTOs\Auth;

use App\Models\User;

/**
 * Authentication Response Data Transfer Object
 *
 * Represents the response data for authentication operations.
 * Immutable and contains user authentication information.
 */
readonly class AuthResponseDTO
{
    public function __construct(
        public string $access_token,
        public string $token_type,
        public int $expires_in,
        public User $user,
    ) {}

    /**
     * Create DTO from authentication data
     *
     * @param  array{access_token: string, token_type: string, expires_in: int, user: User}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            access_token: $data['access_token'],
            token_type: $data['token_type'],
            expires_in: $data['expires_in'],
            user: $data['user'],
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
            'access_token' => $this->access_token,
            'token_type' => $this->token_type,
            'expires_in' => $this->expires_in,
            'user' => $this->user,
        ];
    }
}
