<?php

namespace App\Repositories\Contracts;

use App\DTOs\Project\ProjectDTO;
use App\Enums\Project\ProjectStatus;

/**
 * Project Repository Interface
 *
 * Defines methods for project data access operations.
 */
interface ProjectRepositoryInterface
{
    /**
     * Find project by ID
     *
     * @param int $id
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Get projects by owner
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByOwner(int $userId);

    /**
     * Get projects by status
     *
     * @param ProjectStatus $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(ProjectStatus $status);

    /**
     * Get active projects
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveProjects();

    /**
     * Get completed projects
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompletedProjects();

    /**
     * Create project from DTO
     *
     * @param ProjectDTO $projectDTO
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\Project\ProjectDTO $projectDTO);

    /**
     * Update project from DTO
     *
     * @param int $id
     * @param ProjectDTO $projectDTO
     * @return bool
     */
    public function updateFromDTO(int $id, \App\DTOs\Project\ProjectDTO $projectDTO): bool;

    /**
     * Update project status
     *
     * @param int $id
     * @param ProjectStatus $status
     * @return bool
     */
    public function updateStatus(int $id, ProjectStatus $status): bool;

    /**
     * Get project with tasks count
     *
     * @param int $id
     * @return mixed
     */
    public function getWithTasksCount(int $id);

    /**
     * Get projects with tasks statistics
     *
     * @param int|null $userId Optional user filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWithStatistics(?int $userId = null);

    /**
     * Get project completion percentage
     *
     * @param int $id
     * @return float
     */
    public function getCompletionPercentage(int $id): float;

    /**
     * Search projects by name or description
     *
     * @param string $query
     * @param int|null $userId Optional user filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query, ?int $userId = null);

    /**
     * Get user's project statistics
     *
     * @param int $userId
     * @return array<string, int>
     */
    public function getUserProjectStatistics(int $userId): array;

    /**
     * Archive project
     *
     * @param int $id
     * @return bool
     */
    public function archive(int $id): bool;

    /**
     * Complete project
     *
     * @param int $id
     * @return bool
     */
    public function complete(int $id): bool;
}
