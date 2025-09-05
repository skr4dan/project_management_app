<?php

namespace App\Repositories\Contracts;

use App\DTOs\Task\TaskDTO;
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
     * @param int $id
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Find tasks by project
     *
     * @param int $projectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByProject(int $projectId);

    /**
     * Find tasks by assignee
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByAssignee(int $userId);

    /**
     * Find tasks by creator
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCreator(int $userId);

    /**
     * Find tasks by status
     *
     * @param TaskStatus $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByStatus(TaskStatus $status);

    /**
     * Find tasks by priority
     *
     * @param TaskPriority $priority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByPriority(TaskPriority $priority);

    /**
     * Get overdue tasks
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueTasks();

    /**
     * Get tasks due soon (within next N days)
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksDueSoon(int $days = 7);

    /**
     * Create task from DTO
     *
     * @param TaskDTO $taskDTO
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\Task\TaskDTO $taskDTO);

    /**
     * Update task from DTO
     *
     * @param int $id
     * @param TaskDTO $taskDTO
     * @return bool
     */
    public function updateFromDTO(int $id, \App\DTOs\Task\TaskDTO $taskDTO): bool;

    /**
     * Update task status
     *
     * @param int $id
     * @param TaskStatus $status
     * @return bool
     */
    public function updateStatus(int $id, TaskStatus $status): bool;

    /**
     * Update task priority
     *
     * @param int $id
     * @param TaskPriority $priority
     * @return bool
     */
    public function updatePriority(int $id, TaskPriority $priority): bool;

    /**
     * Assign task to user
     *
     * @param int $taskId
     * @param int $userId
     * @return bool
     */
    public function assignToUser(int $taskId, int $userId): bool;

    /**
     * Unassign task from user
     *
     * @param int $taskId
     * @return bool
     */
    public function unassignFromUser(int $taskId): bool;

    /**
     * Get tasks statistics for project
     *
     * @param int $projectId
     * @return array<string, int>
     */
    public function getProjectStatistics(int $projectId): array;

    /**
     * Get user's task statistics
     *
     * @param int $userId
     * @return array<string, int>
     */
    public function getUserStatistics(int $userId): array;

    /**
     * Filter tasks using criteria
     *
     * @param \App\Repositories\Criteria\Task\TaskFilter $filter
     * @param \App\DTOs\PaginationDTO $pagination
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function filter(\App\Repositories\Criteria\Task\TaskFilter $filter, \App\DTOs\PaginationDTO $pagination);
}
