<?php

namespace App\Listeners;

use App\Events\Project\ProjectStatusChanged;
use App\Jobs\SendProjectStatusChangedNotification as SendProjectStatusChangedNotificationJob;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Listener for project status change events.
 */
class SendProjectStatusChangedNotification implements ShouldQueue
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
        $this->queue = config('notifications.project_status.queue', 'notifications');
    }

    /**
     * Handle the event.
     */
    public function handle(ProjectStatusChanged $event): void
    {
        // Skip if status hasn't actually changed
        if ($event->oldStatus === $event->newStatus) {
            Log::debug('Project status change notification skipped: status unchanged', [
                'project_id' => $event->project->id,
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
                'event' => ProjectStatusChanged::NAME,
            ]);

            return;
        }

        // Get all eligible recipients
        $recipients = $this->getEligibleRecipients($event);

        if ($recipients->isEmpty()) {
            Log::debug('Project status change notification skipped: no eligible recipients', [
                'project_id' => $event->project->id,
                'event' => ProjectStatusChanged::NAME,
            ]);

            return;
        }

        // Dispatch notification jobs for each recipient
        $this->dispatchNotificationJobs($event, $recipients);

        Log::info('Project status change notifications queued', [
            'project_id' => $event->project->id,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'recipient_count' => $recipients->count(),
            'changed_by' => $event->changedBy->id,
            'event' => ProjectStatusChanged::NAME,
        ]);
    }

    /**
     * Get all eligible recipients for the notification.
     *
     * @return Collection<int, User>
     */
    protected function getEligibleRecipients(ProjectStatusChanged $event): Collection
    {
        $recipients = collect([$event->project->createdBy]);

        // Add all users assigned to tasks in this project
        foreach ($event->project->tasks as $task) {
            if ($task->assignedTo) {
                $recipients->push($task->assignedTo);
            }
        }

        // Remove duplicates and exclude the user who made the change
        return $recipients
            ->unique('id')
            ->reject(fn ($user) => $user->id === $event->changedBy->id);
    }

    /**
     * Dispatch notification jobs for each recipient.
     *
     * @param  Collection<int, User>  $recipients
     */
    protected function dispatchNotificationJobs(ProjectStatusChanged $event, Collection $recipients): void
    {
        $delay = $this->getDelayTime();

        foreach ($recipients as $recipient) {
            $job = new SendProjectStatusChangedNotificationJob(
                $event->project,
                $event->oldStatus,
                $event->newStatus,
                $event->changedBy,
                $recipient
            );

            dispatch($job)
                ->onQueue($this->queue)
                ->delay($delay);
        }
    }

    /**
     * Get the delay time for the jobs.
     */
    protected function getDelayTime(): ?int
    {
        $delay = config('notifications.project_status.delay', 0);

        return $delay > 0 ? $delay : null;
    }

    /**
     * Handle failed job.
     */
    public function failed(ProjectStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Project status change notification listener failed', [
            'project_id' => $event->project->id,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'changed_by' => $event->changedBy->id,
            'exception' => $exception->getMessage(),
            'event' => ProjectStatusChanged::NAME,
        ]);

        // Could send alert to admin or implement retry logic
    }
}
