<?php

namespace App\Repositories\Contracts;

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
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Get projects by owner
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>
     */
    public function getByOwner(int $userId);

    /**
     * Get projects by status
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>
     */
    public function getByStatus(ProjectStatus $status);

    /**
     * Get active projects
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>
     */
    public function getActiveProjects();

    /**
     * Get completed projects
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>
     */
    public function getCompletedProjects();

    /**
     * Create project from DTO
     *
     * @return mixed
     */
    public function createFromDTO(\App\DTOs\Project\ProjectDTO $projectDTO);

    /**
     * Update project from DTO
     */
    public function updateFromDTO(int $id, \App\DTOs\Project\ProjectDTO $projectDTO): bool;

    /**
     * Update project status
     */
    public function updateStatus(int $id, ProjectStatus $status): bool;

    /**
     * Get project with tasks count
     *
     * @return mixed
     */
    public function getWithTasksCount(int $id);

    /**
     * Get projects with tasks statistics
     *
     * @param  int|null  $userId  Optional user filter
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>
     */
    public function getWithStatistics(?int $userId = null);

    /**
     * Get project completion percentage
     */
    public function getCompletionPercentage(int $id): float;

    /**
     * Search projects by name or description
     *
     * @param  int|null  $userId  Optional user filter
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>
     */
    public function search(string $query, ?int $userId = null);

    /**
     * Get user's project statistics
     *
     * @return array<string, int>
     */
    public function getUserProjectStatistics(int $userId): array;

    /**
     * Archive project
     */
    public function archive(int $id): bool;

    /**
     * Complete project
     */
    public function complete(int $id): bool;
}
