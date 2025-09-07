<?php

namespace App\Jobs;

use App\Enums\Task\TaskStatus;
use App\Mail\TaskStatusChangedMail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Job to send task status change notification email.
 */
class SendTaskStatusChangedNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * The maximum number of seconds the job should be allowed to run.
     */
    public $timeout = 30;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $oldStatus,
        public readonly TaskStatus $newStatus,
        public readonly User $changedBy
    ) {
        // Set queue from configuration
        $this->queue = config('notifications.task_status.queue', 'notifications');

        // Add delay if configured
        $delay = config('notifications.task_status.delay', 0);
        if ($delay > 0) {
            $this->delay = now()->addSeconds($delay);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Rate limiting check
        if (! $this->checkRateLimit()) {
            $this->release(60); // Retry after 1 minute

            return;
        }

        // Refresh the task data to ensure we have the latest state
        $this->task->refresh();

        $recipient = $this->task->assignedTo;
        if (is_null($recipient)) {
            logger()->warning('Task status change notification skipped: task has no assigned user', [
                'task_id' => $this->task->id,
                'old_status' => $this->oldStatus->value,
                'new_status' => $this->newStatus->value,
                'job_id' => $this->job?->getJobId(),
            ]);

            return;
        }

        $this->sendNotificationEmail($recipient);
    }

    /**
     * Send the notification email.
     */
    protected function sendNotificationEmail(User $recipient): void
    {
        $mail = Mail::to($recipient->email);

        if (config('notifications.email.bcc_admin', false)) {
            $adminEmail = config('notifications.email.admin_email');
            if ($adminEmail) {
                $mail->bcc($adminEmail);
            }
        }

        $mail->send(new TaskStatusChangedMail($this->task, $this->oldStatus, $this->newStatus, $this->changedBy));

        logger()->info('Task status change notification sent successfully', [
            'task_id' => $this->task->id,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'recipient_id' => $recipient->id,
            'recipient_email' => $recipient->email,
            'job_id' => $this->job?->getJobId(),
        ]);
    }

    /**
     * Check rate limiting for this job.
     */
    protected function checkRateLimit(): bool
    {
        if (! config('notifications.rate_limiting.enabled', true)) {
            return true;
        }

        $key = "notification:task_status:{$this->task->assigned_to}";
        $maxAttempts = config('notifications.rate_limiting.max_per_minute', 10);

        return RateLimiter::attempt(
            $key,
            $maxAttempts,
            fn () => true,
            60 // 1 minute window
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        logger()->error('Task status change notification failed', [
            'task_id' => $this->task->id,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'assigned_to' => $this->task->assigned_to,
            'changed_by' => $this->changedBy->id,
            'exception' => $exception->getMessage(),
            'job_id' => $this->job?->getJobId(),
            'attempts' => $this->attempts(),
        ]);

        // Send alert to admin if configured
        if (config('notifications.email.admin_email')) {
            try {
                Mail::raw(
                    "Task status change notification failed for task {$this->task->id}: {$exception->getMessage()}",
                    fn ($message) => $message
                        ->to(config('notifications.email.admin_email'))
                        ->subject('Task Status Change Notification Failed')
                );
            } catch (\Throwable $e) {
                logger()->error('Failed to send admin alert', [
                    'original_exception' => $exception->getMessage(),
                    'alert_exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'notification',
            'task-status-changed',
            'task:'.$this->task->id,
            'user:'.$this->task->assigned_to,
            'changed-by:'.$this->changedBy->id,
            'old-status:'.$this->oldStatus->value,
            'new-status:'.$this->newStatus->value,
        ];
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'task_status_'.$this->task->id.'_'.$this->task->assigned_to.'_'.now()->timestamp;
    }

    /**
     * Determine if the job should be unique.
     */
    public function uniqueFor(): int
    {
        return 180; // 3 minutes - prevent duplicate status change notifications
    }
}
