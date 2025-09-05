<?php

namespace App\Repositories;

use App\DTOs\Project\ProjectDTO;
use App\Enums\Project\ProjectStatus;
use App\Enums\Task\TaskStatus;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Project Repository Implementation
 *
 * Handles all project-related database operations using DTOs.
 * Follows SOLID principles with single responsibility focus.
 */
class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Create a new project repository instance
     *
     * @param Project $project
     */
    public function __construct(
        private Project $project
    ) {}

    /**
     * Find project by ID
     *
     * @param int $id
     * @return Project|null
     */
    public function find(int $id): ?Project
    {
        return $this->project->find($id);
    }

    /**
     * Find project by ID
     *
     * @param int $id
     * @return Project|null
     */
    public function findById(int $id): ?Project
    {
        return $this->find($id);
    }

    /**
     * Find projects by owner
     *
     * @param int $userId
     * @return Collection<int, Project>
     */
    public function findByOwner(int $userId): Collection
    {
        return $this->project->where('created_by', $userId)->get();
    }

    /**
     * Find projects by status
     *
     * @param ProjectStatus $status
     * @return Collection<int, Project>
     */
    public function findByStatus(ProjectStatus $status): Collection
    {
        return $this->project->where('status', $status->value)->get();
    }

    /**
     * Get active projects
     *
     * @return Collection<int, Project>
     */
    public function getActiveProjects(): Collection
    {
        return $this->findByStatus(ProjectStatus::Active);
    }

    /**
     * Get completed projects
     *
     * @return Collection<int, Project>
     */
    public function getCompletedProjects(): Collection
    {
        return $this->findByStatus(ProjectStatus::Completed);
    }

    /**
     * Create project from DTO
     *
     * @param ProjectDTO $projectDTO
     * @return Project
     */
    public function createFromDTO(ProjectDTO $projectDTO): Project
    {
        return $this->project->create($projectDTO->toModelArray());
    }

    /**
     * Update project from DTO
     *
     * @param int $id
     * @param ProjectDTO $projectDTO
     * @return bool
     */
    public function updateFromDTO(int $id, ProjectDTO $projectDTO): bool
    {
        $project = $this->find($id);
        return $project ? $project->update($projectDTO->toModelArray()) : false;
    }

    /**
     * Update project status
     *
     * @param int $id
     * @param ProjectStatus $status
     * @return bool
     */
    public function updateStatus(int $id, ProjectStatus $status): bool
    {
        $project = $this->find($id);
        return $project ? $project->update(['status' => $status->value]) : false;
    }

    /**
     * Get project with tasks count
     *
     * @param int $id
     * @return Project|null
     */
    public function getWithTasksCount(int $id): ?Project
    {
        return $this->project->withCount('tasks')->find($id);
    }

    /**
     * Get projects with tasks statistics
     *
     * @param int|null $userId Optional user filter
     * @return Collection<int, Project>
     */
    public function getWithStatistics(?int $userId = null): Collection
    {
        $query = $this->project->with(['tasks' => function ($query) {
            $query->selectRaw('project_id, status, COUNT(*) as count')
                  ->groupBy('project_id', 'status');
        }]);

        if ($userId) {
            $query->where('created_by', $userId);
        }

        return $query->get();
    }

    /**
     * Get project completion percentage
     *
     * @param int $id
     * @return float
     */
    public function getCompletionPercentage(int $id): float
    {
        $project = $this->getWithTasksCount($id);

        if (!$project || $project->tasks_count === 0) {
            return 0.0;
        }

        $completedTasks = $project->tasks()
            ->where('status', TaskStatus::Completed->value)
            ->count();

        return round(($completedTasks / $project->tasks_count) * 100, 2);
    }

    /**
     * Search projects by name or description
     *
     * @param string $query
     * @param int|null $userId Optional user filter
     * @return Collection<int, Project>
     */
    public function search(string $query, ?int $userId = null): Collection
    {
        $builder = $this->project
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });

        if ($userId) {
            $builder->where('created_by', $userId);
        }

        return $builder->get();
    }

    /**
     * Get user's project statistics
     *
     * @param int $userId
     * @return array<string, int>
     */
    public function getUserProjectStatistics(int $userId): array
    {
        $projects = $this->findByOwner($userId);

        return [
            'total' => $projects->count(),
            'active' => $projects->where('status', ProjectStatus::Active)->count(),
            'completed' => $projects->where('status', ProjectStatus::Completed)->count(),
            'archived' => $projects->where('status', ProjectStatus::Archived)->count(),
        ];
    }

    /**
     * Archive project
     *
     * @param int $id
     * @return bool
     */
    public function archive(int $id): bool
    {
        return $this->updateStatus($id, ProjectStatus::Archived);
    }

    /**
     * Complete project
     *
     * @param int $id
     * @return bool
     */
    public function complete(int $id): bool
    {
        return $this->updateStatus($id, ProjectStatus::Completed);
    }
}
