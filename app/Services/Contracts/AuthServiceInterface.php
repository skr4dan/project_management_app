<?php

namespace App\Services\Contracts;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\TokenResponseDTO;
use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Authenticate user and return JWT token.
     *
     * @throws \Exception
     */
    public function login(LoginDTO $loginDTO): AuthResponseDTO;

    /**
     * Register a new user and return JWT token.
     *
     * @throws \Exception
     */
    public function register(RegisterDTO $registerDTO, ?string $avatarPath = null): AuthResponseDTO;

    /**
     * Logout user by invalidating token.
     */
    public function logout(): void;

    /**
     * Refresh JWT token.
     */
    public function refresh(): TokenResponseDTO;

    /**
     * Get authenticated user.
     */
    public function user(): ?User;

    /**
     * Check if user is authenticated.
     */
    public function check(): bool;
}
