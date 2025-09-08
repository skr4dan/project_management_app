<?php

namespace App\Http\Controllers\Api;

use App\DTOs\PaginationDTO;
use App\DTOs\Task\TaskDTO;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateTaskRequest;
use App\Http\Requests\Api\TaskIndexRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Http\Responses\JsonResponse;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;
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
    public function index(TaskIndexRequest $request): LaravelJsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $this->authService->user();
            $filters = $request->getFilters();
            $filter = new \App\Repositories\Criteria\Task\TaskFilter($filters);
            $pagination = PaginationDTO::fromRequest($request->getPaginationData());

            /** @var \App\Models\Role $role */
            $role = $user->role;
            if ($role->slug !== 'admin') {
                // Add user filter criteria, so user can only see tasks assigned to them or created by them
                $filter->addCriteria(new \App\Repositories\Criteria\Task\UserCriteria($user->id));
            }

            $tasks = $this->taskRepository->filter($filter, $pagination);

            return JsonResponse::successPaginated(
                TaskResource::collection($tasks),
                [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ],
                'Tasks retrieved successfully'
            );
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve tasks');
        }
    }

    /**
     * Create a new task (manager, admin only)
     */
    public function store(CreateTaskRequest $request): LaravelJsonResponse
    {
        try {
            $user = $this->authService->user();

            if (! $user) {
                return JsonResponse::unauthorized('User not authenticated');
            }

            $taskData = array_merge($request->validated(), [
                'created_by' => $user->id,
                'status' => TaskStatus::Pending,
                'priority' => $request->priority ?? TaskPriority::Medium,
            ]);

            $taskDTO = TaskDTO::fromArray($taskData);
            $task = $this->taskRepository->createFromDTO($taskDTO);

            // Load relationships for the response
            $task->load(['project', 'assignedTo', 'createdBy']);

            return JsonResponse::created(new TaskResource($task), 'Task created successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to create task');
        }
    }

    /**
     * Get task details
     */
    public function show(int $id): LaravelJsonResponse
    {
        try {
            $task = $this->taskRepository->findById($id);

            if (! $task) {
                return JsonResponse::notFound('Task not found');
            }

            // Load relationships for the response
            $task->load(['project', 'assignedTo', 'createdBy']);

            return JsonResponse::success(new TaskResource($task), 'Task retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve task');
        }
    }

    /**
     * Update task
     */
    public function update(UpdateTaskRequest $request, int $id): LaravelJsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $this->authService->user();
            $task = $this->taskRepository->findById($id);

            if (! $task) {
                return JsonResponse::notFound('Task not found');
            }

            /** @var \App\Models\Role $role */
            $role = $user->role;
            $canUpdate = $task->assigned_to === $user->id ||
                        ($task->created_by === $user->id && $task->assigned_to === null) ||
                        $role->slug === 'admin';

            if (! $canUpdate) {
                return JsonResponse::forbidden('You can only update tasks assigned to you or created by you');
            }

            // Prepare update data
            $updateData = array_filter($request->validated(), function ($value) {
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

            if (! $updated) {
                return JsonResponse::internalServerError('Failed to update task');
            }

            $updatedTask = $this->taskRepository->findById($id);

            // Load relationships for the response
            $updatedTask->load(['project', 'assignedTo', 'createdBy']);

            return JsonResponse::success(new TaskResource($updatedTask), 'Task updated successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to update task');
        }
    }

    /**
     * Delete task (author or admin)
     */
    public function destroy(int $id): LaravelJsonResponse
    {
        try {
            $user = $this->authService->user();
            $task = $this->taskRepository->findById($id);

            if (! $user) {
                return JsonResponse::unauthorized('User not authenticated');
            }

            if (! $task) {
                return JsonResponse::notFound('Task not found');
            }

            // Check permissions: author or admin
            /** @var \App\Models\Role $role */
            $role = $user->role;
            if ($task->created_by !== $user->id && $role->slug !== 'admin') {
                return JsonResponse::forbidden('You can only delete your own tasks');
            }

            // Note: Laravel repositories typically don't have delete methods
            // We'll use the model directly for deletion
            $deleted = $task->delete();

            if (! $deleted) {
                return JsonResponse::internalServerError('Failed to delete task');
            }

            return JsonResponse::successMessage('Task deleted successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to delete task');
        }
    }
}
