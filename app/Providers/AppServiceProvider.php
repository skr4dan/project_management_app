<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\Contracts\AuthServiceInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TaskRepository;
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

        // Register JWT middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('jwt.auth', \App\Http\Middleware\JwtAuthenticate::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
