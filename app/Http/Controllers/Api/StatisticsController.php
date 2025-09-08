<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonResponse;
use App\Services\Contracts\StatisticsServiceInterface;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsServiceInterface $statisticsService,
    ) {}

    /**
     * Get general statistics
     */
    public function index(): LaravelJsonResponse
    {
        try {
            $statistics = $this->statisticsService->getStatistics();

            return JsonResponse::success($statistics->toArray(), 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve statistics');
        }
    }
}
