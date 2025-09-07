<?php

namespace App\Listeners;

use App\Events\Project\ProjectStatusChanged;
use App\Events\Task\TaskAssigned;
use App\Events\Task\TaskStatusChanged;
use App\Services\Contracts\StatisticsServiceInterface;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Listener for invalidating statistics cache when data changes.
 *
 * Handles all model events that should trigger cache invalidation.
 */
class StatisticsCacheInvalidationListener
{
    use Queueable;

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly StatisticsServiceInterface $statisticsService
    ) {}

    /**
     * Handle model events that require cache invalidation.
     */
    public function handle(ProjectStatusChanged|TaskStatusChanged|TaskAssigned $event): void
    {
        $tags = $this->getCacheTags($event);

        if (! empty($tags)) {
            $this->clearStatisticsCache($tags, $event);
        }
    }

    /**
     * Get cache tags based on the event type.
     *
     * @return array<string>
     */
    private function getCacheTags(ProjectStatusChanged|TaskStatusChanged|TaskAssigned $event): array
    {
        if ($event instanceof ProjectStatusChanged) {
            return ['projects'];
        }

        // At this point, event must be TaskStatusChanged or TaskAssigned
        return ['tasks'];
    }

    /**
     * Clear statistics cache with specific tags.
     *
     * @param  array<string>  $tags
     */
    private function clearStatisticsCache(array $tags, ProjectStatusChanged|TaskStatusChanged|TaskAssigned $event): void
    {
        try {
            $this->statisticsService->clearCache($tags);

            Log::debug('Statistics cache cleared', [
                'tags' => $tags,
                'event' => $event::NAME,
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the application
            Log::error("Failed to clear statistics cache: {$e->getMessage()}", [
                'tags' => $tags,
                'event' => $event::NAME,
                'exception' => $e,
            ]);
        }
    }
}
