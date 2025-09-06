<?php

namespace App\Services;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\TokenResponseDTO;
use App\DTOs\User\UserDTO;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    /**
     * Authenticate user and return JWT token.
     *
     * @throws \Exception
     */
    public function login(LoginDTO $loginDTO): AuthResponseDTO
    {
        $token = JWTAuth::attempt($loginDTO->toArray());

        if (! $token) {
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
     * @throws \Exception
     */
    public function register(RegisterDTO $registerDTO, ?string $avatarPath = null): AuthResponseDTO
    {
        // Check if user already exists
        if ($this->userRepository->findByEmail($registerDTO->email)) {
            throw new \Exception('User already exists with this email');
        }

        // Get the default 'user' role
        $defaultRole = $this->roleRepository->findBySlug('user');
        if (! $defaultRole) {
            throw new \Exception('Default user role not found. Please run database migrations.');
        }

        // Create UserDTO from RegisterDTO
        $userDTO = new UserDTO(
            id: null,
            first_name: explode(' ', $registerDTO->name)[0],
            last_name: trim(str_replace(explode(' ', $registerDTO->name)[0], '', $registerDTO->name)),
            email: $registerDTO->email,
            password: Hash::make($registerDTO->password),
            role_id: $defaultRole->id,
            status: \App\Enums\User\UserStatus::Active,
            avatar: $avatarPath,
            phone: null,
            remember_token: null,
            created_at: null,
            updated_at: null
        );

        // Create user
        $user = $this->userRepository->createFromDTO($userDTO);

        // Load the role relationship
        $user->load('role');

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
        JWTAuth::invalidate();
    }

    /**
     * Refresh JWT token.
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
        /** @var User|null $user */
        $user = JWTAuth::user();
        return $user;
    }

    /**
     * Check if user is authenticated.
     */
    public function check(): bool
    {
        return JWTAuth::check();
    }
}
