<?php

namespace App\Repositories;

use App\DTOs\PaginationDTO;
use App\DTOs\Task\TaskDTO;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Task Repository Implementation
 *
 * Handles all task-related database operations using DTOs.
 * Follows SOLID principles with single responsibility focus.
 */
class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Create a new task repository instance
     */
    public function __construct(
        private Task $task
    ) {}

    /**
     * Find task by ID
     */
    public function findById(int $id): ?Task
    {
        return $this->task->find($id);
    }

    /**
     * Get tasks by project
     *
     * @return Collection<int, Task>
     */
    public function getByProject(int $projectId): Collection
    {
        return $this->task->where('project_id', $projectId)->get();
    }

    /**
     * Get tasks by assignee
     *
     * @return Collection<int, Task>
     */
    public function getByAssignee(int $userId): Collection
    {
        return $this->task->where('assigned_to', $userId)->get();
    }

    /**
     * Get tasks by creator
     *
     * @return Collection<int, Task>
     */
    public function getByCreator(int $userId): Collection
    {
        return $this->task->where('created_by', $userId)->get();
    }

    /**
     * Get tasks by status
     *
     * @return Collection<int, Task>
     */
    public function getByStatus(TaskStatus $status): Collection
    {
        return $this->task->where('status', $status->value)->get();
    }

    /**
     * Get tasks by priority
     *
     * @return Collection<int, Task>
     */
    public function getByPriority(TaskPriority $priority): Collection
    {
        return $this->task->where('priority', $priority->value)->get();
    }

    /**
     * Get overdue tasks
     *
     * @return Collection<int, Task>
     */
    public function getOverdueTasks(): Collection
    {
        return $this->task
            ->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Completed->value)
            ->get();
    }

    /**
     * Get tasks due soon (within next N days)
     *
     * @return Collection<int, Task>
     */
    public function getTasksDueSoon(int $days = 7): Collection
    {
        return $this->task
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->where('status', '!=', TaskStatus::Completed->value)
            ->get();
    }

    /**
     * Create task from DTO
     */
    public function createFromDTO(TaskDTO $taskDTO): Task
    {
        return $this->task->create($taskDTO->toModelArray());
    }

    /**
     * Update task from DTO
     */
    public function updateFromDTO(int $id, TaskDTO $taskDTO): bool
    {
        $task = $this->findById($id);

        return $task ? $task->update($taskDTO->toModelArray()) : false;
    }

    /**
     * Update task status
     */
    public function updateStatus(int $id, TaskStatus $status): bool
    {
        $task = $this->findById($id);

        return $task ? $task->update(['status' => $status->value]) : false;
    }

    /**
     * Update task priority
     */
    public function updatePriority(int $id, TaskPriority $priority): bool
    {
        $task = $this->findById($id);

        return $task ? $task->update(['priority' => $priority->value]) : false;
    }

    /**
     * Assign task to user
     */
    public function assignToUser(int $taskId, int $userId): bool
    {
        $task = $this->findById($taskId);

        return $task ? $task->update(['assigned_to' => $userId]) : false;
    }

    /**
     * Unassign task from user
     */
    public function unassignFromUser(int $taskId): bool
    {
        $task = $this->findById($taskId);

        return $task ? $task->update(['assigned_to' => null]) : false;
    }

    /**
     * Get tasks statistics for project
     *
     * @return array<string, int>
     */
    public function getProjectStatistics(int $projectId): array
    {
        $tasks = $this->getByProject($projectId);

        return [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', TaskStatus::Pending)->count(),
            'in_progress' => $tasks->where('status', TaskStatus::InProgress)->count(),
            'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
            'overdue' => $tasks->filter(fn ($task) => $task->due_date && $task->due_date < now() && $task->status !== TaskStatus::Completed)->count(),
        ];
    }

    /**
     * Get user's task statistics
     *
     * @return array<string, int>
     */
    public function getUserStatistics(int $userId): array
    {
        $tasks = $this->getByAssignee($userId);

        return [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', TaskStatus::Pending)->count(),
            'in_progress' => $tasks->where('status', TaskStatus::InProgress)->count(),
            'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
            'overdue' => $tasks->filter(fn ($task) => $task->due_date && $task->due_date < now() && $task->status !== TaskStatus::Completed)->count(),
        ];
    }

    /**
     * Filter tasks using criteria
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\Task>
     */
    public function filter(
        \App\Repositories\Criteria\Task\TaskFilter $filter,
        \App\DTOs\PaginationDTO $pagination = new PaginationDTO
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = $this->task->with(['project', 'assignedTo', 'createdBy'])->newQuery();

        // Apply all criteria
        $query = $filter->apply($query);

        return $query->paginate($pagination->perPage, ['*'], 'page', $pagination->page);
    }
}
