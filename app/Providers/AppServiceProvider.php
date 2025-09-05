<?php

namespace App\Providers;

use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\StatisticsServiceInterface;
use App\Services\StatisticsService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);

        // Register service bindings
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(StatisticsServiceInterface::class, function ($app) {
            return new StatisticsService(
                $app->make(\App\Repositories\Contracts\ProjectRepositoryInterface::class),
                $app->make(\App\Repositories\Contracts\TaskRepositoryInterface::class),
                $app->make(\App\Repositories\Contracts\UserRepositoryInterface::class),
                $app->make(Repository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Clear statistics cache when data changes
        $this->setupStatisticsCacheInvalidation();
    }

    /**
     * Setup cache invalidation for statistics when data changes.
     */
    private function setupStatisticsCacheInvalidation(): void
    {
        // Clear statistics cache when projects change
        \App\Models\Project::created(fn () => $this->clearStatisticsCache(['projects']));
        \App\Models\Project::updated(fn () => $this->clearStatisticsCache(['projects']));
        \App\Models\Project::deleted(fn () => $this->clearStatisticsCache(['projects']));

        // Clear statistics cache when tasks change
        \App\Models\Task::created(fn () => $this->clearStatisticsCache(['tasks']));
        \App\Models\Task::updated(fn () => $this->clearStatisticsCache(['tasks']));
        \App\Models\Task::deleted(fn () => $this->clearStatisticsCache(['tasks']));

        // Clear statistics cache when users change
        \App\Models\User::created(fn () => $this->clearStatisticsCache(['users']));
        \App\Models\User::updated(fn () => $this->clearStatisticsCache(['users']));
        \App\Models\User::deleted(fn () => $this->clearStatisticsCache(['users']));
    }

    /**
     * Clear statistics cache with specific tags.
     */
    private function clearStatisticsCache(array $tags): void
    {
        try {
            $statisticsService = app(StatisticsServiceInterface::class);
            $statisticsService->clearCache($tags);
        } catch (\Exception $e) {
            // Log error but don't break the application
            logger("Failed to clear statistics cache: {$e->getMessage()}");
        }
    }
}
