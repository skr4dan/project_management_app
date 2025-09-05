<?php

namespace App\Repositories;

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
     *
     * @param Task $task
     */
    public function __construct(
        private Task $task
    ) {}

    /**
     * Find task by ID
     *
     * @param int $id
     * @return Task|null
     */
    public function find(int $id): ?Task
    {
        return $this->task->find($id);
    }

    /**
     * Find task by ID
     *
     * @param int $id
     * @return Task|null
     */
    public function findById(int $id): ?Task
    {
        return $this->find($id);
    }

    /**
     * Find tasks by project
     *
     * @param int $projectId
     * @return Collection<int, Task>
     */
    public function findByProject(int $projectId): Collection
    {
        return $this->task->where('project_id', $projectId)->get();
    }

    /**
     * Find tasks by assignee
     *
     * @param int $userId
     * @return Collection<int, Task>
     */
    public function findByAssignee(int $userId): Collection
    {
        return $this->task->where('assigned_to', $userId)->get();
    }

    /**
     * Find tasks by creator
     *
     * @param int $userId
     * @return Collection<int, Task>
     */
    public function findByCreator(int $userId): Collection
    {
        return $this->task->where('created_by', $userId)->get();
    }

    /**
     * Find tasks by status
     *
     * @param TaskStatus $status
     * @return Collection<int, Task>
     */
    public function findByStatus(TaskStatus $status): Collection
    {
        return $this->task->where('status', $status->value)->get();
    }

    /**
     * Find tasks by priority
     *
     * @param TaskPriority $priority
     * @return Collection<int, Task>
     */
    public function findByPriority(TaskPriority $priority): Collection
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
     * @param int $days
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
     *
     * @param TaskDTO $taskDTO
     * @return Task
     */
    public function createFromDTO(TaskDTO $taskDTO): Task
    {
        return $this->task->create($taskDTO->toModelArray());
    }

    /**
     * Update task from DTO
     *
     * @param int $id
     * @param TaskDTO $taskDTO
     * @return bool
     */
    public function updateFromDTO(int $id, TaskDTO $taskDTO): bool
    {
        $task = $this->find($id);
        return $task ? $task->update($taskDTO->toModelArray()) : false;
    }

    /**
     * Update task status
     *
     * @param int $id
     * @param TaskStatus $status
     * @return bool
     */
    public function updateStatus(int $id, TaskStatus $status): bool
    {
        $task = $this->find($id);
        return $task ? $task->update(['status' => $status->value]) : false;
    }

    /**
     * Update task priority
     *
     * @param int $id
     * @param TaskPriority $priority
     * @return bool
     */
    public function updatePriority(int $id, TaskPriority $priority): bool
    {
        $task = $this->find($id);
        return $task ? $task->update(['priority' => $priority->value]) : false;
    }

    /**
     * Assign task to user
     *
     * @param int $taskId
     * @param int $userId
     * @return bool
     */
    public function assignToUser(int $taskId, int $userId): bool
    {
        $task = $this->find($taskId);
        return $task ? $task->update(['assigned_to' => $userId]) : false;
    }

    /**
     * Unassign task from user
     *
     * @param int $taskId
     * @return bool
     */
    public function unassignFromUser(int $taskId): bool
    {
        $task = $this->find($taskId);
        return $task ? $task->update(['assigned_to' => null]) : false;
    }

    /**
     * Get tasks statistics for project
     *
     * @param int $projectId
     * @return array<string, int>
     */
    public function getProjectStatistics(int $projectId): array
    {
        $tasks = $this->findByProject($projectId);

        return [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', TaskStatus::Pending)->count(),
            'in_progress' => $tasks->where('status', TaskStatus::InProgress)->count(),
            'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
            'overdue' => $tasks->filter(fn($task) => $task->due_date && $task->due_date < now() && $task->status !== TaskStatus::Completed)->count(),
        ];
    }

    /**
     * Get user's task statistics
     *
     * @param int $userId
     * @return array<string, int>
     */
    public function getUserStatistics(int $userId): array
    {
        $tasks = $this->findByAssignee($userId);

        return [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', TaskStatus::Pending)->count(),
            'in_progress' => $tasks->where('status', TaskStatus::InProgress)->count(),
            'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
            'overdue' => $tasks->filter(fn($task) => $task->due_date && $task->due_date < now() && $task->status !== TaskStatus::Completed)->count(),
        ];
    }

    /**
     * Filter tasks using criteria
     *
     * @param \App\Repositories\Criteria\Task\TaskFilter $filter
     * @return Collection<int, Task>
     */
    public function filter(\App\Repositories\Criteria\Task\TaskFilter $filter): Collection
    {
        $query = $this->task->newQuery();

        // Apply all criteria
        $query = $filter->apply($query);

        return $query->get();
    }
}
