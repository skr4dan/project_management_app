<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonResponse;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\StatisticsServiceInterface;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsServiceInterface $statisticsService,
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Get general statistics (admin only)
     */
    public function index(): LaravelJsonResponse
    {
        try {
            $user = $this->authService->user();

            if (! $user || $user->role?->slug !== 'admin') {
                return JsonResponse::forbidden('Access denied. Admin privileges required.');
            }

            $statistics = $this->statisticsService->getStatistics();

            return JsonResponse::success($statistics->toArray(), 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve statistics');
        }
    }
}
