<?php

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Authenticate user and return JWT token.
     *
     * @param array{email: string, password: string} $credentials
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     * @throws \Exception
     */
    public function login(array $credentials): array;

    /**
     * Register a new user and return JWT token.
     *
     * @param array{name: string, email: string, password: string} $userData
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     * @throws \Exception
     */
    public function register(array $userData): array;

    /**
     * Logout user by invalidating token.
     */
    public function logout(): void;

    /**
     * Refresh JWT token.
     *
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function refresh(): array;

    /**
     * Get authenticated user.
     */
    public function user(): ?User;

    /**
     * Check if user is authenticated.
     */
    public function check(): bool;
}
