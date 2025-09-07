<?php

namespace App\Listeners;

use App\Events\Task\TaskAssigned;
use App\Jobs\SendTaskAssignedNotification as SendTaskAssignedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Listener for task assignment events.
 */
class SendTaskAssignedNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public bool $failOnTimeout = true;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        $this->queue = config('notifications.task_assigned.queue', 'notifications');
    }

    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        // Send notification only if there's an assigned user
        if (! $event->task->assignedTo) {
            Log::debug('Task assignment notification skipped: no assigned user', [
                'task_id' => $event->task->id,
                'event' => TaskAssigned::NAME,
            ]);

            return;
        }

        $this->dispatchNotificationJob($event);

        Log::info('Task assignment notification queued', [
            'task_id' => $event->task->id,
            'assigned_to' => $event->task->assigned_to,
            'assigned_by' => $event->assignedBy->id,
            'event' => TaskAssigned::NAME,
        ]);
    }

    /**
     * Dispatch the notification job.
     */
    protected function dispatchNotificationJob(TaskAssigned $event): void
    {
        $job = new SendTaskAssignedNotificationJob(
            $event->task,
            $event->assignedBy
        );

        dispatch($job)
            ->onQueue($this->queue)
            ->delay($this->getDelayTime());
    }

    /**
     * Get the delay time for the job.
     */
    protected function getDelayTime(): ?int
    {
        $delay = config('notifications.task_assigned.delay', 0);

        return $delay > 0 ? $delay : null;
    }

    /**
     * Handle failed job.
     */
    public function failed(TaskAssigned $event, \Throwable $exception): void
    {
        Log::error('Task assignment notification listener failed', [
            'task_id' => $event->task->id,
            'assigned_to' => $event->task->assigned_to,
            'assigned_by' => $event->assignedBy->id,
            'exception' => $exception->getMessage(),
            'event' => TaskAssigned::NAME,
        ]);

        // Could send alert to admin or implement retry logic
    }
}
