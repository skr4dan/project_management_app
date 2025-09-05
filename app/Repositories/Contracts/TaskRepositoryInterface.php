<?php

namespace App\Repositories\Contracts;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;

/**
 * Task Repository Interface
 *
 * Defines methods for task data access operations.
 */
interface TaskRepositoryInterface
{
    /**
     * Find task by ID
     *
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Get tasks by project
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByProject(int $projectId);

    /**
     * Get tasks by assignee
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByAssignee(int $userId);

    /**
     * Get tasks by creator
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCreator(int $userId);

    /**
     * Get tasks by status
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(TaskStatus $status);

    /**
     * Get tasks by priority
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByPriority(TaskPriority $priority);

    /**
     * Get overdue tasks
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueTasks();

    /**
     * Get tasks due soon (within next N days)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksDueSoon(int $days = 7);

    /**
     * Create task from DTO
     *
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\Task\TaskDTO $taskDTO);

    /**
     * Update task from DTO
     */
    public function updateFromDTO(int $id, \App\DTOs\Task\TaskDTO $taskDTO): bool;

    /**
     * Update task status
     */
    public function updateStatus(int $id, TaskStatus $status): bool;

    /**
     * Update task priority
     */
    public function updatePriority(int $id, TaskPriority $priority): bool;

    /**
     * Assign task to user
     */
    public function assignToUser(int $taskId, int $userId): bool;

    /**
     * Unassign task from user
     */
    public function unassignFromUser(int $taskId): bool;

    /**
     * Get tasks statistics for project
     *
     * @return array<string, int>
     */
    public function getProjectStatistics(int $projectId): array;

    /**
     * Get user's task statistics
     *
     * @return array<string, int>
     */
    public function getUserStatistics(int $userId): array;

    /**
     * Filter tasks using criteria
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function filter(\App\Repositories\Criteria\Task\TaskFilter $filter, \App\DTOs\PaginationDTO $pagination);
}
