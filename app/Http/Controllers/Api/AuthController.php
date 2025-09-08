<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\TokenResponseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\JsonResponse;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Login user and return JWT token.
     */
    public function login(LoginRequest $request): LaravelJsonResponse
    {
        try {
            $loginDTO = LoginDTO::fromArray($request->validated());
            /** @var AuthResponseDTO $result */
            $result = $this->authService->login($loginDTO);

            return JsonResponse::success([
                'access_token' => $result->access_token,
                'token_type' => $result->token_type,
                'expires_in' => $result->expires_in,
                'user' => new UserResource($result->user),
            ], 'Login successful');
        } catch (\Exception $e) {
            return JsonResponse::unauthorized($e->getMessage());
        }
    }

    /**
     * Register a new user and return JWT token.
     */
    public function register(RegisterRequest $request): LaravelJsonResponse
    {
        try {
            $validatedData = $request->validated();
            $avatarPath = null;

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public') ?: null;
            }

            $registerDTO = RegisterDTO::fromArray($validatedData);
            /** @var AuthResponseDTO $result */
            $result = $this->authService->register($registerDTO, $avatarPath);

            return JsonResponse::created([
                'access_token' => $result->access_token,
                'token_type' => $result->token_type,
                'expires_in' => $result->expires_in,
                'user' => new UserResource($result->user),
            ], 'Registration successful');
        } catch (\Exception $e) {
            return JsonResponse::badRequest($e->getMessage());
        }
    }

    /**
     * Logout user by invalidating token.
     */
    public function logout(): LaravelJsonResponse
    {
        try {
            $this->authService->logout();

            return JsonResponse::successMessage('Logout successful');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Logout failed');
        }
    }

    /**
     * Refresh JWT token.
     */
    public function refresh(): LaravelJsonResponse
    {
        try {
            /** @var TokenResponseDTO $result */
            $result = $this->authService->refresh();

            return JsonResponse::success($result->toArray(), 'Token refreshed successfully');
        } catch (\Exception $e) {
            return JsonResponse::unauthorized('Token refresh failed');
        }
    }

    /**
     * Get authenticated user profile.
     */
    public function user(): LaravelJsonResponse
    {
        try {
            $user = $this->authService->user();

            if (! $user) {
                return JsonResponse::notFound('User not found');
            }

            return JsonResponse::success(new UserResource($user), 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve user profile');
        }
    }
}
