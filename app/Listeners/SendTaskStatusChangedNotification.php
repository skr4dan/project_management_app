<?php

namespace App\Listeners;

use App\Events\Task\TaskStatusChanged;
use App\Jobs\SendTaskStatusChangedNotification as SendTaskStatusChangedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Listener for task status change events.
 */
class SendTaskStatusChangedNotification implements ShouldQueue
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
        $this->queue = config('notifications.task_status.queue', 'notifications');
    }

    /**
     * Handle the event.
     */
    public function handle(TaskStatusChanged $event): void
    {
        // Send notification only if there's an assigned user
        if (! $event->task->assignedTo) {
            Log::debug('Task status change notification skipped: no assigned user', [
                'task_id' => $event->task->id,
                'event' => TaskStatusChanged::NAME,
            ]);

            return;
        }

        // Skip if status hasn't actually changed
        if ($event->oldStatus === $event->newStatus) {
            Log::debug('Task status change notification skipped: status unchanged', [
                'task_id' => $event->task->id,
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
                'event' => TaskStatusChanged::NAME,
            ]);

            return;
        }

        // Skip if we don't know who made the change
        if (! $event->changedBy) {
            Log::debug('Task status change notification skipped: no user who changed status', [
                'task_id' => $event->task->id,
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
                'event' => TaskStatusChanged::NAME,
            ]);

            return;
        }

        $changedBy = $event->changedBy; // At this point we know it's not null

        $this->dispatchNotificationJob($event, $changedBy);

        Log::info('Task status change notification queued', [
            'task_id' => $event->task->id,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'assigned_to' => $event->task->assigned_to,
            'changed_by' => $changedBy->id,
            'event' => TaskStatusChanged::NAME,
        ]);
    }

    /**
     * Dispatch the notification job.
     */
    protected function dispatchNotificationJob(TaskStatusChanged $event, \App\Models\User $changedBy): void
    {
        $job = new SendTaskStatusChangedNotificationJob(
            $event->task,
            $event->oldStatus,
            $event->newStatus,
            $changedBy
        );

        dispatch($job)
            ->onQueue($this->queue)
            ->delay($this->getDelayTime());
    }

    /**
     * Check if notifications should be sent.
     */
    protected function shouldSendNotification(): bool
    {
        return config('notifications.task_status.enabled', true);
    }

    /**
     * Get the delay time for the job.
     */
    protected function getDelayTime(): ?int
    {
        $delay = config('notifications.task_status.delay', 0);

        return $delay > 0 ? $delay : null;
    }

    /**
     * Handle failed job.
     */
    public function failed(TaskStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Task status change notification listener failed', [
            'task_id' => $event->task->id,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'assigned_to' => $event->task->assigned_to,
            'changed_by' => $event->changedBy?->id,
            'exception' => $exception->getMessage(),
            'event' => TaskStatusChanged::NAME,
        ]);

        // Could send alert to admin or implement retry logic
    }
}
