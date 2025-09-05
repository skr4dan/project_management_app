<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\StatisticsServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsServiceInterface $statisticsService,
    ) {}

    /**
     * Get general statistics
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = $this->statisticsService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics->toArray(),
                'message' => 'Statistics retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
