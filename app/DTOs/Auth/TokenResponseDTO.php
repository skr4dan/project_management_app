<?php

namespace App\DTOs\Auth;

/**
 * Token Response Data Transfer Object
 *
 * Represents the response data for token operations like refresh.
 * Immutable and contains token information.
 */
readonly class TokenResponseDTO
{
    public function __construct(
        public string $access_token,
        public string $token_type,
        public int $expires_in,
    ) {}

    /**
     * Create DTO from token data
     *
     * @param array{access_token: string, token_type: string, expires_in: int} $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            access_token: $data['access_token'],
            token_type: $data['token_type'],
            expires_in: $data['expires_in'],
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
        ];
    }
}
