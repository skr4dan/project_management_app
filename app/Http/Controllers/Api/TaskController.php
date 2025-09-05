<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Task\TaskDTO;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateTaskRequest;
use App\Http\Requests\Api\TaskIndexRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Get list of tasks with filtering and sorting
     */
    public function index(TaskIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->getFilters();
            $filter = new \App\Repositories\Criteria\Task\TaskFilter($filters);
            $tasks = $this->taskRepository->filter($filter);

            return response()->json([
                'success' => true,
                'data' => TaskResource::collection($tasks),
                'message' => 'Tasks retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new task (manager, admin only)
     */
    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->user();

            $taskData = array_merge($request->validated(), [
                'created_by' => $user->id,
                'status' => TaskStatus::Pending,
                'priority' => $request->priority ?? TaskPriority::Medium,
            ]);

            $taskDTO = TaskDTO::fromArray($taskData);
            $task = $this->taskRepository->createFromDTO($taskDTO);

            return response()->json([
                'success' => true,
                'data' => new TaskResource($task),
                'message' => 'Task created successfully',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get task details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $task = $this->taskRepository->findById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => new TaskResource($task),
                'message' => 'Task retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update task
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->authService->user();
            $task = $this->taskRepository->findById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check permissions: assignee, creator, or admin can update
            $canUpdate = $task->assigned_to === $user->id ||
                        $task->created_by === $user->id ||
                        $user->role->slug === 'admin';

            if (!$canUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update tasks assigned to you or created by you',
                ], Response::HTTP_FORBIDDEN);
            }

            // Prepare update data
            $updateData = array_filter($request->validated(), function($value) {
                return $value !== null;
            });

            // Get current task data for merging
            $currentData = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'project_id' => $task->project_id,
                'assigned_to' => $task->assigned_to,
                'created_by' => $task->created_by,
                'due_date' => $task->due_date,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ];

            $mergedData = array_merge($currentData, $updateData);

            $taskDTO = TaskDTO::fromArray($mergedData);
            $updated = $this->taskRepository->updateFromDTO($id, $taskDTO);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update task',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $updatedTask = $this->taskRepository->findById($id);

            return response()->json([
                'success' => true,
                'data' => new TaskResource($updatedTask),
                'message' => 'Task updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete task (author or admin)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = $this->authService->user();
            $task = $this->taskRepository->findById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check permissions: author or admin
            if ($task->created_by !== $user->id && $user->role->slug !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own tasks',
                ], Response::HTTP_FORBIDDEN);
            }

            // Note: Laravel repositories typically don't have delete methods
            // We'll use the model directly for deletion
            $deleted = $task->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete task',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
