<?php

namespace App\Http\Controllers\Api;

use App\DTOs\User\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\JsonResponse;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Get list of users (admin, manager only)
     */
    public function index(): LaravelJsonResponse
    {
        try {
            $users = $this->userRepository->getActiveUsers()->load('role');

            return JsonResponse::success(UserResource::collection($users), 'Users retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve users');
        }
    }

    /**
     * Get user details
     */
    public function show(int $id): LaravelJsonResponse
    {
        try {
            $user = $this->userRepository->findById($id);

            if (! $user) {
                return JsonResponse::notFound('User not found');
            }

            // Load role relationship for the response
            $user->load('role');

            return JsonResponse::success(new UserResource($user), 'User retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve user');
        }
    }

    /**
     * Update user profile (own profile or admin)
     */
    public function update(UpdateUserRequest $request, int $id): LaravelJsonResponse
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = $this->authService->user();

            $targetUser = $this->userRepository->findById($id);

            if (! $targetUser) {
                return JsonResponse::notFound('User not found');
            }

            // Allow admin to update any user, or users to update their own profile
            /** @var \App\Models\Role $role */
            $role = $authenticatedUser->role;
            if ($authenticatedUser->id !== $id && $role->slug !== 'admin') {
                return JsonResponse::forbidden('You can only update your own profile');
            }

            $user = $targetUser;

            // Prepare update data
            $updateData = array_filter($request->validated(), function ($value) {
                return $value !== null;
            });

            $avatarPath = $user->avatar;

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            }

            // Get current user data for merging
            $currentData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'password' => $user->password,
                'role_id' => $user->role_id,
                'status' => $user->status,
                'avatar' => $avatarPath,
                'phone' => $user->phone,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            $mergedData = array_merge($currentData, $updateData);

            $userDTO = UserDTO::fromArray($mergedData);
            $updated = $this->userRepository->updateFromDTO($id, $userDTO);

            if (! $updated) {
                return JsonResponse::internalServerError('Failed to update user');
            }

            $updatedUser = $this->userRepository->findById($id);

            return JsonResponse::success(new UserResource($updatedUser), 'User updated successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to update user');
        }
    }
}
