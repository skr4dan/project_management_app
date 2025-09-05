<?php

namespace App\Services;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\TokenResponseDTO;
use App\DTOs\User\UserDTO;
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
     * @param LoginDTO $loginDTO
     * @return AuthResponseDTO
     * @throws \Exception
     */
    public function login(LoginDTO $loginDTO): AuthResponseDTO
    {
        $token = JWTAuth::attempt($loginDTO->toArray());

        if (!$token) {
            throw new \Exception('Invalid credentials');
        }

        /** @var User $user */
        $user = JWTAuth::user();

        return new AuthResponseDTO(
            access_token: $token,
            token_type: 'Bearer',
            expires_in: config('jwt.ttl') * 60, // Convert minutes to seconds
            user: $user,
        );
    }

    /**
     * Register a new user and return JWT token.
     *
     * @param RegisterDTO $registerDTO
     * @return AuthResponseDTO
     * @throws \Exception
     */
    public function register(RegisterDTO $registerDTO): AuthResponseDTO
    {
        // Check if user already exists
        if ($this->userRepository->findByEmail($registerDTO->email)) {
            throw new \Exception('User already exists with this email');
        }

        // Create UserDTO from RegisterDTO
        $userDTO = new UserDTO(
            id: null,
            first_name: explode(' ', $registerDTO->name)[0],
            last_name: trim(str_replace(explode(' ', $registerDTO->name)[0], '', $registerDTO->name)),
            email: $registerDTO->email,
            password: Hash::make($registerDTO->password),
            role_id: null,
            status: \App\Enums\User\UserStatus::Active,
            avatar: null,
            phone: null,
            remember_token: null,
            created_at: null,
            updated_at: null
        );

        // Create user
        $user = $this->userRepository->createFromDTO($userDTO);

        // Generate token
        $token = JWTAuth::fromUser($user);

        return new AuthResponseDTO(
            access_token: $token,
            token_type: 'Bearer',
            expires_in: config('jwt.ttl') * 60,
            user: $user,
        );
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
     * @return TokenResponseDTO
     */
    public function refresh(): TokenResponseDTO
    {
        $newToken = JWTAuth::refresh();

        return new TokenResponseDTO(
            access_token: $newToken,
            token_type: 'Bearer',
            expires_in: config('jwt.ttl') * 60,
        );
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
