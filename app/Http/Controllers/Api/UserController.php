<?php

namespace App\Http\Controllers\Api;

use App\DTOs\User\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Contracts\AuthServiceInterface;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Get list of users (admin, manager only)
     */
    public function index(): JsonResponse
    {
        try {
            $users = $this->userRepository->getActiveUsers();

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'message' => 'Users retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
                'message' => 'User retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update user profile (own profile or admin)
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $authenticatedUser = $this->authService->user();

            $targetUser = $this->userRepository->findById($id);

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Allow admin to update any user, or users to update their own profile
            if ($authenticatedUser->id !== $id && $authenticatedUser->role->slug !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own profile',
                ], Response::HTTP_FORBIDDEN);
            }

            $user = $targetUser;

            // Prepare update data
            $updateData = array_filter($request->validated(), function($value) {
                return $value !== null;
            });

            // Get current user data for merging
            $currentData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'password' => $user->password,
                'role_id' => $user->role_id,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            $mergedData = array_merge($currentData, $updateData);

            $userDTO = UserDTO::fromArray($mergedData);
            $updated = $this->userRepository->updateFromDTO($id, $userDTO);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $updatedUser = $this->userRepository->findById($id);

            return response()->json([
                'success' => true,
                'data' => new UserResource($updatedUser),
                'message' => 'User updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
