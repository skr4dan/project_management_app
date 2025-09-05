<?php

namespace App\Services\Contracts;

use App\DTOs\Statistics\StatisticsDTO;

interface StatisticsServiceInterface
{
    /**
     * Get general statistics for the application.
     *
     * @return StatisticsDTO
     */
    public function getStatistics(): StatisticsDTO;

    /**
     * Clear statistics cache when data changes.
     *
     * @param array<string> $tags
     * @return void
     */
    public function clearCache(array $tags = []): void;

    /**
     * Get cached statistics without TTL refresh.
     *
     * @return StatisticsDTO|null
     */
    public function getCachedStatistics(): ?StatisticsDTO;
}
