<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Authenticate user and return JWT token.
     *
     * @param array{email: string, password: string} $credentials
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     * @throws \Exception
     */
    public function login(array $credentials): array
    {
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            throw new \Exception('Invalid credentials');
        }

        /** @var User $user */
        $user = JWTAuth::user();

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
            'user' => $user,
        ];
    }

    /**
     * Register a new user and return JWT token.
     *
     * @param array{name: string, email: string, password: string} $userData
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     * @throws \Exception
     */
    public function register(array $userData): array
    {
        // Check if user already exists
        if ($this->userRepository->findByEmail($userData['email'])) {
            throw new \Exception('User already exists with this email');
        }

        // Hash the password
        $userData['password'] = Hash::make($userData['password']);

        // Create user
        $user = $this->userRepository->create($userData);

        // Generate token
        $token = JWTAuth::fromUser($user);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
        ];
    }

    /**
     * Logout user by invalidating token.
     */
    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    /**
     * Refresh JWT token.
     *
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function refresh(): array
    {
        $newToken = JWTAuth::refresh();

        return [
            'access_token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Get authenticated user.
     */
    public function user(): ?User
    {
        return JWTAuth::user();
    }

    /**
     * Check if user is authenticated.
     */
    public function check(): bool
    {
        return JWTAuth::check();
    }
}
