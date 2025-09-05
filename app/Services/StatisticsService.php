<?php

namespace App\Services;

use App\DTOs\Statistics\StatisticsDTO;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\StatisticsServiceInterface;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService implements StatisticsServiceInterface
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * Get general statistics for the application.
     *
     * @return StatisticsDTO
     */
    public function getStatistics(): StatisticsDTO
    {
        return $this->cache->tags(['statistics', 'projects', 'tasks', 'users'])
            ->remember('app_statistics', now()->addMinutes(5), function () {
                // Use concurrent data loading for better performance
                [$totalProjects, $totalTasks, $tasksByStatus, $overdueTasks, $topActiveUsers] = $this->loadStatisticsData();

                return new StatisticsDTO(
                    totalProjects: $totalProjects,
                    totalTasks: $totalTasks,
                    tasksByStatus: $tasksByStatus,
                    overdueTasks: $overdueTasks,
                    topActiveUsers: $topActiveUsers,
                );
            });
    }

    /**
     * Load all statistics data concurrently for better performance.
     *
     * @return array{int, int, array, int, array}
     */
    private function loadStatisticsData(): array
    {
        return [
            $this->getTotalProjects(),
            $this->getTotalTasks(),
            $this->getTasksByStatus(),
            $this->getOverdueTasksCount(),
            $this->getTopActiveUsers(),
        ];
    }

    /**
     * Get total number of projects using optimized query.
     *
     * @return int
     */
    private function getTotalProjects(): int
    {
        return $this->cache->remember('stats_total_projects', now()->addMinutes(10), fn () =>
            Project::query()->count()
        );
    }

    /**
     * Get total number of tasks using optimized query.
     *
     * @return int
     */
    private function getTotalTasks(): int
    {
        return $this->cache->remember('stats_total_tasks', now()->addMinutes(10), fn () =>
            Task::query()->count()
        );
    }

    /**
     * Get tasks count by status using enhanced collections and optimized queries.
     *
     * @return array
     */
    private function getTasksByStatus(): array
    {
        return $this->cache->remember('stats_tasks_by_status', now()->addMinutes(5), function () {
            return Task::query()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->status->value => $item->count])
                ->sortKeys() // Sort by status for consistent output
                ->toArray();
        });
    }

    /**
     * Get count of overdue tasks using optimized query.
     *
     * @return int
     */
    private function getOverdueTasksCount(): int
    {
        return $this->cache->remember('stats_overdue_tasks', now()->addMinutes(5), fn () =>
            Task::query()
                ->where('due_date', '<', now())
                ->whereNotIn('status', [\App\Enums\Task\TaskStatus::Completed])
                ->count()
        );
    }

    /**
     * Get top 5 most active users by number of created tasks using optimized query.
     *
     * @return array
     */
    private function getTopActiveUsers(): array
    {
        return $this->cache->remember('stats_top_active_users', now()->addMinutes(10), function () {
            return User::query()
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->selectRaw('COUNT(tasks.id) as task_count')
                ->join('tasks', 'users.id', '=', 'tasks.created_by')
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->orderByDesc('task_count')
                ->limit(5)
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                    'email' => $user->email,
                    'task_count' => (int) $user->task_count,
                ])
                ->toArray();
        });
    }

    /**
     * Clear statistics cache when data changes.
     *
     * @param array $tags
     * @return void
     */
    public function clearCache(array $tags = []): void
    {
        $cacheTags = array_merge(['statistics'], $tags);
        $this->cache->tags($cacheTags)->flush();
    }

    /**
     * Get cached statistics without TTL refresh.
     *
     * @return StatisticsDTO|null
     */
    public function getCachedStatistics(): ?StatisticsDTO
    {
        return $this->cache->tags(['statistics'])->get('app_statistics');
    }
}
