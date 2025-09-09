<?php

use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\AvatarFileService;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\AvatarFileServiceInterface;
use App\Services\Contracts\StatisticsServiceInterface;
use App\Services\StatisticsService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtAuthenticate::class,
        ]);
    })
    ->withBindings([
        // Register repository bindings
        UserRepositoryInterface::class => UserRepository::class,
        ProjectRepositoryInterface::class => ProjectRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        TaskRepositoryInterface::class => TaskRepository::class,

        // Register service bindings
        AuthServiceInterface::class => AuthService::class,
        AvatarFileServiceInterface::class => AvatarFileService::class,
        StatisticsServiceInterface::class => function ($app) {
            return new StatisticsService(
                $app->make(Repository::class)
            );
        },
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
