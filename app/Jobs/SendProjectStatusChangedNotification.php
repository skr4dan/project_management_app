<?php

namespace App\Jobs;

use App\Enums\Project\ProjectStatus;
use App\Mail\ProjectStatusChangedMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Job to send project status change notification email.
 */
class SendProjectStatusChangedNotification implements ShouldQueue
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
        public readonly Project $project,
        public readonly ProjectStatus $oldStatus,
        public readonly ProjectStatus $newStatus,
        public readonly User $changedBy,
        public readonly User $recipient
    ) {
        // Set queue from configuration
        $this->queue = config('notifications.project_status.queue', 'notifications');

        // Add delay if configured
        $delay = config('notifications.project_status.delay', 0);
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

        // Refresh the project data to ensure we have the latest state
        $this->project->refresh();

        $this->sendNotificationEmail();
    }

    /**
     * Send the notification email.
     */
    protected function sendNotificationEmail(): void
    {
        $mail = Mail::to($this->recipient->email);

        if (config('notifications.email.bcc_admin', false)) {
            $adminEmail = config('notifications.email.admin_email');
            if ($adminEmail) {
                $mail->bcc($adminEmail);
            }
        }

        $mail->send(new ProjectStatusChangedMail(
            $this->project,
            $this->oldStatus,
            $this->newStatus,
            $this->changedBy,
            $this->recipient
        ));

        logger()->info('Project status change notification sent successfully', [
            'project_id' => $this->project->id,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'recipient_id' => $this->recipient->id,
            'recipient_email' => $this->recipient->email,
            'changed_by' => $this->changedBy->id,
            'job_id' => $this->job?->getJobId(),
        ]);
    }

    /**
     * Check if the user has opted out of notifications.
     */
    protected function userHasOptedOut(User $user): bool
    {
        // Check user preferences or cache
        return Cache::get("user:{$user->id}:notification_opt_out", false);
    }

    /**
     * Check rate limiting for this job.
     */
    protected function checkRateLimit(): bool
    {
        if (! config('notifications.rate_limiting.enabled', true)) {
            return true;
        }

        $key = "notification:project_status:{$this->recipient->id}";
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
        logger()->error('Project status change notification failed', [
            'project_id' => $this->project->id,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'recipient_id' => $this->recipient->id,
            'recipient_email' => $this->recipient->email,
            'changed_by' => $this->changedBy->id,
            'exception' => $exception->getMessage(),
            'job_id' => $this->job?->getJobId(),
            'attempts' => $this->attempts(),
        ]);

        // Send alert to admin if configured
        if (config('notifications.email.admin_email')) {
            try {
                Mail::raw(
                    "Project status change notification failed for project {$this->project->id} to user {$this->recipient->id}: {$exception->getMessage()}",
                    fn ($message) => $message
                        ->to(config('notifications.email.admin_email'))
                        ->subject('Project Status Change Notification Failed')
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
            'project-status-changed',
            'project:'.$this->project->id,
            'user:'.$this->recipient->id,
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
        return 'project_status_'.$this->project->id.'_'.$this->recipient->id.'_'.now()->timestamp;
    }

    /**
     * Determine if the job should be unique.
     */
    public function uniqueFor(): int
    {
        return 300; // 5 minutes - prevent duplicate project status notifications to the same user
    }
}
